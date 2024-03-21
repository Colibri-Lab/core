<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\FileSystem
 */


namespace Colibri\IO\FileSystem;

/**
 * Base class for File and Directory.
 */
class Node
{

    /**
     * Attributes
     *
     * @var Attributes
     */
    protected ? Attributes $attributes = null;

    /**
     * Access rights
     *
     * @var Security
     */
    protected ? Security $access = null;

    /**
     * Setter
     *
     * @param string $property Property
     * @param mixed $value Value
     */
    public function __set(string $property, mixed $value): void
    {
        switch (strtolower($property)) {
            case 'created':
                $this->getAttributesObject()->created = $value;
                break;
            case 'modified':
                $this->getAttributesObject()->modified = $value;
                break;
            case 'readonly':
                $this->getAttributesObject()->readonly = $value;
                break;
            case 'hidden':
                $this->getAttributesObject()->hidden = $value;
                break;
            default: {
                    break;
                }
        }
    }

    /**
     * Load attribute data.
     *
     * @return Attributes
     */
    protected function getAttributesObject(): Attributes
    {
        if ($this->attributes === null) {
            $this->attributes = new Attributes($this);
        }
        return $this->attributes;
    }

    /**
     * Load access rights data.
     *
     * @return Security
     */
    protected function getSecurityObject(): Security
    {
        if ($this->access === null) {
            $this->access = new Security($this);
        }
        return $this->access;
    }

    /**
     * Creates a symbolic link.
     *
     * @param string $sourcePath The path to the source file or directory.
     * @param string $destPath The path to the destination where the link will be created.
     * @param bool $recursive Whether to create parent directories if they don't exist. Defaults to true.
     * @param string $mode The mode for the parent directory if it needs to be created. Defaults to '777'.
     */
    public static function Link(string $sourcePath, string $destPath, bool $recursive = true, string $mode = '777')
    {
        if(!file_exists($destPath) && file_exists($sourcePath)) {
            $path2 = dirname($destPath[strlen($destPath) - 1] == '/' ? $destPath . '#' : $destPath);
            shell_exec('mkdir' . ($recursive ? ' -p' : '') . ' -m' . $mode . ' ' . $path2);
            symlink($sourcePath, $destPath);
        }
    }

}