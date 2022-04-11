<?php

/**
 * FileSystem
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

use Colibri\Collections\ArrayList;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Debug;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Класс помогающий искать файлы и директории
 * @testFunction testFinder
 */
class Finder
{

    /**
     * Конструктор
     */
    public function __construct()
    {
    // Do nothing
    }

    /**
     * Найти файлы
     *
     * @param string $path путь к папке
     * @param string $match регулярное выражение
     * @param boolean $sortField поле для сориторовки
     * @param boolean $sortType тип сортировки
     * @return ArrayList
     * @testFunction testFinderFiles
     */
    public function Files(string $path, string $match = '', bool $sortField = false, bool $sortType = false)
    {

        $ret = new ArrayList();

        $files = glob($path . '{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
        sort($files, SORT_ASC);

        foreach ($files as $file) {
            if (filetype($file) != "dir") {
                if (!VariableHelper::IsEmpty($match) && preg_match($match, basename($file)) == 0) {
                    continue;
                }
                $ret->Add(new File($file));
            }
        }

        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }

        return $ret;
    }

    /**
     * Найти файлы рекурсивно
     *
     * @param string $path путь к папке
     * @param string $match регулярное выражение
     * @param string $sortField поле для сориторовки
     * @param int $sortType тип сортировки
     * @return ArrayList
     * @testFunction testFinderFiles
     */
    public function FilesRecursive(string $path, string $match = '/.*/', string $sortField = '', int $sortType = SORT_ASC): ArrayList
    {
        $directoryIterator = new RecursiveDirectoryIterator($path);
        $iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
        $regexIterator = new RegexIterator($iteratorIterator, $match);
        $ret = new ArrayList();
        foreach ($regexIterator as $file) {
            if (is_dir($file)) {
                continue;
            }
            $ret->Add(new File($file->getPathname()));
        }
        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }
        return $ret;
    }

    /**
     * Найти директории
     * q 
     * @param string $path путь к папке
     * @param string $sortField поле для сортировки
     * @param int $sortType типа сортировки
     * @return ArrayList
     * @testFunction testFinderDirectories
     */
    public function Directories(string $path, string $sortField = '', int $sortType = SORT_ASC): ArrayList
    {

        $ret = new ArrayList();
        $files = glob($path . '{,.}[!.,!..]*', GLOB_ONLYDIR | GLOB_MARK | GLOB_BRACE);
        sort($files, SORT_ASC);
        foreach ($files as $file) {
            $ret->Add(new Directory($file . '/'));
        }

        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }
        return $ret;
    }

    /**
     * Найти файлы рекурсивно
     *
     * @param string $path путь к папке
     * @param string $match регулярное выражение
     * @param string $sortField поле для сориторовки
     * @param int $sortType тип сортировки
     * @return ArrayList
     * @testFunction testFinderFiles
     */
    public function DirectoriesRecursive(string $path, string $match = '/.*/', string $sortField = '', int $sortType = SORT_ASC): ArrayList
    {
        $directoryIterator = new RecursiveDirectoryIterator($path);
        $iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
        $regexIterator = new RegexIterator($iteratorIterator, $match);
        $ret = new ArrayList();

        $keys = [];
        foreach ($regexIterator as $file) {
            
            if (is_file($file)) {
                continue;
            }

            $lpath = $file->getPathname();
            $lpath = preg_replace('/\/\.$/', '/', $lpath);
            $lpath = preg_replace('/\/\.\.$/', '/', $lpath);
            if(isset($keys[$lpath]) || $path == $lpath) {
                continue;
            }

            $keys[$lpath] = $lpath;
            $ret->Add(new Directory($lpath));

        }

        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }
        return $ret;

    }

    /**
     * Вернуть папки в директории
     *
     * @param string $path путь к директории
     * @return ArrayList
     * @testFunction testFinderChildren
     */
    public function Children(string $path): ArrayList
    {

        $ret = new ArrayList();
        $files = glob($path . '{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
        sort($files, SORT_ASC);
        foreach ($files as $file) {
            if (filetype($file) == "dir") {
                $ret->Add(new Directory($file . '/'));
            }
            else {
                $ret->Add(new File($file));
            }
        }
        return $ret;
    }


}
