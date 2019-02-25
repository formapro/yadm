<?php
namespace Formapro\Yadm;

class PessimisticLockException extends \LogicException
{
    /**
     * @return PessimisticLockException
     */
    public static function failedObtainLock(string $id, int $limit): self
    {
        return new self(sprintf('Cannot obtain the lock for id "%s". Timeout after %s seconds', $id, $limit));
    }
}
