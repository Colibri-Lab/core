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
    public static function Create(string $binary, string $file): string
    {
        $runtime = App::$appRoot . App::$config->Query('runtime')->GetValue() . '/temp.zip';
        if(File::Exists($runtime)) {
            File::Delete($runtime);
        }
        $zip = new \ZipArchive();
        $zip->open($runtime, \ZipArchive::CREATE);
        $zip->addFromString($file, $binary, \ZipArchive::FL_OVERWRITE);
        $zip->close();
        $return = file_get_contents($runtime);
        File::Delete($runtime);
        return $return;
    }

    public static function Extract(string $binary): string
    {
        $runtime = App::$appRoot . App::$config->Query('runtime')->GetValue() . '/temp.zip';
        if (File::Exists($runtime)) {
            File::Delete($runtime);
        }
        File::Create($runtime);
        File::Write($runtime, $binary);

        if(self::IsArchive($runtime)) {
            $zip = new \ZipArchive();
            $zip->open($runtime);
            $return = $zip->getFromIndex(0);
            $zip->close();
        }

        File::Delete($runtime);
        return $return;
    }

    public static function IsArchive(string $filename): bool
    {
        $fh = fopen($filename, 'r');
        $bytes = fread($fh, 4);
        fclose($fh);
        return '504b0304' === bin2hex($bytes);
    }

}
