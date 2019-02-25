<?php
namespace Formapro\Yadm;

class Registry
{
    /**
     * @var array|Storage[]
     */
    private $storages;

    /**
     * @var array|Repository[]
     */
    private $repositories;

    /**
     * @param Storage[]|array $storages
     * @param Repository[]|array $repositories
     */
    public function __construct(array $storages, array $repositories)
    {
        $this->storages = $storages;
        $this->repositories = $repositories;
    }

    /**
     * @return Storage[]|array
     */
    public function getStorages()
    {
        return $this->storages;
    }

    /**
     * @return Storage[]|array
     */
    public function getUniqueStorages()
    {
        $uniqueStorages = [];
        foreach ($this->storages as $storage) {
            if (isset($uniqueStorages[$storage->getCollection()->getCollectionName()])) {
                continue;
            }

            $uniqueStorages[$storage->getCollection()->getCollectionName()] = $storage;
        }

        return $uniqueStorages;
    }

    /**
     * @param object|string $modelOrClass
     *
     * @return Storage
     */
    public function getStorage($modelOrClass)
    {
        $class = is_object($modelOrClass) ? get_class($modelOrClass) : $modelOrClass;

        if (false == array_key_exists($class, $this->storages)) {
            throw new \InvalidArgumentException(sprintf('The storage for model "%s" does not exist', $class));
        }

        $storage = $this->storages[$class];
        if (false == $storage instanceof Storage) {
            throw new \LogicException(sprintf(
                'Storage must be instance of %s but got %s',
                Storage::class,
                is_object($storage) ? get_class($storage) : gettype($storage)
            ));
        }

        return $storage;
    }

    /**
     * @param object|string $modelOrClass
     *
     * @return Repository
     */
    public function getRepository($modelOrClass)
    {
        $class = is_object($modelOrClass) ? get_class($modelOrClass) : $modelOrClass;

        if (false == array_key_exists($class, $this->repositories)) {
            $this->repositories = new Repository($this->getStorage($class));
        }

        $repository = $this->repositories[$class];
        if (false == $repository instanceof  Repository) {
            throw new \LogicException(sprintf(
                'Repository must be instance of %s but got %s',
                Repository::class,
                is_object($repository) ? get_class($repository) : gettype($repository)
            ));
        }

        return $repository;
    }
}
