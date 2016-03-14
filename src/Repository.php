<?php
namespace Makasim\Yadm;

class Repository
{
    /**
     * @var MongodbStorage
     */
    protected $storage;

    /**
     * @param MongodbStorage $storage
     */
    public function __construct(MongodbStorage $storage)
    {
        $this->storage = $storage;
    }
}
