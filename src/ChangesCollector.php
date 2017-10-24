<?php
namespace Makasim\Yadm;

use function Makasim\Values\get_values;
use mikemccabe\JsonPatch\JsonPatch;

class ChangesCollector
{
    public function register($object)
    {
        (function() {
            $this->originalValues = get_values($this, true);
        })->call($object);
    }

    public function changes($object)
    {
        return (function() {
            $values = $this->values;

            if (property_exists($this, 'originalValues')) {
                $originalValues = $this->originalValues;

                $diff = JsonPatch::diff($originalValues, $values);

                return Converter::convertJsonPatchToMongoUpdate($diff);
            }

            return ['$set' => $values];
        })->call($object);
    }
}
