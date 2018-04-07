<?php
declare(strict_types=1);

namespace Makasim\Yadm\Type;

use function Makasim\Values\array_get;
use MongoDB\BSON\UTCDateTime;

class UTCDatetimeType implements Type
{
    public function toMongoValue(string $key, array $values, array $originalValues): ?UTCDatetime
    {
        /** @var string|null $currentValue */
        $currentValue = array_get($key, null, $values);

        /** @var UTCDatetime|null $originalValue */
        $originalValue = array_get($key, null, $originalValues);

        if (null == $currentValue) {
            return null;
        }

        if ($originalValue && $currentValue === $originalValue->toDateTime()->getTimestamp()) {
            return $originalValue;
        }

        return new UTCDatetime($currentValue);
    }

    public function toPHPValue(string $key, array $originalValues): ?int
    {
        /** @var UTCDateTime|null $value */
        $value = array_get($key, null, $originalValues);

        return null !== $value ? $value->toDateTime()->getTimestamp() : null;
    }
}
