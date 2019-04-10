<?php
namespace Formapro\Yadm;

use function Formapro\Values\get_value;
use function Formapro\Values\get_values;
use function Formapro\Values\set_value;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;

class Storage
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * @var ChangesCollector
     */
    private $changesCollector;

    /**
     * @var PessimisticLock
     */
    private $pessimisticLock;

    /**
     * @var ConvertValues
     */
    private $convertValues;

    /**
     * @var StorageMetaInterface
     */
    private $storageMeta;

    public function __construct(
        Collection $collection,
        Hydrator $hydrator,
        ChangesCollector $changesCollector = null,
        PessimisticLock $pessimisticLock = null,
        ConvertValues $convertValues = null,
        StorageMetaInterface $storageMeta = null
    ) {
        $this->collection = $collection;
        $this->hydrator = $hydrator;
        $this->pessimisticLock = $pessimisticLock;

        $this->changesCollector = $changesCollector ?: new ChangesCollector();
        $this->convertValues = $convertValues ?: new ConvertValues([]);
        
        if ($storageMeta) {
            $this->storageMeta = $storageMeta;
        } elseif ($storageMeta === null && $this instanceof StorageMetaInterface) {
            $this->storageMeta = $this;
        } else {
            $this->storageMeta = new StorageMeta();    
        }
    }

    /**
     * @return object
     */
    public function create()
    {
        return $this->hydrator->create();
    }

    /**
     * @param object $model
     * @param array  $options
     *
     * @return \MongoDB\InsertOneResult
     */
    public function insert($model, array $options = [])
    {
        $values = $this->convertValues->convertToMongoValues(get_values($model), []);

        $result = $this->collection->insertOne($values, $options);
        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Operation is not acknowledged');
        }

        set_object_id($model, new ObjectID((string) $result->getInsertedId()));
        $this->changesCollector->register($model, $values);

        return $result;
    }

    /**
     * @param object[] $models
     * @param array  $options
     *
     * @return \MongoDB\InsertManyResult
     */
    public function insertMany(array $models, array $options = [])
    {
        $data = [];
        foreach ($models as $key => $model) {
            $data[$key] = get_values($model, false);
        }

        $result = $this->collection->insertMany($data, $options);
        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Operation is not acknowledged');
        }

        foreach ($result->getInsertedIds() as $key => $modelId) {
            $this->hydrator->hydrate($data[$key], $models[$key]);
            set_object_id($models[$key], $modelId);

            $this->changesCollector->register($models[$key], $data[$key]);
        }

        return $result;
    }

    /**
     * @param object     $model
     * @param null|array $filter
     * @param array      $options
     *
     * @return \MongoDB\UpdateResult|null
     */
    public function update($model, $filter = null, array $options = [])
    {
        if (null === $filter) {
            $filter = ['_id' => get_object_id($model)];
        }

        $useOptimisticLock = false;
        if (isset($options['optimistic_lock']) && $options['optimistic_lock']) {
            $useOptimisticLock = true;

            if (false == $version = get_value($model, 'version', false)) {
                throw new \LogicException('The optimistic lock has been requested but version field is not set on the model');
            }

            $filter = array_merge($filter, ['version' => $version]);
        }
        unset($options['optimistic_lock']);

        $originalValues = $this->changesCollector->getOriginalValues($model);
        $values = $this->convertValues->convertToMongoValues(get_values($model, true), $originalValues ?: []);

        $update = $this->changesCollector->changes($values, $originalValues);
        if (empty($update)) {
            return;
        }

        if ($useOptimisticLock) {
            if (false == isset($update['$inc'])) {
                $update['$inc'] = [];
            }

            $update['$inc']['version'] = 1;
        }

        // mongodb's update cannot do a change of existing element and push a new one to a collection.
        $pushUpdate = [];
        $result = null;
        if (array_key_exists('$push', $update)) {
            $pushUpdate['$push'] = $update['$push'];
            unset($update['$push']);

            $this->collection->updateOne($filter, $pushUpdate, $options);

            if ($update) {
                $result = $this->collection->updateOne($filter, $update, $options);
            }
        } else {
            $result = $this->collection->updateOne($filter, $update, $options);
        }

        if ($useOptimisticLock && 0 === $result->getModifiedCount()) {
            throw OptimisticLockException::lockFailed();
        }

        if ($useOptimisticLock) {
            set_value($model, 'version', $version + 1);
        }

        if ($result && $result->getUpsertedCount()) {
            set_object_id($model, new ObjectID((string) $result->getUpsertedId()));
        }

        $this->changesCollector->register($model, $values ?: []);

        return $result;
    }

    /**
     * @param object     $model
     * @param null|array $filter
     * @param array      $options
     *
     * @return \MongoDB\UpdateResult|null
     */
    public function replace($model, array $options = [])
    {
        $filter = ['_id' => get_object_id($model)];
        
        return $this->getCollection()->replaceOne($filter, get_values($model), $options);
    }

    /**
     * @param object $model
     * @param array  $options
     *
     * @return \MongoDB\DeleteResult
     */
    public function delete($model, array $options = [])
    {
        return $this->collection->deleteOne(['_id' => get_object_id($model)], $options);
    }

    /**
     * @param array $filter
     * @param array $options
     *
     * @return object
     */
    public function findOne(array $filter = [], array $options = [])
    {
        $options['typeMap'] = ['root' => 'array', 'document' => 'array', 'array' => 'array'];

        if ($originalValues = $this->collection->findOne($filter, $options)) {
            $values = $this->convertValues->convertToPHPValues($originalValues);


            $object = $this->hydrator->hydrate($values);

            $this->changesCollector->register($object, $originalValues);

            return $object;
        }
    }

    /**
     * @param array $filter
     * @param array $options
     *
     * @return \Traversable
     */
    public function find(array $filter = [], array $options = [])
    {
        $cursor = $this->collection->find($filter, $options);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        foreach ($cursor as $originalValues) {
            $values = $this->convertValues->convertToPHPValues($originalValues);

            $object = $this->hydrator->hydrate($values);

            $this->changesCollector->register($object, $originalValues);

            yield $object;
        }
    }
    
    public function register($object, array $originalValues)
    {
        $this->changesCollector->register($object, $originalValues);
    }

    /**
     * @param array $filter
     * @param array $options
     *
     * @return int
     */
    public function count(array $filter = [], array $options = [])
    {
        return $this->collection->count($filter, $options);
    }

    /**
     * @param object|ObjectID|string $id
     * @param callable $lockCallback
     * @param bool $blocking
     * @param int $limit
     *
     * @return mixed
     */
    public function lock($id, callable $lockCallback, bool $blocking = true, int $limit = 300)
    {
        if ($id instanceof ObjectID) {
            // do nothing
        } elseif (is_object($id)) {
            $id = get_object_id($id);
        } elseif (is_string($id)) {
            $id = new ObjectID((string) $id);
        } else {
            throw new \LogicException('The given id is not supported. Could be a model object, Mongo\'s ObjectID or a string model id.');
        }

        if (false == $this->pessimisticLock) {
            throw new \LogicException('Cannot lock. The PessimisticLock instance is not injected');
        }

        $this->pessimisticLock->lock($id, $blocking, $limit);
        $result = null;
        try {
            if ($model = $this->findOne(['_id' => $id])) {
                $result = call_user_func($lockCallback, $model, $this);
            }

            return $result;
        } finally {
            $this->pessimisticLock->unlock($id);
        }
    }

    public function getMeta(): StorageMetaInterface
    {
        return $this->storageMeta;
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @return PessimisticLock
     */
    public function getPessimisticLock(): ?PessimisticLock
    {
        return $this->pessimisticLock;
    }

    protected function getHydrator(): Hydrator
    {
        return $this->hydrator;
    }

    protected function getChangesCollector(): ChangesCollector
    {
        return $this->changesCollector;
    }

    protected function getConvertValues(): ConvertValues
    {
        return $this->convertValues;
    }
}
