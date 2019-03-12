<?php
namespace Formapro\Yadm;

use MongoDB\Client;

class ClientProvider
{
    private $args;
    
    private $client;
    
    /** 
     * @see MongoDB\Client::__construct arguments
     */
    public function __construct(...$args)
    {
        $this->args = $args;
    }

    public function getClient(): Client
    {
        if (null === $this->client) {
            $this->client = new Client(...$this->args);
        }
        
        return $this->client;
    }
}
