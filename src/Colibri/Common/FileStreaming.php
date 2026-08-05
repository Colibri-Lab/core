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
 * Represents a class for handling file streaming operations.
 * @class
 */
class FileStreaming
{
    /**
     * Converts a file to its Base64-encoded representation.
     *
     * @param string $file The path to the file to be converted.
     *
     * @return string The Base64-encoded content of the file.
     * @public
     * @static
     * @example
     * ```
     * $filePath = 'path/to/file.txt';
     * $base64Content = FileStreaming::ToBase64($filePath);
     * echo $base64Content; // Outputs the Base64-encoded content of the file
     * ```
     */
    public static function ToBase64(string $file): string
    {
        $fileData = File::Read($file);
        $fi = new File($file);
        $mime = new MimeType($fi->extension);
        $mimeType = $mime->data;
        return 'data:' . $mimeType . ';base64,' . base64_encode($fileData);
    }

    /**
     * Converts the content of a file to a text representation.
     *
     * @param string $file The path to the file.
     *
     * @return string The text content of the file.
     * @public
     * @static
     * @example
     * ```
     * $filePath = 'path/to/file.txt';
     * $textContent = FileStreaming::AsText($filePath);
     * echo $textContent; // Outputs the text content of the file
     * ```
     */
    public static function AsText(string $file): string
    {
        return File::Read($file);
    }

    /**
     * Converts the content of a file to a tag representation.
     *
     * @param string $file The path to the file.
     * @param bool $background Whether the tag should have a background (optional, default is false).
     *
     * @return string The tag representation.
     * @public
     * @static
     * @example
     * ```
     * $filePath = 'path/to/image.png';
     * $tagRepresentation = FileStreaming::AsTag($filePath, true);
     * echo $tagRepresentation; // Outputs the tag representation of the file
     * ```
     */
    public static function AsTag(string $file, bool $background = false): string
    {
        $fi = new File($file);
        if ($fi->extension == 'svg') {
            return File::Read($file);
        } elseif ($background) {
            return '<img src="/res/1x1.gif" style="background-image: url(' . str_replace('//', '/', str_replace(App::$webRoot, '/', $file)) . ')" />';
        } else {
            return '<img src="' . str_replace('//', '/', str_replace(App::$webRoot, '/', $file)) . '" />';
        }
    }
}
