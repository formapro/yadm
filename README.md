# Yadm is the fastest MongoDB ODM.

[![Build Status](https://travis-ci.org/formapro/values.png?branch=master)](https://travis-ci.org/formapro/yadm)

The schema less ODM. It gives you the fastest hydration and persistent. Based on [formapro/values](https://github.com/formapro/values) lib.

## Install

```bash
$ composer require formapro/yadm "mikemccabe/json-patch-php:dev-master as 0.1.1"
```

## Storage example

Let's say we have an order model:

```php
<?php

namespace Acme;

use function Formapro\Values\set_value;
use function Formapro\Values\get_value;

class Price
{
    private $values = [];
    
    public function setCurrency(string $value): void
    {
        set_value($this, 'currency', $value);
    }
    
    public function getCurrency(): string 
    {
        return get_value($this, 'currency');
    }
    
    public function setAmount(int $value): void
    {
        set_value($this, 'amount', $value);
    }
    
    public function getAmount(): string 
    {
        return get_value($this, 'amount');
    }
}
```

```php
<?php
namespace Acme;

use function Formapro\Values\set_value;
use function Formapro\Values\get_value;
use function Formapro\Values\set_object;
use function Formapro\Values\get_object;

class Order
{
    private $values = [];
    
    public function setNumber(string $number): void
    {
        set_value($this, 'number', $number);
    }
    
    public function getNumber(): string 
    {
        return get_value($this, 'number');
    }
    
    public function setPrice(Price $price): void
    {
        set_object($this, 'price', $price);
    }
    
    public function getPrice(): Price
    {
        return get_object($this, 'price', Price::class);
    }
}
```


```php
<?php
namespace Acme;

use MongoDB\Client;
use Formapro\Yadm\Hydrator;
use Formapro\Yadm\Storage;

$collection = (new Client())->selectCollection('acme_demo', 'orders');
$hydrator = new Hydrator(Order::class);
$storage = new Storage($collection, $hydrator);

$price = new Price();
$price->setAmount(123); # 1.23 USD
$price->setCurrency('USD');

$order = new Order();
$order->setNumber(1234);
$order->setPrice($price);

$storage->insert($order);

$foundOrder = $storage->find(['_id' => get_object_id($order)]);
$foundOrder->setNumber(4321);
$storage->update($foundOrder);

$storage->delete($foundOrder);
```

## MongoDB special types usage


```php
<?php
namespace Acme;

use MongoDB\Client;
use Formapro\Yadm\Hydrator;
use Formapro\Yadm\Storage;
use Formapro\Yadm\ConvertValues;
use Formapro\Yadm\Type\UuidType;
use Formapro\Yadm\Type\UTCDatetimeType;
use Formapro\Yadm\Uuid;
use function Formapro\Values\set_value;
use function Formapro\Values\get_value;

$convertValues = new ConvertValues([
    'id' => new UuidType(),
    'createdAt' => new UTCDatetimeType(),
]);

$collection = (new Client())->selectCollection('acme_demo', 'orders');
$hydrator = new Hydrator(Order::class);
$storage = new Storage($collection, $hydrator, null, null, $convertValues);
 

$order = new Order();
set_value($order, 'id', Uuid::generate()->toString());
set_value($order, 'createdAt', (new \DateTime())->format('U'));

$storage->insert($order);

$id = get_value($order, 'id');

// find by uuid
$anotherOrder = $storage->findOne(['id' => new Uuid($id)]);

// do not update id if not changed
$storage->update($anotherOrder);

// update on change
set_value($anotherOrder, 'id', Uuid::generate()->toString());
$storage->update($anotherOrder);
```

## Other examples

In [formapro/values](https://github.com/formapro/values) repo you can find examples on how to build simple objects, object trees, hydrate and retrive data from\to object.

## Benchmarks

* [Results](https://docs.google.com/spreadsheets/d/1CzVQuAz6cVAUKZyoQZyagQv48mgA3JAYJ2dNsoALV7A/edit#gid=0)
* [Code](https://github.com/makasim/yadm-benchmark)

## License

MIT
