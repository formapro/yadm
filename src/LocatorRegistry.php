<?php
namespace Formapro\Yadm;

use Psr\Container\ContainerInterface;

class LocatorRegistry implements Registry
{
    private $container;
    
    private $storageIds;

    /**
     * @param string[] $storageIds
     */
    public function __construct(array $storageIds, ContainerInterface $container)
    {
        $this->storageIds = $storageIds;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorages(): array
    {
        $uniqueStorages = [];
        foreach ($this->storageIds as $storageId) {
            $storage = $this->getStorage($storageId);

            if (isset($uniqueStorages[$storage->getCollection()->getCollectionName()])) {
                continue;
            }

            $uniqueStorages[$storage->getCollection()->getCollectionName()] = $storage;
        }

        return $uniqueStorages;
    }

    public function getStorage(string $name): Storage
    {
        if (false == $this->container->has($name)) {
            throw new \InvalidArgumentException(sprintf('The storage with name "%s" does not exist', $id));
        }

        return $this->container->get($name);
    }
}
