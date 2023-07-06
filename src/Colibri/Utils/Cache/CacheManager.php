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

    private static function _getPath(string $section, string $fileName)
    {
        $etag = md5($fileName);
        $pathParts = substr($etag, 0, 2) . '/' . substr($etag, 2, 2);
        return App::$appRoot . App::$config->Query('runtime')->GetValue() . $section . '/' . $pathParts;
    }

    /**
     * Положить файл в кэш
     *
     * @param string $section
     * @param string $file
     * @return string
     * @testFunction testCacheManagerPut
     */
    public static function Put(string $section, string $fileName, string $fileContent)
    {
        $targetFilePath = self::GetPath($section, $fileName);
        File::Write($targetFilePath, $fileContent, true, '777');
        return $targetFilePath;
    }


    /**
     * Проверяет наличие кэша
     * @param string $section секция
     * @param string $file файл
     * @return bool есть/нет
     */
    public static function Exists(string $section, string $fileName)
    {
        return File::Exists(self::GetPath($section, $fileName));
    }

    /**
     * Возвращает файл (путь к файлу) кэша
     * ! внимание, не проверяет наличие файла
     * @param string $section секция
     * @param string $file файл
     * @return string 
     */
    public static function Get(string $section, string $fileName): ?string
    {
        if(self::Exists($section, $fileName)) {
            return File::Read(self::GetPath($section, $fileName));
        } else {
            return null;
        }
    }

    /**
     * Возвращает данные файл кэша
     * @param string $section секция
     * @param string $file файл
     * @return string|null 
     */
    public static function GetPath(string $section, string $fileName): string
    {
        $targetPath = self::_getPath($section, $fileName);
        return $targetPath . $fileName;
    }
}