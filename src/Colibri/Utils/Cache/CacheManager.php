<?php

/**
 * Хочется что либо закэшировать?
 * Тебе сюда!
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Cache
 * @version 1.0.0
 * 
 */

namespace Colibri\Utils\Cache;

use Colibri\App;
use Colibri\IO\FileSystem\File;

/**
 * Менеджер кэширования
 * @testFunction testCacheManager
 */
class CacheManager
{

    private static function _getPath($section, $file)
    {
        $etag = md5($file);
        $pathParts = substr($etag, 0, 2) . '/' . substr($etag, 2, 2);

        return App::$webRoot . '_cache/' . $section . '/' . $pathParts . '/';
    }

    /**
     * Положить файл в кэш
     *
     * @param string $section
     * @param string $file
     * @return string
     * @testFunction testCacheManagerPut
     */
    public static function Put($section, $file)
    {

        $targetPath = self::_getPath($section, $file);
        $fileName = basename($file);
        $targetFilePath = $targetPath . $fileName;

        $mtime = filemtime($file);

        $compareTime = $mtime;
        if (File::Exists($targetFilePath)) {
            $compareTime = filemtime($targetFilePath);

            if ($compareTime != $mtime) {
                File::Delete($targetFilePath);
            }
        }


        File::Write($targetFilePath, File::Read($file));

        return $targetFilePath;
    }

    /**
     * Создать кэш файл из данных
     *
     * @param string $section секция кэша
     * @param string $fileName название файла (без пути)
     * @param string $data данные, которые нужно записать, по умолчанию ничего
     * @return string
     * @testFunction testCacheManagerCreate
     */
    public static function Create($section, $fileName, $data = '')
    {

        $targetPath = self::_getPath($section, $fileName);
        $fileName = basename($fileName);
        
        $targetFilePath = $targetPath . $fileName;

        $md5 = md5($data);
        $compareWith = '';
        if (File::Exists($targetFilePath)) {
            $compareWith = md5_file($targetFilePath);

            if ($compareWith != $md5) {
                File::Delete($targetFilePath);
            }
        }

        File::Write($targetFilePath, $data, true);

        return $targetFilePath;
    }

    /**
     * Проверяет наличие кэша
     * @param string $section секция
     * @param string $file файл
     * @return bool есть/нет
     */
    public static function Exists($section, $file)
    {
        $targetPath = self::_getPath($section, $file);
        $fileName = basename($file);
        return File::Exists($targetPath . $fileName);
    }

    /**
     * Возвращает файл (путь к файлу) кэша
     * ! внимание, не проверяет наличие файла
     * @param string $section секция
     * @param string $file файл
     * @return string 
     */
    public static function Get($section, $file)
    {
        $targetPath = self::_getPath($section, $file);
        $fileName = basename($file);
        return $targetPath . $fileName;
    }



    /**
     * Возвращает данные файл кэша
     * @param string $section секция
     * @param string $file файл
     * @return string|null 
     */
    public static function GetStream($section, $file)
    {
        $targetPath = self::_getPath($section, $file);
        $fileName = basename($file);
        if (File::Exists($targetPath . $fileName)) {
            return File::Read($targetPath . $fileName);
        }
        return null;
    }
}
