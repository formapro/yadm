<?php
namespace Formapro\Yadm;

final class Index
{
    /**
     * @var array
     */
    private $key;

    /**
     * @var array
     */
    private $options;

    public function __construct(array $key, array $options = [])
    {
        $this->key = $key;
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getKey(): array
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
