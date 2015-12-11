<?php
namespace Makasim\Yadm\Tests\Functional;

use MongoDB\Client;
use MongoDB\Database;

abstract class FunctionalTest extends \PHPUnit_Framework_TestCase
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
        $client = new Client();
        $this->database = $client->selectDatabase('yadm_test');

        foreach ($this->database->listCollections() as $collectionInfo) {
            if ('system.indexes' == $collectionInfo->getName()) {
                continue;
            }

            $this->database->dropCollection($collectionInfo->getName());
        }
    }
}