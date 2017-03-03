<?php
namespace Makasim\Yadm\Tests\Model;

use Makasim\Values\ObjectsTrait;
use Makasim\Values\ValuesTrait;

class Object
{
    use ValuesTrait {
        getValue as public;
        setValue as public;
        addValue as public;
    }

    use ObjectsTrait {
        setObject as public;
        getObject as public;
        setObjects as public;
        getObjects as public;
        addObject as public;
    }
}