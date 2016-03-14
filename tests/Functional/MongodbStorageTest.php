<?php
namespace Makasim\Yadm\Tests\Functional;

use Makasim\Yadm\Hydrator;
use Makasim\Yadm\PessimisticLock;
use Makasim\Yadm\MongodbStorage;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Persistable;
use MongoDB\InsertOneResult;

class MongodbStorageTest extends FunctionalTest
{
    public function testCreateModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator);


        $model = $storage->create();

        $this->assertInstanceOf(Model::class, $model);
        $this->assertEquals([], $model->values);
    }

    public function testInsertModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator);

        $model = new Model();
        $model->values = ['foo' => 'fooVal', 'bar' => 'barVal', 'ololo' => ['foo', 'foo' => 'fooVal']];

        $result = $storage->insert($model);

        $this->assertInstanceOf(InsertOneResult::class, $result);
        $this->assertTrue($result->isAcknowledged());

        $this->assertArrayHasKey('_id', $model->values);
        $this->assertNotEmpty($model->values['_id']);
        $this->assertInternalType('string', $model->values['_id']);

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->values['_id'])]);

        $this->assertInstanceOf(Model::class, $foundModel);
        $this->assertEquals($model->values, $foundModel->values);
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
        $this->assertTrue($result->isAcknowledged());

        $model->values['ololo'] = 'ololoVal';

        $result = $storage->update($model);

        //guard
        $this->assertTrue($result->isAcknowledged());

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->values['_id'])]);

        $this->assertInstanceOf(Model::class, $foundModel);
        $this->assertEquals($model->values, $foundModel->values);
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
        $this->assertTrue($result->isAcknowledged());

        $result = $storage->delete($model);

        //guard
        $this->assertTrue($result->isAcknowledged());

        $this->assertNull($storage->findOne(['_id' => new ObjectID($model->values['_id'])]));
    }

    public function testUpdateModelPessimisticLock()
    {
        $lockCollection = $this->database->selectCollection('storage_lock_test');
        $pessimisticLock = new PessimisticLock($lockCollection);
        $pessimisticLock->createIndexes();

        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator, $pessimisticLock);

        $model = new Model();
        $model->values = ['foo' => 'fooVal', 'bar' => 'barVal'];

        $result = $storage->insert($model);

        //guard
        $this->assertTrue($result->isAcknowledged());

        $storage->lock($model->values['_id'], function($lockedModel, $storage) use ($model) {
            $this->assertInstanceOf(Model::class, $lockedModel);
            $this->assertEquals($model->values, $lockedModel->values);

            $this->assertInstanceOf(MongodbStorage::class, $storage);

            $model->values['ololo'] = 'ololoVal';

            $result = $storage->update($model);

            //guard
            $this->assertTrue($result->isAcknowledged());
        });

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->values['_id'])]);

        $this->assertInstanceOf(Model::class, $foundModel);
        $this->assertEquals($model->values, $foundModel->values);
    }

    public function testFindModels()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new MongodbStorage($collection, $hydrator);

        $result = $storage->find([]);

        $this->assertInstanceOf(\Traversable::class, $result);
        $this->assertCount(0, iterator_to_array($result));

        $storage->insert(new Model());
        $storage->insert(new Model());
        $storage->insert(new Model());

        $result = $storage->find([]);

        $this->assertInstanceOf(\Traversable::class, $result);
        $data = iterator_to_array($result);

        $this->assertCount(3, $data);
        $this->assertContainsOnly(Model::class, $data);
    }
}

class Model
{
    public $values = [];
}