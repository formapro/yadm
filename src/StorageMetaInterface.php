<?php
namespace Makasim\Yadm;

interface StorageMetaInterface
{
    /**
     * @return Index[]
     */
    public function getIndexes(): array;

    public function getCreateCollectionOptions(): array;
}
