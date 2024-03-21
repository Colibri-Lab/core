<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Storages
 */

namespace Colibri\IO\FileSystem;

use Colibri\Collections\ArrayList;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Debug;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use DirectoryIterator;
use Throwable;

/**
 * Class helping to find files and directories.
 */
class Finder
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Do nothing
    }

    /**
     * Find files.
     *
     * @param string $path Path to the folder
     * @param string $match Regular expression
     * @param string $sortField Field for sorting
     * @param int $sortType Sorting type
     * @return ArrayList
     */
    public function Files(string $path, string $match = '/.*/', string $sortField = '', int $sortType = SORT_ASC)
    {

        $ret = new ArrayList();

        try {
            if(Directory::Exists($path)) {

                $directoryIterator = new DirectoryIterator($path);
                foreach ($directoryIterator as $file) {
                    if (is_dir($file->getPathname())) {
                        continue;
                    }
                    if (!VariableHelper::IsEmpty($match) && preg_match($match, basename($file)) == 0) {
                        continue;
                    }

                    $ret->Add(new File($file->getPathname()));
                }
            }

        } catch (Throwable $e) {
            // do nothing
        }

        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }

        return $ret;
    }

    /**
     * Find files recursively.
     *
     * @param string $path Path to the folder
     * @param string $match Regular expression
     * @param string $sortField Field for sorting
     * @param int $sortType Sorting type
     * @return ArrayList
     */
    public function FilesRecursive(string $path, string $match = '/.*/', string $sortField = '', int $sortType = SORT_ASC): ArrayList
    {

        $ret = new ArrayList();
        try {
            $directoryIterator = new RecursiveDirectoryIterator($path);
            $iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
            $regexIterator = new RegexIterator($iteratorIterator, $match);
            foreach ($regexIterator as $file) {
                if (is_dir($file)) {
                    continue;
                }
                $ret->Add(new File($file->getPathname()));
            }
        } catch (Throwable $e) {
            // do nothing
        }

        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }
        return $ret;
    }

    /**
     * Find directories.
     *
     * @param string $path Path to the folder
     * @param string $sortField Field for sorting
     * @param int $sortType Sorting type
     * @return ArrayList
     */
    public function Directories(string $path, string $sortField = '', int $sortType = SORT_ASC): ArrayList
    {
        $ret = new ArrayList();
        try {
            if(Directory::Exists($path)) {
                $directoryIterator = new DirectoryIterator($path);
                foreach ($directoryIterator as $file) {
                    if (is_dir($file->getPathname()) && !in_array($file->getFilename(), ['.', '..'])) {
                        $ret->Add(new File($file->getPathname()));
                    }
                }
            }
        } catch (Throwable $e) {
            // do nothing
        }

        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }

        return $ret;
    }

    /**
     * Find directories recursively.
     *
     * @param string $path Path to the folder
     * @param string $match Regular expression
     * @param string $sortField Field for sorting
     * @param int $sortType Sorting type
     * @return ArrayList
     */
    public function DirectoriesRecursive(string $path, string $match = '/.*/', string $sortField = '', int $sortType = SORT_ASC): ArrayList
    {
        $ret = new ArrayList();
        try {
            if(Directory::Exists($path)) {

                $directoryIterator = new RecursiveDirectoryIterator($path);
                $iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
                $regexIterator = new RegexIterator($iteratorIterator, $match);

                $keys = [];
                foreach ($regexIterator as $file) {

                    if (is_file($file)) {
                        continue;
                    }

                    $lpath = $file->getPathname();
                    $lpath = preg_replace('/\/\.$/', '/', $lpath);
                    $lpath = preg_replace('/\/\.\.$/', '/', $lpath);
                    if (isset($keys[$lpath]) || $path == $lpath) {
                        continue;
                    }

                    $keys[$lpath] = $lpath;
                    $ret->Add(new Directory($lpath));

                }
            }
        } catch (Throwable $e) {
            // do nothing
        }


        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }
        return $ret;

    }

    /**
     * Return children folders in a directory.
     *
     * @param string $path Path to the directory
     * @return ArrayList
     */
    public function Children(string $path): ArrayList
    {

        $ret = new ArrayList();
        try {
            if(Directory::Exists($path)) {

                $directoryIterator = new DirectoryIterator($path);
                foreach ($directoryIterator as $file) {
                    if (!in_array($file->getFilename(), ['.', '..'])) {
                        $ret->Add(new File($file->getPathname()));
                    }
                }
            }
        } catch (Throwable $e) {
            // do nothing
        }

        return $ret;
    }


}
