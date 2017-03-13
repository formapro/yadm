<?php
namespace Makasim\Yadm;

use function Makasim\Values\get_values;
use mikemccabe\JsonPatch\JsonPatch;

class ChangesCollector
{
    private $originalValues;

    public function register($object)
    {
        if ($id = get_object_id($object)) {
            $this->originalValues[$id] = get_values($object);
        }
    }

    public function unregister($object)
    {
        if ($id = get_object_id($object)) {
            unset($this->originalValues[$id]);
        }
    }

    public function changes($object)
    {
        if (false == $id = get_object_id($object)) {
            throw new \LogicException(sprintf('Object does not have an id set.'));
        }

        if (false == array_key_exists($id, $this->originalValues)) {
            throw new \LogicException(sprintf('Changes has not been collected. The object with id "%s" original data is missing.'));
        }

        $diff = JsonPatch::diff($this->originalValues[$id], get_values($object));

        return Converter::convertJsonPatchToMongoUpdate($diff);
    }
}
