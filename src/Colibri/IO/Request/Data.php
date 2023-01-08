<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\Request
 */

namespace Colibri\IO\Request;

use Colibri\Collections\ArrayList;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use Colibri\Web\RequestedFile;

/**
 * Данные запроса
 * @extends ArrayList<DataItem> 
 * @testFunction testData
 */
class Data extends ArrayList
{

    /**
     * Создать данные из массива
     *
     * @param mixed $array
     * @return Data
     * @testFunction testDataFromArray
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