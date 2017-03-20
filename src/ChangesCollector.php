<?php
namespace Makasim\Yadm;

use mikemccabe\JsonPatch\JsonPatch;

class ChangesCollector
{
    public function register($object)
    {
        (function() {
            $this->originalValues = $this->values;
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

            unset($values['_id']);

            return ['$set' => $values];
        })->call($object);
    }
}
