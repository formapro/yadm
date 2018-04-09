<?php
namespace Makasim\Yadm;

use MongoDB\BSON\Binary;

class Uuid extends Binary
{
    public function __construct($data)
    {
        if (is_string($data) && false !== strpos($data, '-')) {
            $bytes = \Ramsey\Uuid\Uuid::fromString($data)->getBytes();
        } elseif ($data instanceof Binary) {
            $bytes = $data->getData();
        } else {
            $bytes = $data;
        }

        parent::__construct($bytes, Binary::TYPE_UUID);
    }

    public function toString(): string
    {
        return \Ramsey\Uuid\Uuid::fromBytes($this->getData())->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function generate(): self
    {
        return new static (\Ramsey\Uuid\Uuid::uuid4()->getBytes());
    }

}
