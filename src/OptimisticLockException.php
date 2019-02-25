<?php
namespace Formapro\Yadm;

/**
 * An OptimisticLockException is thrown when a version check on an object
 * that uses optimistic locking through a version field fails.
 */
class OptimisticLockException extends \RuntimeException
{
    /**
     * @return OptimisticLockException
     */
    public static function lockFailed()
    {
        return new self('The optimistic lock on an document failed.');
    }
}