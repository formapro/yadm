<?php
namespace Makasim\Yadm;
use MongoDB\BSON\ObjectID;

/**
 * @param object $object
 *
 * @return string
 */
function get_object_id($object)
{
    return (function () {
        return (string) isset($this->values['_id']) ? $this->values['_id'] : null;
    })->call($object);
}

/**
 * @param object          $object
 * @param ObjectID|string $objectId
 */
function set_object_id($object, $objectId)
{
    return (function () use ($objectId) {
        $this->values['_id'] = (string) $objectId;
    })->call($object);
}