<?php
namespace Formapro\Yadm;

use function Formapro\Values\build_object;
use function Formapro\Values\set_values;
use MongoDB\BSON\ObjectID;

class Hydrator
{
    /**
     * @var string
     */
    private $modelClass;

    /**
     * @param string $modelClass
     */
    public function __construct($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * @param array $values
     *
     * @return object
     */
    public function create(array $values = [])
    {
        return $this->hydrate($values, build_object($this->modelClass, $values));
    }

    /**
     * @param array $values
     *
     * @param object|null $model
     *
     * @return object
     */
    public function hydrate(array $values, $model = null)
    {
        $model = $model ?: $this->create($values);

        if (isset($values['_id'])) {
            set_object_id($model, new ObjectID((string) $values['_id']));

            unset($values['_id']);
        }

        set_values($model, $values);

        return $model;
    }
}