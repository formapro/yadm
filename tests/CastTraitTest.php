<?php
namespace Makasim\Yadm\Tests;

use Makasim\Yadm\CastTrait;
use Makasim\Yadm\ValuesTrait;

class CastTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldAllowCastToTypeOnGet()
    {
        $obj = new CastTest();
        $obj->setValue('aNamespace.aKey', '123');

        $this->assertSame(123, $obj->getValue('aNamespace.aKey', null, 'int'));
    }

    public function testShouldAllowSetDateTimeValueAndGetPreviouslySet()
    {
        $now = new \DateTime('now');
        $timestamp = $now->format('U');

        $obj = new CastTest();
        $obj->setValue('aNamespace.aKey', $now);

        $actualDate = $obj->getValue('aNamespace.aKey', null, \DateTime::class);
        $this->assertInstanceOf(\DateTime::class, $actualDate);
        $this->assertEquals($timestamp, $actualDate->format('U'));
    }

    public function testShouldAllowSetDateTimeValueASISOAndGetPreviouslySet()
    {
        $now = new \DateTime('now');
        $timestamp = $now->format('U');
        $iso = $now->format(DATE_ISO8601);

        $obj = new CastTest();
        $obj->setValue('aNamespace.aKey', $now->format(DATE_ISO8601));

        $this->assertSame($iso, $obj->getValue('aNamespace.aKey'));

        $actualDate = $obj->getValue('aNamespace.aKey', null, \DateTime::class);
        $this->assertInstanceOf(\DateTime::class, $actualDate);
        $this->assertEquals($timestamp, $actualDate->format('U'));
    }

    public function testShouldAllowSetDateTimeValueASTimestampAndGetPreviouslySet()
    {
        $now = new \DateTime('now');
        $timestamp = $now->format('U');

        $obj = new CastTest();
        $obj->setValue('aNamespace.aKey', $now->format('U'));

        $this->assertSame($timestamp, $obj->getValue('aNamespace.aKey'));

        $actualDate = $obj->getValue('aNamespace.aKey', null, \DateTime::class);
        $this->assertInstanceOf(\DateTime::class, $actualDate);
        $this->assertEquals($timestamp, $actualDate->format('U'));
    }

    public function testShouldAllowAddDateValueToArrayAndConvertToISO()
    {
        $now = new \DateTime('now');
        $timestamp = (int) $now->format('U');
        $iso = $now->format(DATE_ISO8601);

        $obj = new CastTest();
        $obj->addValue('aNamespace.aKey', $now);

        $this->assertSame([['unix' => $timestamp, 'iso' => $iso]], $obj->getValue('aNamespace.aKey'));
        $this->assertSame(['aNamespace' => ['aKey' => [['unix' => $timestamp, 'iso' => $iso]]]], \Makasim\Yadm\get_object_values($obj));
        $this->assertSame(['aNamespace' => ['aKey' => [['unix' => $timestamp, 'iso' => $iso]]]], \Makasim\Yadm\get_object_changed_values($obj));
    }

    public function testShouldAllowAddDateIntervalValueToArray()
    {
        $interval = new \DateInterval('P7D');

        $obj = new CastTest();
        $obj->addValue('aNamespace.aKey', $interval);

        $this->assertSame([[
            'interval' => 'P0Y0M7DT00H00M00S',
            'days' => false,
            'y' => 0,
            'm' => 0,
            'd' => 7,
            'h' => 0,
            'i' => 0,
            's' => 0,
        ]], $obj->getValue('aNamespace.aKey'));
    }

    public function testShouldAllowSetDateIntervalValueToArray()
    {
        $interval = new \DateInterval('P7D');

        $obj = new CastTest();
        $obj->setValue('aNamespace.aKey', $interval);

        $this->assertSame([
            'interval' => 'P0Y0M7DT00H00M00S',
            'days' => false,
            'y' => 0,
            'm' => 0,
            'd' => 7,
            'h' => 0,
            'i' => 0,
            's' => 0,
        ], $obj->getValue('aNamespace.aKey'));
    }

    public function testShouldAllowSetDateTimeValueASStringAndGetPreviouslySet()
    {
        $obj = new CastTest();
        $obj->setValue('aNamespace.aKey', 'P7D');


        $interval = $obj->getValue('aNamespace.aKey', null, \DateInterval::class);
        $this->assertInstanceOf(\DateInterval::class, $interval);
        $this->assertEquals('P0Y0M7DT00H00M00S', $interval->format('P%yY%mM%dDT%HH%IM%SS'));
    }

    public function testShouldAllowSetDateTimeValueASArrayAndGetPreviouslySet()
    {
        $obj = new CastTest();
        $obj->setValue('aNamespace.aKey', ['interval' => 'P7D']);


        $interval = $obj->getValue('aNamespace.aKey', null, \DateInterval::class);
        $this->assertInstanceOf(\DateInterval::class, $interval);
        $this->assertEquals('P0Y0M7DT00H00M00S', $interval->format('P%yY%mM%dDT%HH%IM%SS'));
    }
}

class CastTest
{
    use ValuesTrait {
        getValue as public;
        setValue as public;
        addValue as public;
    }
    use CastTrait;
}