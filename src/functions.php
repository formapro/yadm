<?php
namespace Makasim\Yadm;
use MongoDB\BSON\ObjectID;

/**
 * @param object $object
 * @param bool $orNull
 *
 * @return ObjectID|null
 */
function get_object_id($object, $orNull = false)
{
    return (function () use ($orNull) {
        $id = isset($this->_id) ? $this->_id : null;


        if (false == $id && false == $orNull) {
            throw new \LogicException('The object id is not set.');
        }

        return $id;
    })->call($object);
}

/**
 * @param object   $object
 * @param ObjectID $id
 */
function set_object_id($object, ObjectID $id)
{
    (function () use ($id) { $this->_id = $id; })->call($object);
}
