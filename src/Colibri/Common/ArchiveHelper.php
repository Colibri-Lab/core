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
        $zip = new \ZipArchive();
        $zip->open($runtime, \ZipArchive::CREATE);
        $zip->addFromString($file, $binary);
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
        $zip = new \ZipArchive();
        $zip->open($runtime);
        $return = $zip->getFromIndex(0);
        $zip->close();
        File::Delete($runtime);
        return $return;
    }

}