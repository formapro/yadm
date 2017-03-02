<?php
namespace Makasim\Yadm\Tests\Functional;

use MongoDB\Client;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;

abstract class FunctionalTest extends TestCase
{
    /**
     * @var Database
     */
    protected $database;

    /**
     * @before
     */
    protected function setUpMongoClient()
    {
        $uri = getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1/';

        $client = new Client($uri);
        $this->database = $client->selectDatabase('yadm_test');

        foreach ($this->database->listCollections() as $collectionInfo) {
            if ('system.indexes' == $collectionInfo->getName()) {
                continue;
            }

            $this->database->dropCollection($collectionInfo->getName());
        }
    }
}