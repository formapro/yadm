<?php
namespace Makasim\Yadm;

use MongoDB\Client;
use MongoDB\Collection;

class CollectionFactory
{
    /**
     * @var Client
     */
    private $mongodb;

    /**
     * @var string
     */
    private $mongoDsn;

    public function __construct(Client $mongodb, string $mongoDsn)
    {
        $this->mongodb = $mongodb;
        $this->mongoDsn = $mongoDsn;
    }

    public function create(string $collectionName, string $databaseName = null, array $options = []): Collection
    {
       if (false == $databaseName) {
           $databaseName = parse_url($this->mongoDsn, PHP_URL_PATH);
       }

       if (false == $databaseName) {
           throw new \LogicException('Failed to guess database name, neither mongo DSN nor argument have it.');
       }

       return $this->mongodb->selectCollection($databaseName, $collectionName, $options);
    }
}
