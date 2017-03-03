<?php
namespace Makasim\Yadm\Tests\Functional;

use Makasim\Yadm\Hydrator;
use Makasim\Yadm\PessimisticLock;
use Makasim\Yadm\MongodbStorage;
use MongoDB\BSON\ObjectID;
use MongoDB\InsertOneResult;

class MongodbStorageTest extends FunctionalTest
{
    public function testCreateModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator);


        $model = $storage->create();

        self::assertInstanceOf(Model::class, $model);
        self::assertEquals([], $model->values);
    }

    public function testInsertModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator);

        $model = new Model();
        $model->values = ['foo' => 'fooVal', 'bar' => 'barVal', 'ololo' => ['foo', 'foo' => 'fooVal']];

        $result = $storage->insert($model);

        self::assertInstanceOf(InsertOneResult::class, $result);
        self::assertTrue($result->isAcknowledged());

        self::assertArrayHasKey('_id', $model->values);
        self::assertNotEmpty($model->values['_id']);
        self::assertInternalType('string', $model->values['_id']);

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->values['_id'])]);

        self::assertInstanceOf(Model::class, $foundModel);
        self::assertEquals($model->values, $foundModel->values);
    }

    public function testUpdateModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator);

        $model = new Model();
        $model->values = ['foo' => 'fooVal', 'bar' => 'barVal'];

        $result = $storage->insert($model);

        //guard
        self::assertTrue($result->isAcknowledged());

        $model->values['ololo'] = 'ololoVal';

        $result = $storage->update($model);

        //guard
        self::assertTrue($result->isAcknowledged());

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->values['_id'])]);

        self::assertInstanceOf(Model::class, $foundModel);
        self::assertEquals($model->values, $foundModel->values);
    }

    public function testDeleteModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator);

        $model = new Model();
        $model->values = ['foo' => 'fooVal', 'bar' => 'barVal'];

        $result = $storage->insert($model);

        //guard
        self::assertTrue($result->isAcknowledged());

        $result = $storage->delete($model);

        //guard
        self::assertTrue($result->isAcknowledged());

        self::assertNull($storage->findOne(['_id' => new ObjectID($model->values['_id'])]));
    }

    public function testUpdateModelPessimisticLock()
    {
        $lockCollection = $this->database->selectCollection('storage_lock_test');
        $pessimisticLock = new PessimisticLock($lockCollection);
        $pessimisticLock->createIndexes();

        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator, null, $pessimisticLock);

        $model = new Model();
        $model->values = ['foo' => 'fooVal', 'bar' => 'barVal'];

        $result = $storage->insert($model);

        //guard
        self::assertTrue($result->isAcknowledged());

        $storage->lock($model->values['_id'], function($lockedModel, $storage) use ($model) {
            self::assertInstanceOf(Model::class, $lockedModel);
            self::assertEquals($model->values, $lockedModel->values);

            self::assertInstanceOf(MongodbStorage::class, $storage);

            $model->values['ololo'] = 'ololoVal';

            $result = $storage->update($model);

            //guard
            self::assertTrue($result->isAcknowledged());
        });

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->values['_id'])]);

        self::assertInstanceOf(Model::class, $foundModel);
        self::assertEquals($model->values, $foundModel->values);
    }

    public function testFindModels()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator);

        $result = $storage->find([]);

        self::assertInstanceOf(\Traversable::class, $result);
        self::assertCount(0, iterator_to_array($result));

        $storage->insert(new Model());
        $storage->insert(new Model());
        $storage->insert(new Model());

        $result = $storage->find([]);

        self::assertInstanceOf(\Traversable::class, $result);
        $data = iterator_to_array($result);

        self::assertCount(3, $data);
        self::assertContainsOnly(Model::class, $data);
    }
}

class Model
{
    public $values = [];
    public $hookId;
}