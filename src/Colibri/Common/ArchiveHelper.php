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
 * @class
 */
class ArchiveHelper
{
    /**
     * Create archive from binary data and file name
     * @property string $binary data to archive
     * @property string $file file name
     * @return string
     * @public
     * @static
     * @example
     * ```
     * $binaryData = 'Hello, World!';
     * $fileName = 'example.txt';
     * $archiveData = ArchiveHelper::Create($binaryData, $fileName);
     * echo $archiveData; // Outputs the binary data of the created archive
     * ```
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
     * @public
     * @static
     * @example
     * ```
     * $archiveData = '...'; // Binary data of the archive  
     * $extractedData = ArchiveHelper::Extract($archiveData);
     * echo $extractedData; // Outputs the extracted data from the archive
     * ```
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
     * @public
     * @static
     * @example
     * ```
     * $fileName = 'example.zip';
     * $isArchive = ArchiveHelper::IsArchive($fileName);
     * if ($isArchive) {
     *     echo "$fileName is a valid zip archive.";
     * } else {
     *     echo "$fileName is not a valid zip archive.";
     * }
     * ```
     */
    public static function IsArchive(string $filename): bool
    {
        $fh = fopen($filename, 'r');
        $bytes = fread($fh, 4);
        fclose($fh);
        return '504b0304' === bin2hex($bytes);
    }

    /**
     * Extracts a zip archive to the specified directory.
     *
     * @param string $path The path to the zip archive.
     * @param string $directoryPath The path to the directory where the archive will be extracted.
     * @return void
     * @public
     * @static
     * @example
     * ```
     * $zipPath = 'path/to/archive.zip';
     * $extractTo = 'path/to/extract/directory';
     * ArchiveHelper::ExtractTo($zipPath, $extractTo);
     * echo "Archive extracted to: $extractTo";
     * ```
     */
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
