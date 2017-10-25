<?php
namespace Makasim\Yadm;

use MongoDB\BSON\UTCDatetime;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\DuplicateKeyException;
use MongoDB\Driver\Exception\RuntimeException;

class PessimisticLock
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var string
     */
    private $sessionId;
    /**
     * @var int
     */
    private $limit;

    public function __construct(Collection $collection, string $sessionId = null, int $limit = 300)
    {
        $this->collection = $collection;
        $this->sessionId = $sessionId ?: getmypid().'-'.(microtime(true)*10000);
        $this->limit = $limit;

        register_shutdown_function(function () { $this->unlockAll(); });
    }

    /**
     * Limit is in seconds
     *
     * @param string $id
     * @param int $limit
     */
    public function lock(string $id, int $limit = 300): void
    {
        $this->createIndexes();

        $timeout = time() + $limit; // I think it must be a bit greater then mongos index ttl so there is a way to process data.

        while (time() < $timeout) {
            try {
                $result = $this->collection->insertOne([
                    'id' => $id,
                    'timestamp' => new UTCDatetime(time() * 1000),
                    'sessionId' => $this->sessionId,
                ]);

                if (false == $result->isAcknowledged()) {
                    throw new \LogicException(sprintf('Cannot obtain the lock for id %s. The insertOne operation is not acknowledged.', $id));
                }

                return;
            } catch (BulkWriteException $e) {
            } catch (DuplicateKeyException $e) {
                // The lock is obtained by another process. Let's try again later.
            }

            // Mongo does database lock level on insert, so everything has to wait even reads.
            // I decided to do it rarely to decrease global lock rate.
            // We will have at least 150 attempts to get the lock, pretty enough IMO.
            // More here http://docs.mongodb.org/manual/faq/concurrency/
            usleep(200000);
        }

        throw new \RuntimeException(sprintf('Cannot obtain the lock for id "%s". Timeout after %s seconds', $id, $limit));
    }

    /**
     * @param string $id
     */
    public function unlock(string $id): void
    {
        $result = $this->collection->deleteOne([
            'id' => $id,
            'sessionId' => $this->sessionId,
        ]);

        if (false == $result->isAcknowledged()) {
            throw new \LogicException(sprintf('Cannot unlock id %s. The deleteOne operation is not acknowledged.', $id));
        }
    }

    public function unlockAll()
    {
        $result = $this->collection->deleteMany([
            'sessionId' => $this->sessionId,
        ]);

        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Cannot unlock all locked ids. The deleteMany operation is not acknowledged.');
        }
    }

    public function createIndexes()
    {
        try {
            $this->collection->dropIndexes();
        } catch (RuntimeException $e) {
        }

        $this->collection->createIndex(['id' => 1], ['unique' => true]);
        $this->collection->createIndex(['timestamp' => 1], ['expireAfterSeconds' => 302]);
        $this->collection->createIndex(['sessionId' => 1], ['unique' => false]);
    }
}
