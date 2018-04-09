<?php
namespace Makasim\Yadm\Tests;

use function Makasim\Values\add_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;
use function Makasim\Values\set_values;
use Makasim\Yadm\ChangesCollector;
use function Makasim\Yadm\set_object_id;
use Makasim\Yadm\Tests\Model\TestObject;
use MongoDB\BSON\ObjectID;
use PHPUnit\Framework\TestCase;

class ChangesCollectorTest extends TestCase
{
    public function testShouldTrackSetValue()
    {
        $obj = $this->createPersistedObject();

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        set_value($obj, 'aKey', 'aVal');

        self::assertEquals([
            '$set' => [
                'aKey' => 'aVal',
            ],
        ], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));

        // 417025ae3572262667ac5686ce5242722228d7011c335d62e760b5337f48db09
    }

    public function testShouldTrackAddedValueToEmptyCollection()
    {
        $obj = $this->createPersistedObject();

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        add_value($obj, 'aKey', 'aVal');

        self::assertEquals([
            '$set' => [
                'aKey.0' => 'aVal',
            ],
        ], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));
    }

    public function testShouldSkipMongoIdField()
    {
        $obj = $this->createPersistedObject();
        set_value($obj, '_id',123);

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        set_value($obj, '_id',321);

        self::assertEquals([], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));
    }

    public function testShouldUseWholeValuesIfNotRegistered()
    {
        $collector = new ChangesCollector();

        $obj = new TestObject();
        set_value($obj, 'foo','fooVal');
        set_value($obj, 'bar.baz','barVal');

        self::assertEquals([
            '$set' => [
                'foo' => 'fooVal',
                'bar' => ['baz' => 'barVal'],
            ],
        ], $collector->changes($obj, []));
    }

    public function testShouldTrackAddedValue()
    {
        $obj = $this->createPersistedObject();
        add_value($obj, 'aKey', 'anOldVal');

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        add_value($obj, 'aKey', 'aVal');

        self::assertEquals([
            '$set' => [
                'aKey.1' => 'aVal',
            ],
        ], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));
    }

    public function testShouldNotTrackSetValueAndUnsetLater()
    {
        $obj = $this->createPersistedObject();

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        set_value($obj, 'aKey', 'aVal');
        set_value($obj, 'aKey', null);

        self::assertEquals([], $collector->changes($obj, []));
    }

    public function testShouldTrackUnsetValue()
    {
        $obj = $this->createPersistedObject(['aKey' => 'aVal']);
        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        set_value($obj, 'aKey', null);

        self::assertEquals([
            '$unset' => [
                'aKey' => '',
            ]
        ], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));
    }

    public function testShouldTrackChangedValue()
    {
        $obj = $this->createPersistedObject(['aKey' => 'aVal']);

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        set_value($obj, 'aKey', 'aNewVal');

        self::assertEquals([
            '$set' => [
                'aKey' => 'aNewVal',
            ],
        ], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));
    }

    public function testShouldTrackStringValueChangedToArrayValue()
    {
        $obj = $this->createPersistedObject(['aKey' => 'aVal']);

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        set_value($obj, 'aKey.fooKey', 'aFooVal');
        set_value($obj, 'aKey.barKey', 'aBarVal');

        self::assertEquals([
            '$set' => [
                'aKey' => [
                    'fooKey' => 'aFooVal',
                    'barKey' => 'aBarVal',
                ],
            ],
        ], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));
    }

    public function testShouldTrackArrayValueChangedToStringValue()
    {
        $obj = $this->createPersistedObject([
            'aKey' => [
                'fooKey' => 'aFooVal',
                'barKey' => 'aBarVal',
            ]
        ]);

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        set_value($obj, 'aKey', 'aVal');

        self::assertEquals([
            '$set' => [
                'aKey' => 'aVal',
            ],
        ], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));
    }

    public function testShouldFoo()
    {
        $obj = $this->createPersistedObject([
            'aKey' => 'aVal',
        ]);

        $collector = new ChangesCollector();
        $collector->register($obj, get_values($obj));

        set_value($obj, 'aKey', null);
        set_value($obj, 'anotherKey', 'aVal');

        self::assertEquals([
            '$set' => [
                'anotherKey' => 'aVal',
            ],
            '$unset' => [
                'aKey' => '',
            ],
        ], $collector->changes(get_values($obj), $collector->getOriginalValues($obj)));
    }

    /**
     * @return object
     */
    private function createPersistedObject(array $values = [])
    {
        $obj = new TestObject();
        set_values($obj, $values);
        set_object_id($obj, new ObjectID());

        return $obj;
    }
}