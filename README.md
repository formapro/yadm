# Yadm is a MongoDB ODM.

[![Build Status](https://travis-ci.org/makasim/values.png?branch=master)](https://travis-ci.org/makasim/yadm)

The schema less ODM. It gives you the fastest hydration and persistent. The easiest solution for building aggregation roots or objects trees. It is a good choice for [aggregation root](http://martinfowler.com/bliki/DDD_Aggregate.html) models because it super easy t build object trees. [bounded context](http://martinfowler.com/bliki/BoundedContext.html) could be easily build too, because that easy to copy object data from one model to another. 

This approach tries to gather the best from arrays and objects.

## Fast persistence.

To get object state you have to read an array from protected `values` property. You can add a public getter for it or use reflection. There is a handy method for it `get_object_values`.
Once you get the array you can easily persist it.

```php
<?php
namespace Acme;

$price = new Price();
$price->setAmount(100);
$price->setCurrency('USD');

$order = new Order;
$order->setNumber('theNumber');
$order->setPrice($price);

$array = \Makasim\Yadm\get_object_values($order);
// [
//     'number' => 'theNumber'
//     'price' => ['amount' => 100, 'currency' => 'USD'],
// ]
```

## Fast hydration

To set object state you have to write to protected `values` property. 
You can add a public getter for it or use reflection.
There is a handy method for it `set_object_values`.
Once you set the array you can use the model.

```php
<?php
namespace Acme;

$order = new Order;
\Makasim\Yadm\set_object_values($order, [
    'number' => 'theNumber',
    'price' => ['amount' => 100, 'currency' => 'USD'],
]);

$order->getNumber(); // theNumber
$order->getPrice()->getAmount(); // 100
$order->getPrice()->getCurrency(); // USD
```

## Models

You store everything in `values` property as array.

```php
<?php
namespace Acme;

use Makasim\Yadm\ValuesTrait;
use Makasim\Yadm\ObjectsTrait;

class Price
{
    use ValuesTrait;

    public function getAmount()
    {
        return $this->getValue('amount');
    }

    public function setAmount($amount)
    {
        $this->setValue('amount', $amount);
    }

    public function getCurrency()
    {
        return $this->getValue('currency');
    }

    public function setCurrency($currency)
    {
        $this->setValue('currency', $currency);
    }
}

class Order
{
    use ValuesTrait;
    use ObjectsTrait;

    public function getNumber()
    {
        return $this->getValue('number');
    }

    public function setNumber($number)
    {
        $this->setValue('number', $number);
    }

    public function getPrice()
    {
        return $this->getObject('price', Price::class);
    }

    public function setPrice(Price $price = null)
    {
        $this->setValue('price', $price);
    }
}
```

## Mongodb storage

```php
<?php
namespace Acme;

$collection = (new Client())->selectCollection('acme_demo', 'orders');
$hydrator = new Hydrator(Order::class);
$storage = new Storage($collection, $hydrator)

$order = $storage->create();
$order->setNumber(1234);

$storage->insert($order);
```

## License

MIT
