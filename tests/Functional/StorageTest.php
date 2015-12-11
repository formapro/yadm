<?php
namespace Makasim\Yadm\Tests\Functional;

use Makasim\Yadm\Hydrator;
use Makasim\Yadm\Iterator;
use Makasim\Yadm\PessimisticLock;
use Makasim\Yadm\Storage;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Persistable;
use MongoDB\InsertOneResult;

class StorageTest extends FunctionalTest
{
    public function testCreateModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new Storage($collection, $hydrator);


        $model = $storage->create();

        $this->assertInstanceOf(Model::class, $model);
        $this->assertEquals([], $model->data);
    }

    public function testInsertModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new Storage($collection, $hydrator);

        $model = new Model();
        $model->data = ['foo' => 'fooVal', 'bar' => 'barVal', 'ololo' => ['foo', 'foo' => 'fooVal']];

        $result = $storage->insert($model);

        $this->assertInstanceOf(InsertOneResult::class, $result);
        $this->assertTrue($result->isAcknowledged());

        $this->assertArrayHasKey('_id', $model->data);
        $this->assertNotEmpty($model->data['_id']);
        $this->assertInternalType('string', $model->data['_id']);

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->data['_id'])]);

        $this->assertInstanceOf(Model::class, $foundModel);
        $this->assertEquals($model->data, $foundModel->data);
    }

    public function testUpdateModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new Storage($collection, $hydrator);

        $model = new Model();
        $model->data = ['foo' => 'fooVal', 'bar' => 'barVal'];

        $result = $storage->insert($model);

        //guard
        $this->assertTrue($result->isAcknowledged());

        $model->data['ololo'] = 'ololoVal';

        $result = $storage->update($model);

        //guard
        $this->assertTrue($result->isAcknowledged());

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->data['_id'])]);

        $this->assertInstanceOf(Model::class, $foundModel);
        $this->assertEquals($model->data, $foundModel->data);
    }

    public function testDeleteModel()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new Storage($collection, $hydrator);

        $model = new Model();
        $model->data = ['foo' => 'fooVal', 'bar' => 'barVal'];

        $result = $storage->insert($model);

        //guard
        $this->assertTrue($result->isAcknowledged());

        $result = $storage->delete($model);

        //guard
        $this->assertTrue($result->isAcknowledged());

        $this->assertNull($storage->findOne(['_id' => new ObjectID($model->data['_id'])]));
    }

    public function testUpdateModelPessimisticLock()
    {
        $lockCollection = $this->database->selectCollection('storage_lock_test');
        $pessimisticLock = new PessimisticLock($lockCollection);
        $pessimisticLock->createIndexes();

        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new Storage($collection, $hydrator, $pessimisticLock);

        $model = new Model();
        $model->data = ['foo' => 'fooVal', 'bar' => 'barVal'];

        $result = $storage->insert($model);

        //guard
        $this->assertTrue($result->isAcknowledged());

        $storage->lock($model->data['_id'], function($lockedModel, $storage) use ($model) {
            $this->assertInstanceOf(Model::class, $lockedModel);
            $this->assertEquals($model->data, $lockedModel->data);

            $this->assertInstanceOf(Storage::class, $storage);

            $model->data['ololo'] = 'ololoVal';

            $result = $storage->update($model);

            //guard
            $this->assertTrue($result->isAcknowledged());
        });

        $foundModel = $storage->findOne(['_id' => new ObjectID($model->data['_id'])]);

        $this->assertInstanceOf(Model::class, $foundModel);
        $this->assertEquals($model->data, $foundModel->data);
    }

    public function testFindModels()
    {
        $collection = $this->database->selectCollection('storage_test');
        $hydrator = new Hydrator(Model::class);

        $storage = new Storage($collection, $hydrator);

        $result = $storage->find([]);

        $this->assertInstanceOf(Iterator::class, $result);
        $this->assertCount(0, iterator_to_array($result));

        $storage->insert(new Model());
        $storage->insert(new Model());
        $storage->insert(new Model());

        $result = $storage->find([]);

        $this->assertInstanceOf(Iterator::class, $result);
        $data = iterator_to_array($result);

        $this->assertCount(3, $data);
        $this->assertContainsOnly(Model::class, $data);
    }
}

class Model implements Persistable
{
    public $data = [];

    public function bsonSerialize()
    {
        return $this->data;
    }

    public function bsonUnserialize(array $data = array())
    {
        $this->data = $data;
    }
}