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
        if ($value instanceof \DateTime) {
            $value = [
                'unix' => (int) $value->format('U'),
                'iso' => (string) $value->format(DATE_ISO8601),
            ];
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
        if ($value instanceof \DateTime) {
            $value = [
                'unix' => (int) $value->format('U'),
                'iso' => (string) $value->format(DATE_ISO8601),
            ];
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

        if ('date' == $castTo) {
            if (is_numeric($value)) {
                $value = \DateTime::createFromFormat('U', $value);
            } elseif (is_array($value)) {
                $value = \DateTime::createFromFormat('U', $value['unix']);
            } else {
                $value = new \DateTime($value);
            }
        } elseif ($castTo) {
            settype($value, $castTo);
        }

        return $value;
    }
}