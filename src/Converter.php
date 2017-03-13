<?php
namespace Makasim\Yadm;

class Converter
{
    /**
     * @param array $diff
     * 
     * @return array
     */
    public static function convertJsonPatchToMongoUpdate(array $diff)
    {
        $update = ['$set' => [], '$unset' => []];
        foreach ($diff as $op) {
            switch ($op['op']) {
                case 'add':
                    if (is_array($op['value'])) {
                        foreach ($op['value'] as $key => $value) {
                            $update['$set'][self::pathToDot($op['path']).'.'.$key] = $value;
                        }
                    } else {
                        $update['$set'][self::pathToDot($op['path'])] = $op['value'];
                    }

                    break;
                case 'remove':
                    $update['$unset'][self::pathToDot($op['path'])] = '';

                    break;
                case 'replace':
                    $update['$set'][self::pathToDot($op['path'])] = $op['value'];

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
    private static function pathToDot($path)
    {
        $path = ltrim($path, '/');

        return str_replace('/', '.', $path);
    }
}