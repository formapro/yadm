<?php
namespace Makasim\Yadm;
use MongoDB\BSON\ObjectID;

function &get_value($key, $default, &$values)
{
    $keys = explode('.', $key);
    $keyExists = null;
    $value =& array_path_get($values, $keys, $keyExists);

    if ($keyExists) {
        return $value;
    } else {
        return $default;
    }
}

function set_value($key, $value, array &$values, array &$changedValues = null)
{
    $keys = explode('.', $key);

    if (null !== $value) {
        array_path_set($values, $keys, $value);
        
        if (null !== $changedValues) {
            array_path_set($changedValues, $keys, $value);
        }
    } else {
        $keyExists = null;
        array_path_unset($values, $keys, $keyExists);
        if ($keyExists && null !== $changedValues) {
            array_path_set($changedValues, $keys, null);
        }
    }
}

function has_value($key, array &$values)
{
    $keys = explode('.', $key);

    $keyExists = null;
    array_path_get($values, $keys, $keyExists);
    
    return $keyExists;
}

function unset_value($key, array &$values, array &$changedValues = null)
{
    $keys = explode('.', $key);
    
    $keyExists = null;
    array_path_unset($values, $keys, $keyExists);
    if ($keyExists && null !== $changedValues) {
        array_path_set($changedValues, $keys, null);
    }
}

/**
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Component%21Utility%21NestedArray.php/function/NestedArray%3A%3AsetValue/8
 */
function array_path_set(array &$array, array $keys, $value, $force = false) {
    $ref = &$array;
    foreach ($keys as $parent) {
        // PHP auto-creates container arrays and NULL entries without error if $ref
        // is NULL, but throws an error if $ref is set, but not an array.
        if ($force && isset($ref) && !is_array($ref)) {
            $ref = array();
        }

        $ref = &$ref[$parent];
    }

    $ref = $value;
}

/**
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Component%21Utility%21NestedArray.php/function/NestedArray%3A%3AgetValue/8
 */
function &array_path_get(array &$array, array $parents, &$keyExists = null) {
    $ref = &$array;
    foreach ($parents as $parent) {
        if (is_array($ref) && array_key_exists($parent, $ref)) {
            $ref = &$ref[$parent];
        } else {
            $keyExists = false;
            $null = null;

            return $null;
        }
    }

    $keyExists = true;

    return $ref;
}

/**
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Component%21Utility%21NestedArray.php/function/NestedArray%3A%3AunsetValue/8
 */
function array_path_unset(array &$array, array $parents, &$keyExisted = null) {
    $unsetKey = array_pop($parents);
    $ref =& array_path_get($array, $parents, $keyExisted);
    if ($keyExisted && is_array($ref) && array_key_exists($unsetKey, $ref)) {
        $keyExisted = TRUE;
        unset($ref[$unsetKey]);
    } else {
        $keyExisted = FALSE;
    }
}

/**
 * @param object $object
 *
 * @return string
 */
function get_object_id($object)
{
    $function = \Closure::bind(function ($object) {
        return (string) isset($object->values['_id']) ? $object->values['_id'] : null;
    }, null, $object);

    return $function($object);
}

/**
 * @param object          $object
 * @param ObjectID|string $objectId
 */
function set_object_id($object, $objectId)
{
    $function = \Closure::bind(function ($object) use ($objectId) {
        $object->values['_id'] = (string) $objectId;
    }, null, $object);

    return $function($object);
}

/**
 * @param object $object
 * @param array $values
 * @param bool $byReference
 *
 * @return object
 */
function set_object_values($object, array &$values, $byReference = false)
{
    $function = \Closure::bind(function ($object, array &$values, $byReference) {
        if ($byReference) {
            $object->values = &$values;
        } else {
            $object->values = $values;
        }

        $object->changedValues = [];

        if (property_exists($object, 'objects')) {
            $object->objects = [];
        }
    }, null, $object);

    $function($object, $values, $byReference);

    return $object;
}

function get_object_values($object)
{
    $function = \Closure::bind(function ($object) {
        return $object->values;
    }, null, $object);

    return $function($object);
}

function get_object_changed_values($object)
{
    $function = \Closure::bind(function ($object) {
        $changedValues = $object->changedValues;

        // hack I know
        if (property_exists($object, 'objects')) {
            foreach ($object->objects as $namespace => $namespaceValues) {
                foreach ($namespaceValues as $name => $values) {
                    if (is_array($values)) {
                        foreach ($values as $valueKey => $value) {
                            if ($changed = get_object_changed_values($value)) {
                                $changedValues[$namespace][$name][$valueKey] = $changed;
                            }
                        }
                    } elseif (is_object($values)) {
                        if ($changed = get_object_changed_values($values)) {
                            $changedValues[$namespace][$name] = $changed;
                        }
                    }
                }
            }
        }

        return $changedValues;
    }, null, $object);

    return $function($object);
}

function build_object($classOrClosure, array &$values, \Closure $objectBuilder = null)
{
    if ($classOrClosure instanceof \Closure) {
        $class = $classOrClosure($values);
    } else {
        $class = (string) $classOrClosure;
    }

    $object = new $class();
    set_object_values($object, $values, true);

    $objectBuilder && $objectBuilder($object);

    return $object;
}

function clone_object($object)
{
    $values = get_object_values($object);

    return build_object(get_class($object), $values);
}