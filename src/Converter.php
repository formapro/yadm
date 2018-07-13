<?php
namespace Makasim\Yadm;

use function Makasim\Values\array_get;

class Converter
{
    /**
     * @param array $diff
     * 
     * @return array
     */
    public static function convertJsonPatchToMongoUpdate(array $diff, array $values)
    {
        $update = ['$set' => [], '$unset' => [], '$push' => []];

        $arrayFullReset = [];

        foreach ($diff as $op) {
            if (isset($op['path']) && '/_id' == $op['path']) {
                continue;
            }

            switch ($op['op']) {
                case 'add':
                    if (static::isPathArray($op['path'])) {
                        if (false == isset($update['$push'][self::pathToDotWithoutLastPart($op['path'])]['$each'])) {
                            $update['$push'][self::pathToDotWithoutLastPart($op['path'])]['$each'] = [];
                        }

                        $update['$push'][self::pathToDotWithoutLastPart($op['path'])]['$each'][] = $op['value'];
                    } else if (is_array($op['value']) && array_key_exists(0, $op['value'])) {
                        $update['$set'][self::pathToDot($op['path'])] = $op['value'];
                    } else if (is_array($op['value'])) {
                        foreach ($op['value'] as $key => $value) {
                            $update['$set'][self::pathToDot($op['path']) . '.' . $key] = $value;
                        }
                    } else {
                        $update['$set'][self::pathToDot($op['path'])] = $op['value'];
                    }

                    break;
                case 'remove':
                    // fix for  https://jira.mongodb.org/browse/SERVER-1014
                    if (static::isPathArray($op['path'])) {
                        $dotPath = self::pathToDotWithoutLastPart($op['path']);
                        if (array_key_exists($dotPath, $arrayFullReset)) {
                            continue;
                        }

                        $update['$set'][$dotPath] = array_get($dotPath, [], $values);

                        $arrayFullReset[$dotPath] = true;
                    } else {
                        $update['$unset'][self::pathToDot($op['path'])] = '';
                    }

                    break;
                case 'replace':
                    $update['$set'][self::pathToDot($op['path'])] = $op['value'];

                    break;
                default:
                    throw new \LogicException('JSON Patch operation "'.$op['op'].'"" is not supported.');
            }
        }

        foreach (array_keys($arrayFullReset) as $arrayResetPath) {
            foreach (array_keys($update['$set']) as $setPath) {
                if ($setPath === $arrayResetPath) {
                    continue;
                }

                if (0 === strpos($setPath, $arrayResetPath)) {
                    unset($update['$set'][$setPath]);
                }
            }
        }

        foreach (array_keys($arrayFullReset) as $arrayResetPath) {
            foreach (array_keys($update['$push']) as $pushPath) {
                if (0 === strpos($pushPath, $arrayResetPath)) {
                    unset($update['$push'][$pushPath]);
                }
            }
        }

        foreach (array_keys($arrayFullReset) as $arrayResetPath) {
            foreach (array_keys($update['$unset']) as $setPath) {
                if (0 === strpos($setPath, $arrayResetPath)) {
                    unset($update['$unset'][$setPath]);
                }
            }
        }

        if (empty($update['$push'])) {
            unset($update['$push']);
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
    private static function pathToDot(string $path): string
    {
        $path = ltrim($path, '/');

        return str_replace('/', '.', $path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private static function pathToDotWithoutLastPart(string $path): string
    {
        $parts = explode('/', ltrim($path));

        array_pop($parts);

        return static::pathToDot(implode('/', $parts));
    }

    private static function isPathArray(string $path): bool
    {
        $parts = explode('/', ltrim($path));

        return is_numeric(array_pop($parts));
    }
}