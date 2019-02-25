<?php
declare(strict_types=1);

namespace Formapro\Yadm\Type;

interface Type
{
    public function toMongoValue(string $key, array $values, array $originalValues);

    public function toPHPValue(string $key, array $originalValues);
}
