<?php
namespace Makasim\Yadm;

class Hydrator
{
    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var object
     */
    private $prototypeModel;

    /**
     * @param string $modelClass
     */
    public function __construct($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * @return object
     */
    public function create()
    {
        if (false == $this->prototypeModel) {
            $this->prototypeModel = new $this->modelClass();

            $this->hydrate([], $this->prototypeModel);
        }

        return clone $this->prototypeModel;
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
        $model = $model ?: $this->create();

        if (isset($values['_id'])) {
            $values['_id'] = (string) $values['_id'];
        }

        set_values($model, $values);

        return $model;
    }
}