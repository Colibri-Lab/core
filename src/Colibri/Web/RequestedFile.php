<?php

/**
 * Web
 *
 * This abstract class represents a template for web content generation.
 *
 * @package Colibri\Web
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 */

namespace Colibri\Web;

use InvalidArgumentException;

/**
 * Requested File Class
 *
 * Represents a file sent in a request.
 * Read-only.
 *
 * @property boolean $isValid Indicates whether the file is valid.
 * @property string $binary Binary content of the file.
 *
 */
class RequestedFile
{
    /** @var string The name of the file. */
    public string $name;
    /** @var string The extension of the file. */
    public string $ext;
    /** @var string The MIME type of the file. */
    public string $mimetype;
    /** @var string The error message, if any. */
    public string $error;
    /** @var int The size of the file in bytes. */
    public int $size;
    /** @var string The path to the temporary file. */
    public string $temporary;

    /**
     * Constructor.
     *
     * @param array|object $arrFILE The $_FILE array or object.
     */
    public function __construct(array |object $arrFILE)
    {

        if (!$arrFILE) {
            return;
        }

        $arrFILE = (array) $arrFILE;

        $this->name = $arrFILE["name"];
        $ret = preg_split("/\./i", $this->name);
        if (count($ret) > 1) {
            $this->ext = strtolower($ret[count($ret) - 1]);
        }
        $this->mimetype = $arrFILE["type"];
        $this->temporary = $arrFILE["tmp_name"];
        $this->error = $arrFILE["error"];
        $this->size = $arrFILE["size"];
    }

    /**
     * Magic getter method.
     *
     * @param string $prop The property name.
     * @return mixed The value of the property.
     */
    public function __get(string $prop): mixed
    {
        $prop = strtolower($prop);
        if ($prop == 'isvalid') {
            return !empty($this->name);
        } elseif ($prop == 'binary') {
            if(!$this->temporary) {
                throw new InvalidArgumentException('File path can not be empty');
            }
            return file_get_contents($this->temporary);
        }
        return null;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        // if (file_exists($this->temporary)) {
        //     unlink($this->temporary);
        // }
    }

    /**
     * Moves the temporary file to the specified directory.
     *
     * @param string $path The destination path.
     * @param int $mode The permissions to set for the file (default: 0777).
     * @return void
     *
     */
    public function MoveTo(string $path, int $mode = 0777): void
    {
        rename($this->temporary, $path);
        chmod($path, $mode);
    }
}
