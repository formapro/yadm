<?php
namespace Formapro\Yadm;

use MongoDB\Client;
use MongoDB\Collection;

class CollectionFactory
{
    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * @var string
     */
    private $mongoDsn;

    public function __construct(ClientProvider $clientProvider, string $mongoDsn)
    {
        $this->clientProvider = $clientProvider;
        $this->mongoDsn = $mongoDsn;
    }

    public function create(string $collectionName, string $databaseName = null, array $options = []): Collection
    {
       if (false == $databaseName) {
           $databaseName = ltrim(parse_url($this->mongoDsn, PHP_URL_PATH), '/');
       }

       if (false == $databaseName) {
           throw new \LogicException('Failed to guess database name, neither mongo DSN nor argument have it.');
       }

       $client = $this->clientProvider->getClient();

       return $client->selectCollection($databaseName, $collectionName, $options);
    }
}
