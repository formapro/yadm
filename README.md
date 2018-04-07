# Yadm is the fastest MongoDB ODM.

[![Build Status](https://travis-ci.org/makasim/values.png?branch=master)](https://travis-ci.org/makasim/yadm)

The schema less ODM. It gives you the fastest hydration and persistent. Based on [makasim/values](https://github.com/makasim/values) lib.

## Install

```bash
$ composer require makasim/yadm "mikemccabe/json-patch-php:dev-master as 0.1.1"
```

## Storage example

```php
<?php
namespace Acme;

use MongoDB\Client;
use Makasim\Yadm\Hydrator;
use Makasim\Yadm\Storage;

$collection = (new Client())->selectCollection('acme_demo', 'orders');
$hydrator = new Hydrator(Order::class);
$storage = new Storage($collection, $hydrator);

$order = new Order();
$order->setNumber(1234);

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
use MongoDB\BSON\Binary;
use Makasim\Yadm\Hydrator;
use Makasim\Yadm\Storage;
use Makasim\Yadm\ConvertValues;
use Makasim\Yadm\Type\UuidType;
use Makasim\Yadm\Type\UTCDatetimeType;
use Ramsey\Uuid\Uuid;
use function Makasim\Values\set_value;
use function Makasim\Values\get_value;

$convertValues = new ConvertValues([
    'id' => new UuidType(),
    'createdAt' => new UTCDatetimeType(),
]);

$collection = (new Client())->selectCollection('acme_demo', 'orders');
$hydrator = new Hydrator(Order::class);
$storage = new Storage($collection, $hydrator, null, null, $convertValues);
 

$order = new Order();
set_value($order, 'id', Uuid::uuid4()->toString());
set_value($order, 'createdAt', (new \DateTime())->format('U'));

$storage->insert($order);

$id = get_value($order, 'id');

// find by uuid
$anotherOrder = $storage->findOne(['id' => new Binary(Uuid::fromString($id)->getBytes(), Binary::TYPE_UUID)]);

// do not update id if not changed
$storage->update($anotherOrder);

// update on change
set_value($anotherOrder, 'id', Uuid::uuid4()->toString());
$storage->update($anotherOrder);
```

## Other examples

In [makasim/values](https://github.com/makasim/values) repo you can find examples on how to build simple objects, object trees, hydrate and retrive data from\to object.

## Benchmarks

* [Results](https://docs.google.com/spreadsheets/d/1CzVQuAz6cVAUKZyoQZyagQv48mgA3JAYJ2dNsoALV7A/edit#gid=0)
* [Code](https://github.com/makasim/yadm-benchmark)

## License

MIT
