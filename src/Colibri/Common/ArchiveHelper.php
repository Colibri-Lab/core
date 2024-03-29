<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Common
 */
namespace Colibri\Common;

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
        $zip = new \ZipArchive();
        $zip->open('php://temp/maxmemory:' . strlen($binary), \ZipArchive::CREATE);
        $zip->addFromString($file, $binary);
        $zip->close();
        $handle = fopen('php://temp/maxmemory:' . strlen($binary), 'r+');
        return stream_get_contents($handle);
    }

}