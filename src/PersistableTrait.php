<?php
namespace Makasim\Yadm;

/**
 * @property array $values
 */
trait PersistableTrait
{
    public function bsonSerialize()
    {
        return $this->values;
    }

    public function bsonUnserialize(array $values = [])
    {
        $this->values = $values;
    }
}