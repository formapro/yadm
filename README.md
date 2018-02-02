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

## Other examples

In [makasim/values](https://github.com/makasim/values) repo you can find examples on how to build simple objects, object trees, hydrate and retrive data from\to object.

## Benchmarks

* [Results](https://docs.google.com/spreadsheets/d/1CzVQuAz6cVAUKZyoQZyagQv48mgA3JAYJ2dNsoALV7A/edit#gid=0)
* [Code](https://github.com/makasim/yadm-benchmark)

## License

MIT
