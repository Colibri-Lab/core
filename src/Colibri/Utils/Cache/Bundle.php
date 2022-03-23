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
    public static function Compile($name, $exts, $path, $exception = array(), $preg = false, $returnContent = false/*, &$returnFiles = []*/)
    {
        $mode = App::$config ? App::$config->Query('mode')->GetValue() : App::ModeDevelopment;
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

            // $f = new File($file)

            $c = File::Read($file)."\n";
            $args = (object)['content' => $c, 'file' => $file];
            $args = App::$instance->DispatchEvent(EventsContainer::BundleFile, $args);
            if (isset($args->content)) {
                $c = $args->content;
            }
            // $returnFiles[$f->name] = [$c, count(explode("\n", $c))]
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

    public static function LastModified($name, $exts, $path, $exception = array(), $preg = false)
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
    public static function CompileFiles($name, $exts, $files, $returnContent = false)
    {
        $mode = App::$config ? App::$config->Query('mode')->GetValue() : App::ModeDevelopment;
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
    protected static function GetNamespaceAssets($path, $exts, $exception = [], $preg = false)
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

        $foundDirectories = $di->Directories($path, '', 'filename', SORT_ASC); 
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
    protected static function GetChildAssets($path, $exts, $exception = [], $preg = false)
    {
        $files = [];

        $di = new Finder();
        $foundFiles = $di->Files($path, '/^[^\.]/');

        $foundDirectories = $di->Directories($path, '', 'filename', SORT_ASC);
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
    public static function Automate($name, $ext, $ar)
    {
        $mode = App::$config ? App::$config->Query('mode')->GetValue() : App::ModeDevelopment;

        $jpweb = App::$webRoot . App::$config->Query('cache')->GetValue() . 'code/' . $name;
        if(File::Exists($jpweb)) {
            if (in_array($mode, [App::ModeDevelopment, App::ModeLocal])) {
                $lastModified = 0;
                foreach ($ar as $settings) {
                    $lastModified = max($lastModified, self::LastModified(isset($settings['name']) ? $settings['name'] : '',
                        isset($settings['exts']) ? $settings['exts'] : array($ext),
                        $settings['path'],
                        isset($settings['exception']) ? $settings['exception'] : array(),
                        isset($settings['preg']) ? $settings['preg'] : false));
                }
                if(filemtime($jpweb) > $lastModified) {
                    return str_replace(App::$webRoot, '/', $jpweb . '?' . md5_file($jpweb));
                }
            } else {
                return str_replace(App::$webRoot, '/', $jpweb . '?' . md5_file($jpweb));
            }
        }

        $content = array();

        $args = App::$instance->DispatchEvent(EventsContainer::BundleStart, (object)['exts' => [$ext]]);
        if (isset($args['content'])) {
            $content[] = $args['content'];
        }

        // $returnFiles = [];
        // ,
        //         $returnFiles
        foreach ($ar as $settings) {
            $content[] = Bundle::Compile(
                isset($settings['name']) ? $settings['name'] : '',
                isset($settings['exts']) ? $settings['exts'] : array($ext),
                $settings['path'],
                isset($settings['exception']) ? $settings['exception'] : array(),
                isset($settings['preg']) ? $settings['preg'] : false,
                true
            );
        }

        // if(!empty($returnFiles)) {
        //     $map = new SourceMap();
        //     $map->file = basename($jpweb);
        //     $offset = 0;
        //     $index = 0;
        //     foreach ($returnFiles as $script => $scdata) {
        //         $c = $scdata[0];
        //         $lines = $scdata[1];
        //         for ($i=0; $i<$lines; ++$i) {
        //             $map->addPosition([
        //                 'generated' => [
        //                     'line' => $offset+$i,
        //                     'column' => 0,
        //                 ],
        //                 'source' => [
        //                     'fileName' => $script,
        //                     'line' => 1,
        //                     'column' => 0
        //                 ],
        //             ]);
        //             $map->sources->setContent($script, $c);
        //         }
        //         $offset += $lines-1;
        //         $index++;
        //     }

        //     $map->save($jpweb.'.map', JSON_PRETTY_PRINT);
        // }
        // '//# sourceMappingURL='.str_replace(App::$webRoot, '/', $jpweb).'.map'."\n".
        $content = implode('', $content);

        $args = App::$instance->DispatchEvent(EventsContainer::BundleComplete, (object)['content' => $content, 'exts' => [$ext]]);
        if (isset($args->content)) {
            $content = $args->content;
        }
        $recacheKey = md5($content);

        File::Write($jpweb, $content, true, '777');

        return str_replace(App::$webRoot, '/', $jpweb . '?' . $recacheKey);
    }

    
}
