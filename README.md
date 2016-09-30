# Yadm is a MongoDB ODM.

[![Build Status](https://travis-ci.org/makasim/values.png?branch=master)](https://travis-ci.org/makasim/yadm)

The schema less ODM. It gives you the fastest hydration and persistent. The easiest solution for building aggregation roots or objects trees. It is a good choice for [aggregation root](http://martinfowler.com/bliki/DDD_Aggregate.html) models because it super easy t build object trees. [bounded context](http://martinfowler.com/bliki/BoundedContext.html) could be easily build too, because that easy to copy object data from one model to another. 

This approach tries to gather the best from arrays and objects.

## Benchmarks

```
Bench: Mongodb create 10000 models            6.00 MiB    1500 ms
Bench: Mongodb find 10000 models              6.00 MiB    12 ms

Bench: Yadm create 10000 models           	  6.00 MiB    1563 ms
Bench: Yadm find 10000 models             	  6.00 MiB    95 ms

Bench: DoctrineORM create 100 models      	  6.00 MiB    2412 ms
Bench: DoctrineORM create 10000 models. SF    50.00 MiB   1657 ms
Bench: DoctrineORM find 10000 models.     	  52.00 MiB   1732 ms

Bench: DoctrineODM create 100 models      	  4.25 MiB    339 ms
Bench: DoctrineODM create 10000 models. SF    84.25 MiB   2386 ms
Bench: DoctrineODM find 10000 models.         44.00 MiB   560 ms
```

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
        $this->setObject('price', $price);
    }
}
```

## Mongodb storage

```php
<?php
namespace Acme;

$collection = (new Client())->selectCollection('acme_demo', 'orders');
$hydrator = new Hydrator(Order::class);
$storage = new MongodbStorage($collection, $hydrator);

$order = $storage->create();
$order->setNumber(1234);

$storage->insert($order);
```

## License

MIT
