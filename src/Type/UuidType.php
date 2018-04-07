<?php
declare(strict_types=1);

namespace Makasim\Yadm\Type;

use function Makasim\Values\array_get;
use MongoDB\BSON\Binary;
use Ramsey\Uuid\Uuid;

class UuidType implements Type
{
    public function toMongoValue(string $key, array $values, array $originalValues): ?Binary
    {
        /** @var string|null $currentValue */
        $currentValue = array_get($key, null, $values);

        /** @var Binary|null $originalValue */
        $originalValue = array_get($key, null, $originalValues);

        if (null == $currentValue) {
            return null;
        }

        if ($originalValue && Uuid::fromString($currentValue)->equals(Uuid::fromBytes($originalValue->getData()))) {
            return $originalValue;
        }

        return new Binary(
            Uuid::fromString($currentValue)->getBytes(),
            Binary::TYPE_UUID
        );
    }

    public function toPHPValue(string $key, array $originalValues): ?string
    {
        /** @var Binary|null $value */
        $value = array_get($key, null, $originalValues);

        return null !== $value ? (string) Uuid::fromBytes($value->getData()) : null;
    }
}