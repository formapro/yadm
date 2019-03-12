<?php
namespace Formapro\Yadm;

class ArrayRegistry implements Registry
{
    /**
     * @var array|Storage[]
     */
    private $storages;

    /**
     * @param Storage[]|array $storages
     */
    public function __construct(array $storages)
    {
        $this->storages = $storages;
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
        if (false == array_key_exists($id, $this->storages)) {
            throw new \InvalidArgumentException(sprintf('The storage with name "%s" does not exist', $id));
        }

        return $this->storages[$id];
    }
}
