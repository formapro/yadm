<?php
namespace Formapro\Yadm;

use MongoDB\BSON\Binary;

class Uuid
{
    private $binary;
    
    public function __construct($data)
    {
        if (is_string($data) && false !== strpos($data, '-')) {
            $bytes = \Ramsey\Uuid\Uuid::fromString($data)->getBytes();
        } elseif ($data instanceof Binary) {
            $bytes = $data->getData();
        } else {
            $bytes = $data;
        }

        $this->binary = new Binary($bytes, Binary::TYPE_UUID);
    }
    
    public function getBinary(): Binary
    {
        return $this->binary;
    }

    public function getData()
    {
        return $this->binary->getData();
    }

    public function getType()
    {
        return $this->binary->getType();
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
