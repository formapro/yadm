<?php
namespace Makasim\Yadm\Tests\Functional;

use Makasim\Yadm\PessimisticLock;

class PessimisticLockTest extends FunctionalTest
{
    public function testWaitForLockIsReleased()
    {
        $this->markTestSkipped('Do not work. Check it later');

        $lockCollection = $this->database->selectCollection('storage_lock_test');
        $pessimisticLock = new PessimisticLock($lockCollection);
        $pessimisticLock->createIndexes();
        $lockCollection->dropIndex('timestamp_1');
        $lockCollection->createIndex(['timestamp' => 1], ['expireAfterSeconds' => 3]);

        $startTime = time();

        $pessimisticLock->lock('5669dd8f56c02c4628031635');
        $pessimisticLock->lock('5669dd8f56c02c4628031635', 10);

        $endTime = time();

        $this->assertGreaterThanOrEqual(3, $endTime - $startTime);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot obtain the lock for id "5669dd8f56c02c4628031635". Timeout after 2 seconds
     */
    public function testWaitForLockIsNotReleased()
    {
        $lockCollection = $this->database->selectCollection('storage_lock_test');
        $pessimisticLock = new PessimisticLock($lockCollection);
        $pessimisticLock->createIndexes();

        $pessimisticLock->lock('5669dd8f56c02c4628031635');
        $pessimisticLock->lock('5669dd8f56c02c4628031635', 2);
    }
}