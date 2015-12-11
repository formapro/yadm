<?php
namespace Makasim\Yadm;

use MongoDB\Driver\Cursor;

class Iterator implements \Iterator
{
    /**
     * @var Cursor
     */
    private $cursor;

    /**
     * @var \Iterator
     */
    private $iterator;

    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * @param Cursor $cursor
     * @param Hydrator $hydrator
     */
    public function __construct(Cursor $cursor, Hydrator $hydrator)
    {
        $this->cursor = $cursor;
        $this->hydrator = $hydrator;

        $this->iterator = new \IteratorIterator($cursor);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->hydrator->hydrate($this->iterator->current());
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * @return Cursor
     */
    public function getCursor()
    {
        return $this->cursor;
    }
}