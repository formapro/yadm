<?php
namespace Makasim\Yadm\Tests;

use Makasim\Yadm\ValuesTrait;

class ValuesTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldAllowSetValuesAndGetPreviouslySet()
    {
        $values = ['foo' => 'fooVal', 'bar' => ['bar1' => 'bar1Val', 'bar2' => 'bar2Val']];

        $obj = new ValueTest();

        \Makasim\Yadm\set_values($obj, $values);

        $this->assertSame($values, \Makasim\Yadm\get_values($obj));
        $this->assertSame([], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldAllowSetValueAndGetPreviouslySet()
    {
        $obj = new ValueTest();
        $obj->setValue('aNamespace', 'aKey', 'aVal');

        $this->assertSame('aVal', $obj->getValue('aNamespace', 'aKey'));
        $this->assertSame(['aNamespace' => ['aKey' => 'aVal']], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aNamespace' => ['aKey' => 'aVal']], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldAllowGetDefaultValueIfNotSet()
    {
        $obj = new ValueTest();

        $this->assertSame('aDefaultVal', $obj->getValue('aNamespace', 'aKey', 'aDefaultVal'));

        $obj->setValue('aNamespace', 'aKey', 'aVal');

        $this->assertSame('aVal', $obj->getValue('aNamespace', 'aKey', 'aDefaultVal'));
    }

    public function testShouldAllowSetSelfValueAndGetPreviouslySet()
    {
        $obj = new ValueTest();
        $obj->setSelfValue('aKey', 'aVal');

        $this->assertSame('aVal', $obj->getSelfValue('aKey'));
        $this->assertSame(['self' => ['aKey' => 'aVal']], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['self' => ['aKey' => 'aVal']], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldResetChangedValuesWhenValuesSet()
    {
        $obj = new ValueTest();
        $obj->setValue('aNamespace', 'aKey', 'aVal');

        $this->assertSame(['aNamespace' => ['aKey' => 'aVal']], \Makasim\Yadm\get_values($obj));

        $values = ['bar' => 'barVal'];
        \Makasim\Yadm\set_values($obj, $values);
        $this->assertSame([], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldAllowSetDateTimeValueAndGetPreviouslySet()
    {
        $now = new \DateTime('now');
        $timestamp = $now->format('U');

        $obj = new ValueTest();
        $obj->setValue('aNamespace', 'aKey', $now);

        $actualDate = $obj->getValue('aNamespace', 'aKey', null, 'date');
        $this->assertInstanceOf(\DateTime::class, $actualDate);
        $this->assertEquals($timestamp, $actualDate->format('U'));
    }

    public function testShouldAllowSetDateTimeValueASISOAndGetPreviouslySet()
    {
        $now = new \DateTime('now');
        $timestamp = $now->format('U');
        $iso = $now->format(DATE_ISO8601);

        $obj = new ValueTest();
        $obj->setValue('aNamespace', 'aKey', $now->format(DATE_ISO8601));

        $this->assertSame($iso, $obj->getValue('aNamespace', 'aKey'));

        $actualDate = $obj->getValue('aNamespace', 'aKey', null, 'date');
        $this->assertInstanceOf(\DateTime::class, $actualDate);
        $this->assertEquals($timestamp, $actualDate->format('U'));
    }

    public function testShouldAllowSetDateTimeValueASTimestampAndGetPreviouslySet()
    {
        $now = new \DateTime('now');
        $timestamp = $now->format('U');

        $obj = new ValueTest();
        $obj->setValue('aNamespace', 'aKey', $now->format('U'));

        $this->assertSame($timestamp, $obj->getValue('aNamespace', 'aKey'));

        $actualDate = $obj->getValue('aNamespace', 'aKey', null, 'date');
        $this->assertInstanceOf(\DateTime::class, $actualDate);
        $this->assertEquals($timestamp, $actualDate->format('U'));
    }

    public function testShouldAllowCastToTypeOnGet()
    {
        $obj = new ValueTest();
        $obj->setValue('aNamespace', 'aKey', '123');

        $this->assertSame(123, $obj->getValue('aNamespace', 'aKey', null, 'int'));
    }

    public function testShouldAllowUnsetPreviouslySetValue()
    {
        $obj = new ValueTest();
        $obj->setValue('aName', 'aKey', 'aVal');

        $this->assertSame('aVal', $obj->getValue('aName', 'aKey'));
        $this->assertSame(['aName' => ['aKey' => 'aVal']], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aName' => ['aKey' => 'aVal']], \Makasim\Yadm\get_changed_values($obj));

        $obj->setValue('aName', 'aKey', null);

        $this->assertSame(null, $obj->getValue('aName', 'aKey'));
        $this->assertSame(['aName' => []], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aName' => ['aKey' => null]], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldAllowAddValueToEmptyArray()
    {
        $obj = new ValueTest();
        $obj->addValue('aNamespace', 'aKey', 'aVal');

        $this->assertSame(['aVal'], $obj->getValue('aNamespace', 'aKey'));
        $this->assertSame(['aNamespace' => ['aKey' => ['aVal']]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aNamespace' => ['aKey' => ['aVal']]], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldAllowAddDateValueToArrayAndConvertToISO()
    {
        $now = new \DateTime('now');
        $timestamp = (int) $now->format('U');
        $iso = $now->format(DATE_ISO8601);

        $obj = new ValueTest();
        $obj->addValue('aNamespace', 'aKey', $now);

        $this->assertSame([['unix' => $timestamp, 'iso' => $iso]], $obj->getValue('aNamespace', 'aKey'));
        $this->assertSame(['aNamespace' => ['aKey' => [['unix' => $timestamp, 'iso' => $iso]]]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aNamespace' => ['aKey' => [['unix' => $timestamp, 'iso' => $iso]]]], \Makasim\Yadm\get_changed_values($obj));
    }

    public function testShouldAllowAddValueToAlreadyArray()
    {
        $values = ['aNamespace' => ['aKey' => ['aVal']]];

        $obj = new ValueTest();
        \Makasim\Yadm\set_values($obj, $values);
        $obj->addValue('aNamespace', 'aKey', 'aVal');

        $this->assertSame(['aVal', 'aVal'], $obj->getValue('aNamespace', 'aKey'));
        $this->assertSame(['aNamespace' => ['aKey' => ['aVal', 'aVal']]], \Makasim\Yadm\get_values($obj));
        $this->assertSame(['aNamespace' => ['aKey' => ['aVal', 'aVal']]], \Makasim\Yadm\get_changed_values($obj));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot set value to aNamespace.aKey it is already set and not array
     */
    public function testThrowsIfAddValueToExistOneWhichNotArray()
    {
        $values = ['aNamespace' => ['aKey' => 'aVal']];

        $obj = new ValueTest();
        \Makasim\Yadm\set_values($obj, $values);

        $obj->addValue('aNamespace', 'aKey', 'aVal');
    }

    public function testShouldNotReflectChangesOnClonedObject()
    {
        $obj = new ValueTest();
        $obj->setValue('aNamespace', 'aKey', 'foo');

        $clonedObj = \Makasim\Yadm\clone_object($obj);
        $clonedObj->setValue('aNamespace', 'aKey', 'bar');

        $this->assertSame('foo', $obj->getValue('aNamespace', 'aKey'));
        $this->assertSame('bar', $clonedObj->getValue('aNamespace', 'aKey'));
    }
}

class ValueTest
{
    use ValuesTrait {
        setSelfValue as public;
        getSelfValue as public;
        getValue as public;
        setValue as public;
        addValue as public;
    }
}