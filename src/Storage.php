<?php
namespace Makasim\Yadm;

use function Makasim\Values\get_values;
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
     * @param Collection $collection
     * @param Hydrator $hydrator
     * @param ChangesCollector $changesCollector
     * @param PessimisticLock|null $pessimisticLock
     */
    public function __construct(
        Collection $collection,
        Hydrator $hydrator,
        ChangesCollector $changesCollector = null,
        PessimisticLock $pessimisticLock = null
    ) {
        $this->collection = $collection;
        $this->hydrator = $hydrator;
        $this->changesCollector = $changesCollector ?: new ChangesCollector();
        $this->pessimisticLock = $pessimisticLock;
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

        $result = $this->collection->insertOne(get_values($model, false), $options);
        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Operation is not acknowledged');
        }

        set_object_id($model, new ObjectID((string) $result->getInsertedId()));
        $this->changesCollector->register($model);

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
            $data[$key] =get_values($model, false);
        }

        $result = $this->collection->insertMany($data, $options);
        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Operation is not acknowledged');
        }

        foreach ($result->getInsertedIds() as $key => $modelId) {
            $this->hydrator->hydrate($data[$key], $models[$key]);
            set_object_id($models[$key], $modelId);

            $this->changesCollector->register($models[$key]);
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

        $update = $this->changesCollector->changes($model);
        if (empty($update)) {
            return;
        }

        $result = $this->collection->updateOne($filter, $update, $options);

        if ($result->getUpsertedCount()) {
            set_object_id($model, new ObjectID((string) $result->getUpsertedId()));
        }

        $this->changesCollector->register($model);

        return $result;
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

        if ($values = $this->collection->findOne($filter, $options)) {
            $object = $this->hydrator->hydrate($values);

            $this->changesCollector->register($object);

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

        foreach ($cursor as $values) {
            $object = $this->hydrator->hydrate($values);

            $this->changesCollector->register($object);

            yield $object;
        }
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
     * @param $id
     * @param callable $lockCallback
     *
     * @return mixed
     */
    public function lock($id, callable $lockCallback)
    {
        if (false == $this->pessimisticLock) {
            throw new \LogicException('Cannot lock. The PessimisticLock instance is not injected');
        }

        $this->pessimisticLock->lock($id);
        $result = null;
        try {
            if ($model = $this->findOne(['_id' => new ObjectID((string) $id)])) {
                $result = call_user_func($lockCallback, $model, $this);
            }

            return $result;
        } finally {
            $this->pessimisticLock->unlock($id);
        }
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
