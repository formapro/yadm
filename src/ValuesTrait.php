<?php
namespace Makasim\Yadm;

trait ValuesTrait
{
    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var array
     */
    protected $changedValues = [];

    /**
     * @param string $key
     * @param string $value
     */
    protected function addValue($key, $value)
    {
        if (method_exists($this, 'castValue')) {
            $value = $this->castValue($value);
        }

        $currentValue = $this->getValue($key, []);
        if (false == is_array($currentValue)) {
            throw new \LogicException(sprintf('Cannot set value to %s it is already set and not array', $key));
        }

        $currentValue[] = $value;

        $this->setValue($key, $currentValue);
    }

    /**
     * @param string $key
     * @param string $value
     */
    protected function setValue($key, $value)
    {
        if (method_exists($this, 'castValue')) {
            $value = $this->castValue($value);
        }

        set_value($key, $value, $this->values, $this->changedValues);

        if (property_exists($this, 'objects')) {
            unset_value($key, $this->objects);
        }
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @param string $castTo
     *
     * @return mixed
     */
    protected function getValue($key, $default = null, $castTo = null)
    {
        $value = get_value($key, $default , $this->values);

        if ($castTo) {
            if (method_exists($this, 'cast')) {
                $value = $this->cast($value, $castTo);
            } else {
                throw new \LogicException('Casting is not supported.');
            }
        }

        return $value;
    }
}