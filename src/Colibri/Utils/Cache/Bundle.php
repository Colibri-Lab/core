<?php

/**
 * Bundle
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils
 */

namespace Colibri\Utils\Cache;

use Colibri\App;
use Colibri\Common\Encoding;
use Colibri\Events\Event;
use Colibri\Events\EventsContainer;
use Colibri\IO\FileSystem\File;
use Colibri\IO\FileSystem\Directory;
use Colibri\IO\FileSystem\Exception as FileSystemException;
use Colibri\IO\FileSystem\Finder;
use Colibri\Utils\Debug;
use axy\sourcemap\SourceMap;
use Colibri\Utils\Config\ConfigException;

/**
 * Создание кэшей стилей и скриптов
 * @testFunction testBundle
 */
class Bundle
{

    /**
     * Компиляция скриптов и стилей
     *
     * @param string $name
     * @param array $exts
     * @param string $path
     * @param array $exception
     * @param boolean $preg
     * @param boolean $returnContent
     * @return string
     * @testFunction testBundleCompile
     */
    public static function Compile(string $name, array $exts, string $path, array $exception = array(), bool $preg = false, bool $returnContent = false /*, &$returnFiles = []*/): string
    {
        $mode = App::$config ?App::$config->Query('mode')->GetValue() : App::ModeDevelopment;
        $jpweb = App::$webRoot . App::$config->Query('cache')->GetValue() . 'code/' . $name;
        if (!$returnContent && !in_array($mode, [App::ModeDevelopment, App::ModeLocal]) && File::Exists($jpweb)) {
            return str_replace(App::$webRoot, '/', $jpweb);
        }

        $namespaces = self::GetNamespaceAssets($path, $exts, $exception, $preg);
        $files = self::GetChildAssets($path, $exts, $exception, $preg);

        $files = array_merge($namespaces, $files);

        $content = '';
        foreach ($files as $file) {
            if (!File::Exists($file)) {
                throw new FileSystemException('file not exists ' . $file);
            }

            $c = File::Read($file) . "\n";
            $args = (object)['content' => $c, 'file' => $file];
            $args = App::$instance->DispatchEvent(EventsContainer::BundleFile, $args);
            if (isset($args->content)) {
                $c = $args->content;
            }
            
            $content .= $c;
        }

        if ($returnContent) {
            return $content;
        }

        if (File::Exists($jpweb)) {
            File::Delete($jpweb);
        }

        File::Write($jpweb, $content, true, '777');

        return str_replace(App::$webRoot, '/', $jpweb);
    }

    public static function LastModified(string $name, array $exts, string $path, array $exception = array(), bool $preg = false): int
    {
        $lastModified = 0;
        $namespaces = self::GetNamespaceAssets($path, $exts, $exception, $preg);
        $files = self::GetChildAssets($path, $exts, $exception, $preg);

        $files = array_merge($namespaces, $files);

        foreach ($files as $file) {
            if (!File::Exists($file)) {
                throw new FileSystemException('file not exists ' . $file);
            }
            $lastModified = max($lastModified, filemtime($file));
        }
        return $lastModified;
    }

    /**
     * Компиляция скриптов и стилей в виде массива файлов
     *
     * @param string $name
     * @param array $exts
     * @param array $files
     * @param boolean $returnContent
     * @return string
     * @testFunction testBundleCompile
     */
    public static function CompileFiles(string $name, array $exts, array $files, bool $returnContent = false): string
    {
        $mode = App::$config ?App::$config->Query('mode')->GetValue() : App::ModeDevelopment;
        $jpweb = App::$webRoot . App::$config->Query('cache')->GetValue() . 'code/' . $name;
        if (!$returnContent && !in_array($mode, [App::ModeDevelopment, App::ModeLocal]) && File::Exists($jpweb)) {
            return str_replace(App::$webRoot, '/', $jpweb);
        }

        $content = '';
        foreach ($files as $file) {
            if (!File::Exists($file)) {
                throw new FileSystemException('file not exists ' . $file);
            }

            $c = File::Read($file) . "\n\n";
            $args = (object)['content' => $c, 'file' => $file];
            $args = App::$instance->DispatchEvent(EventsContainer::BundleFile, $args);
            if (isset($args->content)) {
                $c = $args->content;
            }
            $content .= $c;
        }

        if ($returnContent) {
            return $content;
        }

        if (File::Exists($jpweb)) {
            File::Delete($jpweb);
        }

        File::Write($jpweb, $content, true, '777');

        return str_replace(App::$webRoot, '/', $jpweb);
    }

    /**
     * @testFunction testBundleGetNamespaceAssets
     */
    protected static function GetNamespaceAssets(string $path, array $exts, array $exception = [], bool $preg = false): array
    {
        $files = [];

        $di = new Finder();
        $foundFiles = $di->Files($path, '/^\..*/');

        foreach ($foundFiles as $file) {
            /** @var File $file */
            if ($file->extension && !in_array($file->name, $exception) && in_array($file->extension, $exts)) {
                $files[] = $file->path;
            }
        }

        $foundDirectories = $di->Directories($path, 'filename', SORT_ASC);
        foreach ($foundDirectories as $dir) {
            /** @var Directory $dir */

            if (!in_array($dir->name, $exception)) {
                $files = array_merge($files, self::GetNamespaceAssets($dir->path . '/', $exts, $exception, $preg));
            }
        }

        return $files;
    }

    /**
     * Возвращает список дочерних вложений
     *
     * @param string $path
     * @param array $exts
     * @param array $exception
     * @param boolean $preg
     * @return array
     * @testFunction testBundleGetChildAssets
     */
    protected static function GetChildAssets(string $path, array $exts, array $exception = [], bool $preg = false): array
    {
        $files = [];

        $di = new Finder();
        $foundFiles = $di->Files($path, '/^[^\.]/');

        $foundDirectories = $di->Directories($path, 'filename', SORT_ASC);
        foreach ($foundDirectories as $dir) {
            /** @var Directory $dir */

            if (!in_array($dir->name, $exception)) {
                $files = array_merge($files, self::GetChildAssets($dir->path . '/', $exts, $exception, $preg));
            }
        }

        foreach ($foundFiles as $file) {
            /** @var File $file */
            if ($file->extension && !in_array($file->name, $exception) && in_array($file->extension, $exts)) {
                $files[] = $file->path;
            }
        }


        return $files;
    }

    /**
     * Автоматизация скриптов и стилей
     *
     * @param string $name
     * @param array|string $ext
     * @param array $ar
     * @return string
     * @testFunction testBundleAutomate
     */
    public static function Automate(string $domain, string $name, string $ext, array $ar): string
    {
        
        $name = $domain . '.' . $name;
        $mode = App::$config ?App::$config->Query('mode')->GetValue() : App::ModeDevelopment;

        $jpweb = App::$webRoot . App::$config->Query('cache')->GetValue() . 'code/' . $name;
        if (File::Exists($jpweb)) {
            if (in_array($mode, [App::ModeDevelopment, App::ModeLocal])) {
                $lastModified = 0;
                foreach ($ar as $settings) {
                    $lastModified = max($lastModified, self::LastModified(isset($settings['name']) ? $settings['name'] : '',
                        isset($settings['exts']) ? $settings['exts'] : [$ext],
                        $settings['path'],
                        isset($settings['exception']) ? $settings['exception'] : array(),
                        isset($settings['preg']) ? $settings['preg'] : false));
                }
                if (filemtime($jpweb) > $lastModified) {
                    return str_replace(App::$webRoot, '/', $jpweb . '?' . md5_file($jpweb));
                }
            }
            else {
                return str_replace(App::$webRoot, '/', $jpweb . '?' . md5_file($jpweb));
            }
        }

        $content = array();

        $args = App::$instance->DispatchEvent(EventsContainer::BundleStart, (object)['exts' => [$ext]]);
        if (isset($args['content'])) {
            $content[] = $args['content'];
        }

        foreach ($ar as $settings) {
            $content[] = Bundle::Compile(
                isset($settings['name']) ? $settings['name'] : '',
                isset($settings['exts']) ? $settings['exts'] : [$ext],
                $settings['path'],
                isset($settings['exception']) ? $settings['exception'] : array(),
                isset($settings['preg']) ? $settings['preg'] : false,
                true
            );
        }

        $content = implode('', $content);

        $args = App::$instance->DispatchEvent(EventsContainer::BundleComplete, (object)['content' => $content, 'exts' => [$ext]]);
        if (isset($args->content)) {
            $content = $args->content;
        }
        $recacheKey = md5($content);

        File::Write($jpweb, $content, true, '777');
        
        self::Export($domain, $ext, $content);

        return str_replace(App::$webRoot, '/', $jpweb . '?' . $recacheKey);
    }

    static function Export(string $domain, string $ext, string $content) {
        try {

            $generateForMobile = App::$config->Query('mobile.bundler.for')->ToArray();
            if(in_array($domain, $generateForMobile)) {
                // надо залить в мобильный проект
                $exportPath = App::$config->Query('mobile.bundler.export')->GetValue();
                
                $paths = App::$config->Query('mobile.bundler.paths')->AsObject();
                foreach($paths as $settings) {
                    if(in_array($ext, (array)$settings->types)) {
                        File::Write($exportPath . $settings->path, $content, true, '777');
                        break;
                    }
                }
                
            }
            
        }
        catch(ConfigException $e) {
            
        }

    }



}
