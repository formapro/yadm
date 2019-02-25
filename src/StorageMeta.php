<?php
namespace Formapro\Yadm;

class StorageMeta implements StorageMetaInterface
{
    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return [];
    }

    public function getCreateCollectionOptions(): array
    {
        return [];
    }
}
