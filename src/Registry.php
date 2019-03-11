<?php
namespace Formapro\Yadm;

interface Registry
{
    /**
     * @return Storage[]|array
     */
    public function getStorages(): array;

    public function getStorage(string $name): Storage;
}
