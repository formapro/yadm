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
     * @param string      $key
     * @param object|null $object
     */
    protected function setSelfObject($key, $object)
    {
        $this->setObject('self', $key, $object);
    }

    /**
     * @param string          $key
     * @param string|\Closure $classOrClosure
     *
     * @return object
     */
    protected function getSelfObject($key, $classOrClosure)
    {
        return $this->getObject('self', $key, $classOrClosure);
    }

    /**
     * @param string   $key
     * @param object[] $objects
     */
    protected function setSelfObjects($key, $objects)
    {
        $this->setObjects('self', $key, $objects);
    }

    /**
     * @param string          $key
     * @param string|\Closure $classOrClosure
     *
     * @return object[]
     */
    protected function getSelfObjects($key, $classOrClosure)
    {
        return $this->getObjects('self', $key, $classOrClosure);
    }

    /**
     * @param string $key
     * @param object $object
     */
    protected function addSelfObject($key, $object)
    {
        $this->addObject('self', $key, $object);
    }


    /**
     * @internal
     *
     * @param string $namespace
     * @param string $key
     * @param $classOrClosure
     *
     * @return object
     *
     */
    protected function getObject($namespace, $key, $classOrClosure)
    {
        if (false == isset($this->values[$namespace][$key])) {
            return;
        }

        if (false == isset($this->objects[$namespace][$key])) {
            $this->objects[$namespace][$key] = build_object(
                $classOrClosure,
                $this->values[$namespace][$key],
                $this->objectBuilder
            );
        }

        return $this->objects[$namespace][$key];
    }

    /**
     * @internal
     *
     * @param string      $namespace
     * @param string      $key
     * @param object|null $object
     */
    protected function setObject($namespace, $key, $object)
    {
        if ($object) {
            $this->values[$namespace][$key] = get_values($object);
            $this->changedValues[$namespace][$key] = get_values($object);

            set_values($object, $this->values[$namespace][$key], true);

            $this->objects[$namespace][$key] = $object;
        } else {
            unset($this->values[$namespace][$key]);
            unset($this->objects[$namespace][$key]);
            $this->changedValues[$namespace][$key] = null;
        }
    }

    /**
     * @internal
     *
     * @param string   $namespace
     * @param string   $key
     * @param object[] $objects
     */
    protected function setObjects($namespace, $key, $objects)
    {
        if (null === $objects) {
            unset($this->objects[$namespace][$key]);
            unset($this->values[$namespace][$key]);
            $this->changedValues[$namespace][$key] = null;
        } else {
            $this->objects[$namespace][$key] = [];
            $this->values[$namespace][$key] = [];
            $this->changedValues[$namespace][$key] = [];

            foreach ($objects as $object) {
                $this->addObject($namespace, $key, $object);
            }
        }
    }

    /**
     * @internal
     *
     * @param string $namespace
     * @param string $key
     * @param object $object
     */
    protected function addObject($namespace, $key, $object)
    {
        if (false == isset($this->values[$namespace][$key])) {
            $this->values[$namespace][$key] = [];
        }
        if (false == isset($this->objects[$namespace][$key])) {
            $this->objects[$namespace][$key] = [];
        }

        $objectKey = count($this->values[$namespace][$key]);

        $this->objects[$namespace][$key][$objectKey] = $object;
        $this->values[$namespace][$key][$objectKey] = get_values($object);
        $this->changedValues[$namespace][$key][$objectKey] = get_values($object);

        set_values($object, $this->values[$namespace][$key][$objectKey], true);
    }

    /**
     * @internal
     *
     * @param string          $namespace
     * @param string          $key
     * @param string|\Closure $classOrClosure
     *
     * @return object[]
     */
    protected function getObjects($namespace, $key, $classOrClosure)
    {
        if (false == isset($this->values[$namespace][$key])) {
            return [];
        }
        if (false == isset($this->objects[$namespace][$key])) {
            $this->objects[$namespace][$key] = [];
        }

        // the addObject method can add an object to the end of collection but the rest of collection has not been initiated yet
        foreach (array_keys($this->values[$namespace][$key]) as $valueKey) {
            if (false == isset($this->objects[$namespace][$key][$valueKey])) {
                $this->objects[$namespace][$key][$valueKey] = build_object(
                    $classOrClosure,
                    $this->values[$namespace][$key][$valueKey],
                    $this->objectBuilder
                );
            }
        }

        return $this->objects[$namespace][$key];
    }
}