<?php
namespace Formapro\Yadm;

use function Formapro\Values\array_set;
use Formapro\Yadm\Type\Type;

class ConvertValues
{
    /**
     * @var Type[]
     */
    private $types;

    public function __construct(array $types)
    {
        $this->types = $types;
    }

    public function convertToMongoValues(array $values, array $originalValues): array
    {
        $updatedValues = $values;
        foreach ($this->types as $key => $type) {
            array_set($key, $type->toMongoValue($key, $values, $originalValues), $updatedValues);
        }

        return $updatedValues;
    }

    public function convertToPHPValues(array $originalValues): array
    {
        $values = $originalValues;
        foreach ($this->types as $key => $type) {
            array_set($key, $type->toPHPValue($key, $originalValues), $values);
        }

        return $values;
    }
}