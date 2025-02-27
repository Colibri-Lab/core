<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */

namespace Colibri\Common;

use Colibri\App;
use Colibri\IO\FileSystem\Directory;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use DateTime;
use DateTimeZone;
use Colibri\Data\Storages\Fields\DateTimeField;

/**
 * Helper class for working with dates.
 */
class ArchiveHelper
{
    /**
     * Create archive from binary data and file name
     * @property string $binary data to archive
     * @property string $file file name
     * @return string
     */
    public static function Create(string $binary, string $file): string
    {
        $runtime = App::$appRoot . App::$config->Query('runtime')->GetValue() . '/temp.zip';
        if(File::Exists($runtime)) {
            File::Delete($runtime);
        }
        $fileInfo = pathinfo($file)['basename'];
        $zip = new \ZipArchive();
        $zip->open($runtime, \ZipArchive::CREATE);
        $zip->addFromString($fileInfo, $binary, \ZipArchive::FL_OVERWRITE);
        $zip->close();
        $return = file_get_contents($runtime);
        File::Delete($runtime);
        return $return;
    }

    /**
     * Extract and archive from binary data
     * @property string $binary archive data
     * @return string
     */
    public static function Extract(string $binary): string
    {
        $runtime = App::$appRoot . App::$config->Query('runtime')->GetValue() . 'temp.zip';
        if (File::Exists($runtime)) {
            File::Delete($runtime);
        }
        File::Create($runtime);
        File::Write($runtime, $binary);

        $return = $binary;
        if(self::IsArchive($runtime)) {
            $zip = new \ZipArchive();
            $zip->open($runtime);
            $return = $zip->getFromIndex(0);
            $zip->close();
        }

        File::Delete($runtime);
        return $return;
    }

    /**
     * Check if the file is zip archive
     * @property string $filename file name to check
     * @return bool
     */
    public static function IsArchive(string $filename): bool
    {
        $fh = fopen($filename, 'r');
        $bytes = fread($fh, 4);
        fclose($fh);
        return '504b0304' === bin2hex($bytes);
    }

    public static function ExtractTo(string $path, string $directoryPath): void
    {

        if(!Directory::Exists($directoryPath)) {
            Directory::Create($directoryPath, true, '777');
        }

        $arch = new \ZipArchive();
        $arch->open($path);
        $arch->extractTo($directoryPath);
        $arch->close();

    }

}
