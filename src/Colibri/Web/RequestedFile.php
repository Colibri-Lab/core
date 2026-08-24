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

use Colibri\Common\MimeType;
use InvalidArgumentException;

/**
 * Requested File Class
 *
 * Represents a file sent in a request.
 * Read-only.
 * @class
 *  
 * @property boolean $isValid Indicates whether the file is valid.
 * @property string $binary Binary content of the file.
 *
 */
class RequestedFile
{
    /** 
     * The name of the file.
     * @var string 
     * @public
     */
    public string $name;
    /** 
     * The extension of the file.
     * @var string
     * @public
     */
    public string $ext;
    /** 
     * The MIME type of the file.
     * @var string
     * @public
     */
    public string $mimetype;
    /** 
     * The error message, if any.
     * @var string
     * @public
     */
    public string $error;
    /** 
     * The size of the file in bytes.
     * @var int 
     * @public
     */
    public int $size;
    /**
     * The path to the temporary file. 
     * @var string 
     * @public
     */
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
        $this->mimetype = $arrFILE["type"] ?: MimeType::Create($this->name)->data;
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
