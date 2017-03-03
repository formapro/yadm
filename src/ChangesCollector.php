<?php
namespace Makasim\Yadm;

use function Makasim\Values\get_values;
use mikemccabe\JsonPatch\JsonPatch;

class ChangesCollector
{
    private $originalValues;

    public function register($object)
    {
        if ($id = get_object_id($object)) {
            $this->originalValues[$id] = get_values($object);
        }
    }

    public function unregister($object)
    {
        if ($id = get_object_id($object)) {
            unset($this->originalValues[$id]);
        }
    }

    public function changes($object)
    {
        if (false == $id = get_object_id($object)) {
            throw new \LogicException(sprintf('Object does not have an id set.'));
        }

        if (false == array_key_exists($id, $this->originalValues)) {
            throw new \LogicException(sprintf('Changes has not been collected. The object with id "%s" original data is missing.'));
        }

        $diff = JsonPatch::diff($this->originalValues[$id], get_values($object));

        $update = ['$set' => [], '$unset' => []];
        foreach ($diff as $op) {
            switch ($op['op']) {
                case 'add':
                    if (is_array($op['value'])) {
                        foreach ($op['value'] as $key => $value) {
                            $update['$set'][$this->pathToDot($op['path']).'.'.$key] = $value;
                        }
                    } else {
                        $update['$set'][$this->pathToDot($op['path'])] = $op['value'];
                    }

                    break;
                case 'remove':
                    $update['$unset'][$this->pathToDot($op['path'])] = '';

                    break;
                case 'replace':
                    $update['$set'][$this->pathToDot($op['path'])] = $op['value'];

                    break;
                default:
                    throw new \LogicException('JSON Patch operation "'.$op['op'].'"" is not supported.');
            }


        }

        if (empty($update['$set'])) {
            unset($update['$set']);
        }
        if (empty($update['$unset'])) {
            unset($update['$unset']);
        }

        return $update;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function pathToDot($path)
    {
        $path = ltrim($path, '/');

        return str_replace('/', '.', $path);
    }
}
