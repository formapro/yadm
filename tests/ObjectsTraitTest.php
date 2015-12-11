<?php
namespace Makasim\Yadm\Tests;

use Makasim\Yadm\ObjectsTrait;
use Makasim\Yadm\ValuesTrait;

class ObjectsTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldResetObjectIfValuesSetAgain()
    {
        $subObj = new SubObjectTest();
        $subObj->setValue('aSubName', 'aSubKey', 'aFooVal');

        $obj = new ObjectTest();
        $obj->setObject('aName', 'aKey', $subObj);

        $this->assertAttributeNotEmpty('values', $obj);
        $this->assertAttributeNotEmpty('objects', $obj);

        $values = [];
        \Makasim\Yadm\set_values($obj, $values);

        $this->assertAttributeEmpty('values', $obj);
        $this->assertAttributeEmpty('objects', $obj);
    }

    public function testShouldAllowGetPreviouslySetObject()
    {
        $subObj = new SubObjectTest();
        $subObj->setValue('aSubName', 'aSubKey', 'aFooVal');

        $obj = new ObjectTest();
        $obj->setObject('aName', 'aKey', $subObj);

        $this->assertSame($subObj, $obj->getObject('aName', 'aKey', SubObjectTest::class));

        $this->assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObj));
    }

    public function testShouldCreateObjectOnGet()
    {
        $obj = new ObjectTest();

        $values = ['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]];
        \Makasim\Yadm\set_values($obj, $values);

        $subObj = $obj->getObject('aName', 'aKey', SubObjectTest::class);
        $this->assertInstanceOf(SubObjectTest::class, $subObj);

        $this->assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObj));
    }

    public function testShouldReturnNullIfValueNotSet()
    {
        $obj = new ObjectTest();

        $this->assertNull($obj->getObject('aName', 'aKey', SubObjectTest::class));
    }

    public function testShouldChangesInSubObjReflectedInObjValues()
    {
        $subObj = new SubObjectTest();
        $subObj->setValue('aSubName', 'aSubKey', 'aFooVal');

        $obj = new ObjectTest();
        $obj->setObject('aName', 'aKey', $subObj);

        $this->assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObj));

        $subObj->setValue('aSubName', 'aSubKey', 'aBarVal');

        $this->assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aBarVal']]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObj));
    }

    public function testShouldChangesInSubSubObjReflectedInObjValues()
    {
        $subSubObj = new SubObjectTest();
        $subSubObj->setValue('aSubSubName', 'aSubSubKey', 'aFooVal');

        $subObj = new ObjectTest();
        $subObj->setObject('aSubName', 'aSubKey', $subSubObj);

        $obj = new ObjectTest();
        $obj->setObject('aName', 'aKey', $subObj);

        $this->assertSame(['aName' => ['aKey' => [
            'aSubName' => [
                'aSubKey' => ['aSubSubName' => ['aSubSubKey' => 'aFooVal']],
            ], ]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubSubName' => ['aSubSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subSubObj));

        $subSubObj->setValue('aSubSubName', 'aSubSubKey', 'aBarVal');

        $this->assertSame(['aName' => ['aKey' => [
            'aSubName' => [
                'aSubKey' => ['aSubSubName' => ['aSubSubKey' => 'aBarVal']],
            ], ]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubSubName' => ['aSubSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subSubObj));
    }

    public function testShouldNotChangesInSubObjReflectedInObjValuesIfUnset()
    {
        $subObj = new SubObjectTest();
        $subObj->setValue('aSubName', 'aSubKey', 'aFooVal');

        $obj = new ObjectTest();
        $obj->setObject('aName', 'aKey', $subObj);

        $this->assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObj));

        $obj->setObject('aName', 'aKey', null);

        $this->assertSame(['aName' => []], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObj));

        $subObj->setValue('aSubName', 'aSubKey', 'aBarVal');
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObj));
    }

    public function testShouldAddSubObjValuesToObjChangedValues()
    {
        $subObj = new SubObjectTest();
        $subObj->setValue('aSubName', 'aSubKey', 'aFooVal');

        $obj = new ObjectTest();
        $obj->setObject('aName', 'aKey', $subObj);

        $this->assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldUnsetSubObjIfSameValueChangedAfterSubObjSet()
    {
        $subObj = new SubObjectTest();
        $subObj->setValue('aSubName', 'aSubKey', 'aFooVal');

        $obj = new ObjectTest();
        $obj->setObject('aName', 'aKey', $subObj);

        $this->assertAttributeSame(['aName' => ['aKey' => $subObj]], 'objects', $obj);

        $obj->setValue('aName', 'aKey', 'aFooVal');

        $this->assertAttributeEquals(['aName' => []], 'objects', $obj);
    }

    public function testShouldAllowDefineClosureAsClass()
    {
        $subObjValues = ['aSubName' => ['aSubKey' => 'aFooVal']];

        $expectedSubClass = $this->getMockClass(SubObjectTest::class);

        $obj = new ObjectTest();

        $values = ['aName' => ['aKey' => $subObjValues]];
        \Makasim\Yadm\set_values($obj, $values);

        $subObj = $obj->getObject('aName', 'aKey', function ($actualSubObjValues) use ($subObjValues, $expectedSubClass) {
            $this->assertSame($subObjValues, $actualSubObjValues);

            return $expectedSubClass;
        });

        $this->assertInstanceOf($expectedSubClass, $subObj);
    }

    public function testShouldAllowGetPreviouslySetObjects()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj = new ObjectTest();
        $obj->setObjects('aName', 'aKey', [$subObjFoo, $subObjBar]);

        $this->assertSame([$subObjFoo, $subObjBar], $obj->getObjects('aName', 'aKey', SubObjectTest::class));

        $this->assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjBar));
    }

    public function testShouldCreateObjectsOnGet()
    {
        $values = ['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]];

        $obj = new ObjectTest();
        \Makasim\Yadm\set_values($obj, $values);

        $subObjs = $obj->getObjects('aName', 'aKey', SubObjectTest::class);
        $this->assertInternalType('array', $subObjs);
        $this->assertCount(2, $subObjs);
        $this->assertContainsOnlyInstancesOf(SubObjectTest::class, $subObjs);

        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjs[0]));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjs[1]));
    }

    public function testShouldAllowAddObjectToCollection()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj = new ObjectTest();
        $obj->addObject('aName', 'aKey', $subObjFoo);
        $obj->addObject('aName', 'aKey', $subObjBar);

        $this->assertSame([$subObjFoo, $subObjBar], $obj->getObjects('aName', 'aKey', SubObjectTest::class));

        $this->assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], \Makasim\Yadm\get_values($obj));

        $this->assertAttributeSame(['aName' => ['aKey' => [$subObjFoo, $subObjBar]]], 'objects', $obj);

        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjBar));
    }

    public function testShouldAllowGetObjectsEitherSetAsValuesAndAddObject()
    {
        $values = ['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
        ]]];

        $obj = new ObjectTest();
        \Makasim\Yadm\set_values($obj, $values);

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj->addObject('aName', 'aKey', $subObjBar);

        $subObjs = $obj->getObjects('aName', 'aKey', SubObjectTest::class);

        $this->assertCount(2, $subObjs);

        $this->assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], \Makasim\Yadm\get_values($obj));

        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjs[0]));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjs[1]));
    }

    public function testShouldUpdateChangedValuesWhenObjectsSet()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj = new ObjectTest();

        $this->assertAttributeEmpty('changedValues', $obj);

        $obj->setObjects('aName', 'aKey', [$subObjFoo, $subObjBar]);

        $this->assertAttributeEquals(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], 'changedValues', $obj);
    }

    public function testShouldUpdatedChangedValuesWhenObjectAdded()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj = new ObjectTest();

        $this->assertAttributeEmpty('changedValues', $obj);

        $obj->addObject('aName', 'aKey', $subObjFoo);
        $obj->addObject('aName', 'aKey', $subObjBar);

        $this->assertSame([$subObjFoo, $subObjBar], $obj->getObjects('aName', 'aKey', SubObjectTest::class));

        $this->assertAttributeEquals(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], 'changedValues', $obj);
    }

    public function testShouldAllowUnsetObjects()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj = new ObjectTest();
        $obj->setObjects('aName', 'aKey', [$subObjFoo, $subObjBar]);

        $this->assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], \Makasim\Yadm\get_values($obj));

        $this->assertAttributeSame(['aName' => ['aKey' => [$subObjFoo, $subObjBar]]], 'objects', $obj);

        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjBar));

        $obj->setObjects('aName', 'aKey', null);

        $this->assertSame(['aName' => []], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjBar));
    }

    public function testShouldAllowResetObjects()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj = new ObjectTest();
        $obj->setObjects('aName', 'aKey', [$subObjFoo, $subObjBar]);

        $this->assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], \Makasim\Yadm\get_values($obj));

        $this->assertAttributeSame(['aName' => ['aKey' => [$subObjFoo, $subObjBar]]], 'objects', $obj);

        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjBar));

        $obj->setObjects('aName', 'aKey', []);

        $this->assertAttributeSame(['aName' => ['aKey' => []]], 'objects', $obj);

        $this->assertSame(['aName' => ['aKey' => []]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjBar));
    }

    public function testShouldReflectChangesDoneInSubObject()
    {
        $values = [
            'aName' => [
                'aKey' => [
                    'aSubName' => ['aSubKey' => 'aFooVal'],
                ],
            ],
        ];

        $obj = new ObjectTest();
        \Makasim\Yadm\set_values($obj, $values);

        //guard
        $this->assertEmpty(\Makasim\Yadm\get_changed_values($obj));

        $subObj = $obj->getObject('aName', 'aKey', SubObjectTest::class);

        $subObj->setValue('aSubName', 'aSubKey', 'aBarVal');

        $this->assertNotEmpty(\Makasim\Yadm\get_changed_values($subObj));
        $this->assertNotEmpty(\Makasim\Yadm\get_changed_values($obj));

        $this->assertEquals([
            'aName' => [
                'aKey' => [
                    'aSubName' => ['aSubKey' => 'aBarVal'],
                ],
            ],
        ], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldReflectChangesDoneInSubObjectFromCollection()
    {
        $values = [
            'aName' => [
                'aKey' => [
                    ['aSubName' => ['aSubKey' => 'aFooVal']],
                    ['aSubName' => ['aSubKey' => 'aBarVal']],
                ],
            ],
        ];

        $obj = new ObjectTest();
        \Makasim\Yadm\set_values($obj, $values);

        //guard
        $this->assertEmpty(\Makasim\Yadm\get_changed_values($obj));

        $subObjs = $obj->getObjects('aName', 'aKey', SubObjectTest::class);

        $subObjs[0]->setValue('aSubName', 'aSubKey', 'aBarVal');

        $this->assertNotEmpty(\Makasim\Yadm\get_changed_values($subObjs[0]));
        $this->assertNotEmpty(\Makasim\Yadm\get_changed_values($obj));

        $this->assertEquals([
            'aName' => [
                'aKey' => [
                    ['aSubName' => ['aSubKey' => 'aBarVal']],
                ],
            ],
        ], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldReflectChangesDoneWhenSubObjectUnset()
    {
        $values = $arr = [
            'aName' => [
                'aKey' => [
                    'aSubName' => ['aSubKey' => 'aFooVal'],
                ],
            ],
        ];

        $obj = new ObjectTest();
        \Makasim\Yadm\set_values($obj, $values);

        //guard
        $this->assertEmpty(\Makasim\Yadm\get_changed_values($obj));

        $obj->setObject('aName', 'aKey', null);

        $this->assertNotEmpty(\Makasim\Yadm\get_changed_values($obj));

        $this->assertEquals(['aName' => ['aKey' => null]], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldNotReflectChangesIfObjectWasCloned()
    {
        $values = [
            'aName' => [
                'aKey' => [
                    'aSubName' => ['aSubKey' => 'aFooVal'],
                ],
            ],
        ];

        $obj = new ObjectTest();
        \Makasim\Yadm\set_values($obj, $values);

        //guard
        $this->assertEmpty(\Makasim\Yadm\get_changed_values($obj));

        /** @var SubObjectTest $subObj */
        $subObj = $obj->getObject('aName', 'aKey', SubObjectTest::class);

        //guard
        $this->assertInstanceOf(SubObjectTest::class, $subObj);

        $clonedSubObj = \Makasim\Yadm\clone_object($subObj);
        $clonedSubObj->setSelfValue('aSubKeyFoo', 'aBarVal');

        $this->assertEquals([
            'aName' => [
                'aKey' => [
                    'aSubName' => ['aSubKey' => 'aFooVal'],
                ],
            ],
        ], \Makasim\Yadm\get_values($obj));
    }

    public function testShouldAllowSetSelfObjectAndGetPreviouslySet()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $obj = new ObjectTest();
        $obj->setSelfObject('aKey', $subObjFoo);

        $this->assertSame($subObjFoo, $obj->getSelfObject('aKey', ObjectTest::class));
        $this->assertSame(['self' => ['aKey' =>
            ['aSubName' => ['aSubKey' => 'aFooVal']],
        ]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
    }

    public function testShouldAllowSetSelfObjectsAndGetPreviouslySet()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj = new ObjectTest();
        $obj->setSelfObjects('aKey', [$subObjFoo, $subObjBar]);

        $this->assertSame([$subObjFoo, $subObjBar], $obj->getSelfObjects('aKey', SubObjectTest::class));

        $this->assertSame(['self' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjBar));
    }

    public function testShouldAllowAddSelfObjectsAndGetPreviouslySet()
    {
        $subObjFoo = new SubObjectTest();
        $subObjFoo->setValue('aSubName', 'aSubKey', 'aFooVal');

        $subObjBar = new SubObjectTest();
        $subObjBar->setValue('aSubName', 'aSubKey', 'aBarVal');

        $obj = new ObjectTest();
        $obj->addSelfObject('aKey', $subObjFoo);
        $obj->addSelfObject('aKey', $subObjBar);

        $this->assertSame([$subObjFoo, $subObjBar], $obj->getSelfObjects('aKey', SubObjectTest::class));

        $this->assertSame(['self' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], \Makasim\Yadm\get_values($subObjFoo));
        $this->assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], \Makasim\Yadm\get_values($subObjBar));
    }
}

class ObjectTest
{
    use ValuesTrait {
        setSelfValue as public;
        getSelfValue as public;
        getValue as public;
        setValue as public;
        addValue as public;
    }

    use ObjectsTrait {
        setSelfObject as public;
        getSelfObject as public;
        setSelfObjects as public;
        getSelfObjects as public;
        addSelfObject as public;

        setObject as public;
        getObject as public;
        setObjects as public;
        getObjects as public;
        addObject as public;
    }
}

class SubObjectTest
{
    use ValuesTrait {
        setSelfValue as public;
        getSelfValue as public;
        getValue as public;
        setValue as public;
        addValue as public;
    }
}