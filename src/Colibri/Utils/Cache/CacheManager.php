<?php

/**
 * Config
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils\Cache
 *
 */

namespace Colibri\Utils\Cache;

use Colibri\App;
use Colibri\IO\FileSystem\File;

/**
 * Cache Manager class.
 *
 */
class CacheManager
{
    /**
     * Gets the cache file path.
     *
     * @param string $section The cache section
     * @param string $fileName The name of the file
     * @return string The cache file path
     */
    private static function _getPath(string $section, string $fileName)
    {
        $etag = md5($fileName);
        $pathParts = substr($etag, 0, 2) . '/' . substr($etag, 2, 2);
        return App::$appRoot . App::$config->Query('runtime')->GetValue() . $section . '/' . $pathParts;
    }

    /**
     * Puts a file into the cache.
     *
     * @param string $section The cache section
     * @param string $fileName The name of the file
     * @param string $fileContent The content of the file
     * @return string The path to the cached file
     */
    public static function Put(string $section, string $fileName, string $fileContent)
    {
        $targetFilePath = self::GetPath($section, $fileName);
        File::Write($targetFilePath, $fileContent, true, '777');
        return $targetFilePath;
    }


    /**
     * Checks if the cache exists for the given file.
     *
     * @param string $section The cache section
     * @param string $fileName The name of the file
     * @return bool True if the cache exists, otherwise false
     */
    public static function Exists(string $section, string $fileName)
    {
        return File::Exists(self::GetPath($section, $fileName));
    }

    /**
     * Gets the cached file content.
     *
     * @param string $section The cache section
     * @param string $fileName The name of the file
     * @return string|null The content of the cached file, or null if not found
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
     * Gets the cache file path.
     *
     * @param string $section The cache section
     * @param string $fileName The name of the file
     * @return string The cache file path
     */
    public static function GetPath(string $section, string $fileName): string
    {
        $targetPath = self::_getPath($section, $fileName);
        return $targetPath . $fileName;
    }
}
