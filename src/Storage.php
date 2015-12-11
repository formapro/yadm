<?php
namespace Makasim\Yadm;

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Persistable;
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
     * @var PessimisticLock
     */
    private $pessimisticLock;

    /**
     * @param Collection $collection
     * @param Hydrator $hydrator
     * @param PessimisticLock|null $pessimisticLock
     */
    public function __construct(Collection $collection, Hydrator $hydrator, PessimisticLock $pessimisticLock = null)
    {
        $this->collection = $collection;
        $this->hydrator = $hydrator;
        $this->pessimisticLock = $pessimisticLock;
    }

    /**
     * @return Persistable
     */
    public function create()
    {
        return $this->hydrator->create();
    }

    /**
     * @param Persistable $model
     * @param array       $options
     *
     * @return \MongoDB\InsertOneResult
     */
    public function insert(Persistable $model, array $options = [])
    {
        $bson = $model->bsonSerialize();

        $result = $this->collection->insertOne($bson, $options);
        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Operation is not acknowledged');
        }

        $bson['_id'] = (string) $result->getInsertedId();

        $this->hydrator->hydrate($bson, $model);

        return $result;
    }

    /**
     * @param Persistable $model
     * @param array       $options
     *
     * @return \MongoDB\UpdateResult
     */
    public function update(Persistable $model, array $options = [])
    {
        $bson = $model->bsonSerialize();

        $modelId = $bson['_id'];
        unset($bson['_id']);

        $result = $this->collection->updateOne(['_id' => new ObjectID($modelId)], ['$set' => $bson], $options);
        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Operation is not acknowledged');
        }

        return $result;
    }

    /**
     * @param Persistable $model
     * @param array       $options
     *
     * @return \MongoDB\DeleteResult
     */
    public function delete(Persistable $model, array $options = [])
    {
        $bson = $model->bsonSerialize();

        if (is_array($bson)) {
            $modelId = $bson['_id'];
            unset($bson['_id']);
        } else {
            $modelId = $bson->_id;
            unset($bson->_id);
        }

        $result = $this->collection->deleteOne(['_id' => new ObjectID($modelId)], $options);
        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Operation is not acknowledged');
        }

        return $result;
    }

    /**
     * @param array $filter
     * @param array $options
     *
     * @return Persistable
     */
    public function findOne(array $filter = [], array $options = [])
    {
        $options['typeMap'] = ['root' => 'array', 'document' => 'array', 'array' => 'array'];

        if ($bson = $this->collection->findOne($filter, $options)) {
            return $this->hydrator->hydrate($bson);
        }
    }

    /**
     * @param array $filter
     * @param array $options
     *
     * @return Iterator
     */
    public function find(array $filter = [], array $options = [])
    {
        $cursor = $this->collection->find($filter, $options);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        return new Iterator($cursor, $this->hydrator);
    }

    /**
     * @param $id
     * @param callable $lockCallback
     */
    public function lock($id, callable $lockCallback)
    {
        if (false == $this->pessimisticLock) {
            throw new \LogicException('Cannot lock. The PessimisticLock instance is not injected');
        }

        $this->pessimisticLock->lock($id);
        try {
            if ($model = $this->findOne(['_id' => (string) $id])) {
                call_user_func($lockCallback, $model, $this);
            }
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

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return new Repository($this);
    }
}
