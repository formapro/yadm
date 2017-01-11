<?php
namespace Makasim\Yadm;

/**
 * @property array $values
 * @property array $changedValues
 */
trait ObjectsTrait
{
    /**
     * @var array
     */
    protected $objects = [];

    /**
     * @var \Closure|null
     */
    protected $objectBuilder;

    /**
     * @param string $key
     * @param $classOrClosure
     *
     * @return object|null
     */
    protected function getObject($key, $classOrClosure)
    {
        if (false == $object = get_value($key, null, $this->objects)) {
            $values =& get_value($key, null, $this->values);
            if (null === $values) {
                return;
            }

            $object = build_object($classOrClosure, $values, $this->objectBuilder);

            set_value($key, $object, $this->objects);
        }

        return $object;
    }

    /**
     * @param string      $key
     * @param object|null $object
     */
    protected function setObject($key, $object)
    {
        unset_value($key, $this->values, $this->changedValues);
        unset_value($key, $this->objects);

        if ($object) {
            set_value($key, get_object_values($object), $this->values, $this->changedValues);

            $values =& get_value($key, [], $this->values);
            set_object_values($object, $values, true);

            set_value($key, $object, $this->objects);
        }
    }

    /**
     * @param string   $key
     * @param object[] $objects
     */
    protected function setObjects($key, $objects)
    {
        if (null === $objects) {
            unset_value($key, $this->values, $this->changedValues);
            unset_value($key, $this->objects);
        } else {
            set_value($key, [], $this->values, $this->changedValues);
            set_value($key, [], $this->objects);
        }

        if ($objects) {
            foreach ($objects as $objectKey => $object) {
                $this->addObject($key, $object, $objectKey);
            }
        }
    }

    /**
     * @param string $key
     * @param object $object
     * @param string|null $objectKey
     */
    protected function addObject($key, $object, $objectKey = null)
    {
        if (false == has_value($key, $this->values)) {
            set_value($key, [], $this->values, $this->changedValues);
        }

        if (false == has_value($key, $this->objects)) {
            set_value($key, [], $this->objects);
        }

        if (null === $objectKey) {
            $objectKey = count(get_value($key, [], $this->values));
        }

        $this->setObject("$key.$objectKey", $object);
    }

    /**
     * @param string          $key
     * @param string|\Closure $classOrClosure
     *
     * @return \Traversable
     */
    protected function getObjects($key, $classOrClosure)
    {
        foreach (array_keys(get_value($key, [], $this->values)) as $valueKey) {
            if (false == $object = get_value("$key.$valueKey", null, $this->objects)) {
                $values =& get_value("$key.$valueKey", [], $this->values);

                $object = build_object($classOrClosure, $values, $this->objectBuilder);

                set_value("$key.$valueKey", $object, $this->objects);
            }

            yield $object;
        }
    }
}