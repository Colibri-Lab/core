<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Storages
 */

namespace Colibri\IO\Request;

use Colibri\Collections\ArrayList;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use Colibri\Web\RequestedFile;

/**
 * Request data.
 *
 * @extends ArrayList<DataItem>
 */
class Data extends ArrayList
{

    /**
     * Create data from an array.
     *
     * @param mixed $array The array from which to create data.
     * @param string|null $keyBefore Optional key before data.
     * @param Data|null &$d Optional Data object reference.
     * @return Data The created data.
     */
    public static function FromArray(array $array, ?string $keyBefore = null, ? Data &$d = null)
    {
        if (!$d) {
            $d = new Data();
        }

        foreach ($array as $k => $v) {
            if ($v instanceof RequestedFile || $v instanceof File) {
                $d->Add(new DataFile($keyBefore !== null ? $keyBefore . '[' . $k . ']' : $k, $v->binary, $v->name, $v->mimetype));
            } elseif (is_object($v) || is_array($v)) {
                self::FromArray($v, $keyBefore !== null ? $keyBefore . '[' . $k . ']' : $k, $d);
            } elseif (!is_null($v)) {
                $d->Add(new DataItem($keyBefore !== null ? $keyBefore . '[' . $k . ']' : $k, $v));
            }
        }
        return $d;
    }
}