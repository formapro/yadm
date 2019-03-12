<?php
namespace Formapro\Yadm;

use Psr\Container\ContainerInterface;

class LocatorRegistry implements Registry
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorages(): array
    {
        $uniqueStorages = [];
        foreach (array_keys($this->storages) as $storageId) {
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
