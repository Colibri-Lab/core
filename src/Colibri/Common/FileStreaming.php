<?php

/**
 * Common
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 */
namespace Colibri\Common;

use Colibri\App;
use Colibri\IO\FileSystem\File;

/**
 * Утилиты для работы с файлами
 */
class FileStreaming
{

    /**
     * Вернуть данные в файле в виде base64-encoded
     *
     * @param string $file
     * @return string
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
     * Вернуть данные в файле в виде строки
     *
     * @param string $file
     * @return string
     */
    public static function AsText(string $file): string
    {
        return File::Read($file);
    }

    /**
     * Вернуть тэг изображения
     *
     * @param string $file
     * @param boolean $background
     * @return string
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