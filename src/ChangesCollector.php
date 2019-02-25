<?php
namespace Formapro\Yadm;

use mikemccabe\JsonPatch\JsonPatch;

class ChangesCollector
{
    public function register($object, array $originalValues): void
    {
        (function() use ($originalValues) {
            $this->originalValues = $originalValues;
        })->call($object);
    }

    public function getOriginalValues($object): ?array
    {
        return (function() {
            return property_exists($this, 'originalValues') ? $this->originalValues : null;
        })->call($object);
    }

    public function changes(array $values, array $originalValues = null): array
    {
        if (null !== $originalValues) {
            $diff = JsonPatch::diff($originalValues, $values);

            return Converter::convertJsonPatchToMongoUpdate($diff, $values);
        }

        return ['$set' => $values];
    }
}
