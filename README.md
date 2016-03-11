# Yadm is a MongoDB ODM.

[![Build Status](https://travis-ci.org/makasim/values.png?branch=master)](https://travis-ci.org/makasim/yadm)

The schema less ODM. It gives you the fastest hydration and persistent. The easiest solution for building aggregation roots or objects trees. Good choice for aggregation root models.

This approach tries to gather the best from arrays and objects.

Model class and its storage:

```php
<?php

namespace Acme;

use Makasim\Yadm\ValuesTrait;
use Makasim\Yadm\ObjectsTrait;
use Makasim\Yadm\Storage;
use Makasim\Yadm\Hydrator;
use MongoDB\Client;
use MongoDB\BSON\ObjectID;

class Order
{
    use ValuesTrait;
    use ObjectsTrait; // If you are not going to use sub objects you can remove it.

    public function getId()
    {
        return \Makasim\Yadm\get_object_id($this);
    }

    public function getNumber()
    {
        return $this->getValue('number');
    }

    public function setNumber($number)
    {
        $this->setValue('number', $number);
    }
}

$collection = (new Client())->selectCollection('acme_demo', 'orders');
$hydrator = new Hydrator(Order::class);

$storage = new Storage($collection, $hydrator)
```

Insert a model:

```php
<?php

$order = $storage->create();

$order->setNumber(1234);

$storage->insert($order);
```

Update a model:

```php
<?php

$order = $storage->create();

$order->setNumber(1234);

$storage->insert($order);

$order->setNumber(5678);

$storage->update($order);
```

Find a model

```php
<?php

$order = $storage->create();

$order->setNumber(1234);

$storage->insert($order);

$storage->findOne(['_id' => new ObjectID($order->getId())]);
```

Hydrate a model:

```php
<?php

$orderValues = [/* an array previously stored somewhere*/];

// create new order
$order = new Order;
\Makasim\Yadm\set_values($order, $orderValues);

$number = $order->getNumber();
```

Set custom values:

```php
<?php

$order = new Order;

$order->setValue('subscription.id', 123);
$order->setValue('subscription.deliveryDate', '2015-10-10');
$order->setValue('fortnox.invoiceNumber', 543);
```

# Objects

Is a thin wrapper above values traits, which allows to build models tree, while still storing everything in the root.
For example we have an order and price where the order is the root and price is a tree leaf.

```php
<?php

namespace Acme;

class Order
{
    // ..

    public function getPrice()
    {
        return $this->getObject('price', Price::class);
    }

    public function setPrice(Price $price = null)
    {
        $this->setObject('price', $price);
    }
}

class Price
{
    use \Makasim\Yadm\ValuesTrait;

    public function getAmount()
    {
        return $this->getValue('amount', null, 'int');
    }

    public function setAmount($amount)
    {
        $this->setValue('amount', $amount);
    }
}
```

Insert order with sub objects:

```php
<?php

$price = new Price();
$price->setAmount(100);

$order = $storage->create();
$order->setPrice($price);

$storage->insert($order);
```
