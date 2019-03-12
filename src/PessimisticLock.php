<?php
declare(strict_types=1);

namespace Formapro\Yadm;

use MongoDB\BSON\UTCDatetime;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\DuplicateKeyException;
use MongoDB\Driver\Exception\RuntimeException;

class PessimisticLock implements StorageMetaInterface
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

    private $autoCreateIndexes;

    public function __construct(Collection $collection, string $sessionId = null, int $limit = 300)
    {
        $this->collection = $collection;
        $this->sessionId = $sessionId ?: getmypid().'-'.(microtime(true) * 10000);
        $this->limit = $limit;
        $this->autoCreateIndexes = true;

        register_shutdown_function(function () { $this->unlockAll(); });
    }

    public function autoCreateIndexes(bool $bool): void
    {
        $this->autoCreateIndexes = $bool;
    }

    public function locked(string $id): bool
    {
        return (bool) $result = $this->collection->count([
            'id' => $id,
            'sessionId' => ['$not' => $this->sessionId],
        ]);
    }

    /**
     * Limit is in seconds
     *
     * @param string $id
     * @param bool $blocking
     * @param int $limit is ignored if blocking is false.
     */
    public function lock(string $id, bool $blocking = true, int $limit = 300): void
    {
        $this->autoCreateIndexes && $this->createIndexes();

        if ($limit > $this->limit) {
            throw new \LogicException('The limit could not be greater than a default one. The default is used to set an expiration index');
        }

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

            if (false == $blocking) {
                throw PessimisticLockException::failedObtainLock($id, $limit);
            }

            // Mongo does database lock level on insert, so everything has to wait even reads.
            // I decided to do it rarely to decrease global lock rate.
            // We will have at least 150 attempts to get the lock, pretty enough IMO.
            // More here http://docs.mongodb.org/manual/faq/concurrency/
            usleep(200000);
        }

        throw PessimisticLockException::failedObtainLock($id, $limit);
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

    public function unlockAll(): void
    {
        $result = $this->collection->deleteMany([
            'sessionId' => $this->sessionId,
        ]);

        if (false == $result->isAcknowledged()) {
            throw new \LogicException('Cannot unlock all locked ids. The deleteMany operation is not acknowledged.');
        }
    }

    public function dropIndexes()
    {
        try {
            $this->collection->dropIndexes();
        } catch (RuntimeException $e) {
        }
    }

    public function createIndexes(int $lockExpireAfterSeconds = null): void
    {
        if (null === $lockExpireAfterSeconds) {
            $lockExpireAfterSeconds = $this->limit + 2;
        }

        if ($lockExpireAfterSeconds <= $this->limit) {
            throw new \LogicException('The expiration could not be lesser than default limit');
        }

        $existingIndexes = [];
        foreach ($this->collection->listIndexes() as $index) {
            $existingIndexes[$index->getName()] = $index->getName();
        }
        
        foreach ($this->getIndexes() as $index) {
            if (empty($index->getOptions()['name'])) {
                $this->collection->createIndex($index->getKey(), $index->getOptions());
            }
        }
    }

    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        $lockExpireAfterSeconds = $this->limit + 2;
        
        return [
            new Index(['id' => 1], ['unique' => true, 'name' => 'id']),
            new Index(['timestamp' => 1], ['expireAfterSeconds' => $lockExpireAfterSeconds, 'name' => 'timestamp']),
            new Index(['sessionId' => 1], ['unique' => false, 'name' => 'sessionId'])
        ];
    }

    public function getCreateCollectionOptions(): array
    {
        return [];
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }
}
