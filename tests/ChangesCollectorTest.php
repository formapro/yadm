<?php
namespace Makasim\Yadm\Tests;

use function Makasim\Values\add_value;
use function Makasim\Values\set_value;
use function Makasim\Values\set_values;
use Makasim\Yadm\ChangesCollector;
use function Makasim\Yadm\set_object_id;
use Makasim\Yadm\Tests\Model\Object;
use MongoDB\BSON\ObjectID;
use PHPUnit\Framework\TestCase;

class ChangesCollectorTest extends TestCase
{
    /**
     * @group d
     */
    public function testShouldTrackSetValue()
    {
        $obj = $this->createPersistedObject();

        $collector = new ChangesCollector();
        $collector->register($obj);

        set_value($obj, 'aKey', 'aVal');
var_dump($collector->changes($obj));
        self::assertEquals([
            '$set' => [
                'aKey' => 'aVal',
            ],
        ], $collector->changes($obj));
    }

    public function testShouldTrackAddedValue()
    {
        $obj = $this->createPersistedObject();

        $collector = new ChangesCollector();
        $collector->register($obj);

        add_value($obj, 'aKey', 'aVal');

        self::assertEquals([
            '$set' => [
                'aKey.0' => 'aVal',
            ],
        ], $collector->changes($obj));
    }

    public function testShouldNotTrackSetValueAndUnsetLater()
    {
        $obj = $this->createPersistedObject();

        $collector = new ChangesCollector();
        $collector->register($obj);

        set_value($obj, 'aKey', 'aVal');
        set_value($obj, 'aKey', null);

        self::assertEquals([], $collector->changes($obj));
    }

    public function testShouldTrackUnsetValue()
    {
        $obj = $this->createPersistedObject(['aKey' => 'aVal']);
        $collector = new ChangesCollector();
        $collector->register($obj);

        set_value($obj, 'aKey', null);

        self::assertEquals([
            '$unset' => [
                'aKey' => '',
            ]
        ], $collector->changes($obj));
    }

    public function testShouldTrackChangedValue()
    {
        $obj = $this->createPersistedObject(['aKey' => 'aVal']);

        $collector = new ChangesCollector();
        $collector->register($obj);

        set_value($obj, 'aKey', 'aNewVal');

        self::assertEquals([
            '$set' => [
                'aKey' => 'aNewVal',
            ],
        ], $collector->changes($obj));
    }

    public function testShouldTrackStringValueChangedToArrayValue()
    {
        $obj = $this->createPersistedObject(['aKey' => 'aVal']);

        $collector = new ChangesCollector();
        $collector->register($obj);

        set_value($obj, 'aKey.fooKey', 'aFooVal');
        set_value($obj, 'aKey.barKey', 'aBarVal');

        self::assertEquals([
            '$set' => [
                'aKey' => [
                    'fooKey' => 'aFooVal',
                    'barKey' => 'aBarVal',
                ],
            ],
        ], $collector->changes($obj));
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
        $collector->register($obj);

        set_value($obj, 'aKey', 'aVal');

        self::assertEquals([
            '$set' => [
                'aKey' => 'aVal',
            ],
        ], $collector->changes($obj));
    }

    public function testShouldFoo()
    {
        $obj = $this->createPersistedObject([
            'aKey' => 'aVal',
        ]);

        $collector = new ChangesCollector();
        $collector->register($obj);

        set_value($obj, 'aKey', null);
        set_value($obj, 'anotherKey', 'aVal');

        self::assertEquals([
            '$set' => [
                'anotherKey' => 'aVal',
            ],
            '$unset' => [
                'aKey' => '',
            ],
        ], $collector->changes($obj));
    }

    /**
     * @return Object
     */
    private function createPersistedObject(array $values = [])
    {
        $obj = new Object();
        set_values($obj, $values);
        set_object_id($obj, new ObjectID());

        return $obj;
    }
}