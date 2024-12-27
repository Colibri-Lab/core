<?php

/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

use Colibri\App;
use Colibri\IO\FileSystem\File;

/**
 * Mime types.
 *
 * @property-read string $data
 * @property-read bool $isCapable
 * @property-read bool $isValid
 * @property-read bool $isImage
 * @property-read bool $isAudio
 * @property-read bool $isVideo
 * @property-read bool $isViewable
 * @property-read bool $isFlashVideo
 * @property-read bool $isFlash
 * @property-read string $type
 */
class MimeType
{
    /**
     * List of MIME types.
     *
     * @var array
     */
    protected static array $mime_types = [];

    /**
    * List of types compatible with browsers.
    *
    * @var array
    */
    protected static array $browserCapableTypes = array(
        "jpg",
        "png",
        "gif",
        "swf",
        "html",
        "htm",
        "css",
        "js",
        "xml",
        "xsl"
    );

    /**
     * File type.
     *
     * @var string
     */
    private string $_type;

    /**
     * Constructor.
     *
     * @param string $type The file type.
     */
    public function __construct(string $type)
    {
        if(empty(self::$mime_types)) {
            $this->_loadMimeTypes();
        }
        $this->_type = $type;
    }

    private function _loadAndSave(): void
    {
        $runtimePath = App::$appRoot . App::$config->Query('runtime')->GetValue();
        $content = file_get_contents('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');
        File::Write($runtimePath . 'mime.types', $content, true, '777');
    }

    private function _loadMimeTypes()
    {
        $runtimePath = App::$appRoot . App::$config->Query('runtime')->GetValue();
        if(File::Exists($runtimePath . 'mime.types')) {
            $file = new File($runtimePath . 'mime.types');
            $modified = $file->attr_modified;
        } else {
            $modified = time() - 31 * 86400;
        }

        if($modified < time() - 30 * 86400) {
            $this->_loadAndSave();
        }

        $mimetypesContent = File::Read($runtimePath . 'mime.types');
        if(!$mimetypesContent) {
            $this->_loadAndSave();
        }
        $mimetypesContent = explode("\n", $mimetypesContent);
        foreach($mimetypesContent as $line) {
            if(!$line) {
                continue;
            }
            if(substr($line, 0, 1) !== '#') {
                $line = preg_replace('/\t+/', "\t", $line);
                $parts = explode("\t", $line);
                $mimetype = trim($parts[0], "\r\t\n ");
                $filetypes = trim($parts[1], "\r\t\n ");
                $filetypes = explode(' ', $filetypes);

                foreach($filetypes as $filetype) {
                    self::$mime_types[$filetype] = $mimetype;
                }
            }
        }
    }

    /**
     * Returns mimetype by given file type
     * @return string
     */
    protected function getPropertyData(): ?string
    {
        return isset(MimeType::$mime_types[$this->_type]) ? MimeType::$mime_types[$this->_type] : null;
    }

    /**
     * Returns if type is compatible with browsers
     * @return bool
     */
    protected function getPropertyIsCapable(): bool
    {
        return array_search($this->_type, MimeType::$browserCapableTypes) !== false;
    }

    /**
     * Returns if the file type is valid
     * @return bool
     */
    protected function getPropertyIsValid(): bool
    {
        return array_key_exists($this->_type, MimeType::$mime_types);
    }

    /**
     * Returns if the file type is image file type
     * @return bool
     */
    protected function getPropertyIsImage(): bool
    {
        return in_array(strtolower($this->_type), array("gif", "jpeg", "jpg", "png", "bmp", "dib"));
    }

    /**
     * Returns if the file type is audio file type
     * @return bool
     */
    protected function getPropertyIsAudio(): bool
    {
        return in_array(strtolower($this->_type), array("mid", "mp3", "au"));
    }

    /**
     * Returns if the file type is videofile type
     * @return bool
     */
    protected function getPropertyIsVideo(): bool
    {
        return in_array(strtolower($this->_type), array("wmv", "mpg", "mp4", "m4v", "avi"));
    }

    /**
     * Returns if the file type is viewable
     * @return bool
     */
    protected function getPropertyIsViewable(): bool
    {
        return in_array(strtolower($this->_type), array("gif", "jpg", "jpeg", "png", "swf"));
    }

    /**
     * Returns if the file type is the flash video object
     * @return bool
     */
    protected function getPropertyIsFlashVideo(): bool
    {
        return in_array(strtolower($this->_type), array("flv"));
    }

    /**
     * Returns if the file type is the flash object
     * @return bool
     */
    protected function getPropertyIsFlash(): bool
    {
        return in_array(strtolower($this->_type), array("swf"));
    }

    /**
     * Returns if the file type in this object
     * @return bool
     */
    protected function getPropertyType(): string
    {
        return $this->_type;
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function __get(string $field): mixed
    {
        $return = null;
        switch ($field) {
            case "data": {
                $return = $this->getPropertyData();
                break;
            }
            case "isCapable": {
                $return = $this->getPropertyIsCapable();
                break;
            }
            case "isValid": {
                $return = $this->getPropertyIsValid();
                break;
            }
            case "isImage": {
                $return = $this->getPropertyIsImage();
                break;
            }
            case "isAudio": {
                $return = $this->getPropertyIsAudio();
                break;
            }
            case "isVideo": {
                $return = $this->getPropertyIsVideo();
                break;
            }
            case "isViewable": {
                $return = $this->getPropertyIsViewable();
                break;
            }
            case "isFlashVideo": {
                $return = $this->getPropertyIsFlashVideo();
                break;
            }
            case "isFlash": {
                $return = $this->getPropertyIsFlash();
                break;
            }
            case "type": {
                $return = $this->getPropertyType();
                break;
            }
            default:
        }
        return $return;
    }

    /**
     * Creates an MimeType object for given file name
     *
     * @param string $filename
     * @return MimeType
     */
    public static function Create(string $filename): MimeType
    {
        $parts = explode(".", basename($filename));
        return new MimeType(end($parts));
    }

    /**
     * Returns file type by mimetype given
     *
     * @param string $mimetype
     * @return string
     */
    public static function GetType(string $mimetype): ?string
    {
        foreach (MimeType::$mime_types as $key => $type) {
            if ($type == $mimetype) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Returns file type by given file name
     */
    public static function GetTypeFromFileName(string $filename): ?string
    {
        $filename = explode('.', $filename);
        return strtolower(end($filename));
    }
}
