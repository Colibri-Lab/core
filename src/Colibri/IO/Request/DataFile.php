<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Storages
 */

namespace Colibri\IO\Request;

use Colibri\Common\MimeType;
use Colibri\IO\FileSystem\File;

/**
 * File in the request.
 *
 * @property string $name The name of the file.
 * @property string $mime The MIME type of the file.
 * @property string $file The file data or path.
 * @property string $value The value of the file.
 */
class DataFile extends DataItem
{

    /**
     * Constructor.
     *
     * @param string $name The name of the property.
     * @param string $filePathOrFileData The file data or path.
     * @param string $filename The name of the file.
     * @param string|null $mime The MIME type.
     */
    public function __construct(string $name, string $filePathOrFileData, string $filename = '', string $mime = null)
    {

        // $data is file path
        if (File::Exists($filePathOrFileData)) {
            $fi = new File($filePathOrFileData);
            $filename = $fi->name;
            $filePathOrFileData = File::Read($filePathOrFileData);
        }

        parent::__construct($name, $filePathOrFileData);

        if (!$mime) {
            $mime = MimeType::Create($filename)->data;
        }

        $this->mime = $mime;
        $this->file = $filename;
    }
}
