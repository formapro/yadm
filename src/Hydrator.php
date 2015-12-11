<?php
namespace Makasim\Yadm;

use MongoDB\BSON\Persistable;

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
     * @return Persistable|object
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
     * @param array|object $bson
     *
     * @param Persistable|null $model
     *
     * @return Persistable|object
     */
    public function hydrate(array $bson, Persistable $model = null)
    {
        $model = $model ?: $this->create();
        if (false == $model instanceof  Persistable) {
            throw new \LogicException(sprintf('The model %s must implement %s interface', $this->modelClass, Persistable::class));
        }

        if (isset($bson['_id'])) {
            $bson['_id'] = (string) $bson['_id'];
        }

        $model->bsonUnserialize($bson);

        return $model;
    }
}