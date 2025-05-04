<?php

/**
 * Config
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils\Cache
 *
 */

namespace Colibri\Utils\Cache;

use axy\sourcemap\SourceMap;
use Colibri\App;
use Colibri\Events\EventsContainer;
use Colibri\IO\FileSystem\File;
use Colibri\IO\FileSystem\Directory;
use Colibri\IO\FileSystem\Exception as FileSystemException;
use Colibri\IO\FileSystem\Finder;
use Colibri\Utils\Debug;
use Colibri\Utils\Config\ConfigException;

/**
 * Utility class for creating cache bundles of styles and scripts.
 *
 */
class Bundle
{
    /**
     * Compiles scripts and styles.
     *
     * @param string $name The name of the bundle
     * @param array $exts File extensions to include
     * @param string $path The path to search for assets
     * @param array $exception Directories to exclude from the search
     * @param bool $preg Use regular expressions in the search
     * @param bool $returnContent Whether to return the compiled content instead of writing to a file
     * @return string The path to the compiled bundle
     */
    public static function Compile(string $name, array $exts, string $path, array $exception = array(), bool $preg = false, bool $returnContent = false): string
    {
        $jpweb = App::$webRoot . App::$config->Query('cache')->GetValue() . 'code/' . $name;
        if (!$returnContent && !App::$isDev && File::Exists($jpweb)) {
            return str_replace(App::$webRoot, '/', $jpweb);
        }

        if (File::Exists($path) && !File::IsDirectory($path)) {
            $files = [$path];
        } else {
            $namespaces = self::GetNamespaceAssets($path, $exts, $exception, $preg);
            $files = self::GetChildAssets($path, $exts, $exception, $preg);
            $files = array_merge($namespaces, $files);
        }

        $content = '';
        foreach ($files as $file) {
            if (!File::Exists($file)) {
                throw new FileSystemException('file not exists ' . $file);
            }

            $c = File::Read($file) . "\n";
            $args = (object) ['content' => $c, 'file' => $file];
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
     * Retrieves the last modified timestamp of the bundle.
     *
     * @param string $name The name of the bundle
     * @param array $exts File extensions to include
     * @param string $path The path to search for assets
     * @param array $exception Directories to exclude from the search
     * @param bool $preg Use regular expressions in the search
     * @return int The last modified timestamp
     */
    public static function LastModified(string $name, array $exts, string $path, array $exception = array(), bool $preg = false): int
    {
        $lastModified = 0;

        if (File::Exists($path) && !File::IsDirectory($path)) {
            $files = [$path];
        } else {
            $namespaces = self::GetNamespaceAssets($path, $exts, $exception, $preg);
            $files = self::GetChildAssets($path, $exts, $exception, $preg);
            $files = array_merge($namespaces, $files);
        }


        foreach ($files as $file) {
            if (!File::Exists($file)) {
                throw new FileSystemException('file not exists ' . $file);
            }
            $lastModified = max($lastModified, filemtime($file));
        }
        return $lastModified;
    }

    /**
     * Compiles scripts and styles from specified files.
     *
     * @param string $name The name of the bundle
     * @param array $exts File extensions to include
     * @param array $files Array of file paths to compile
     * @param bool $returnContent Whether to return the compiled content instead of writing to a file
     * @return string The path to the compiled bundle
     */
    public static function CompileFiles(string $name, array $exts, array $files, bool $returnContent = false): string
    {
        $jpweb = App::$webRoot . App::$config->Query('cache')->GetValue() . 'code/' . $name;
        if (!$returnContent && !App::$isDev && File::Exists($jpweb)) {
            return str_replace(App::$webRoot, '/', $jpweb);
        }

        $content = '';
        foreach ($files as $file) {
            if (!File::Exists($file)) {
                throw new FileSystemException('file not exists ' . $file);
            }

            $c = File::Read($file) . "\n\n";
            $args = (object) ['content' => $c, 'file' => $file];
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
     * Retrieves assets from namespaces.
     *
     * @param string $path The path to search for assets
     * @param array $exts File extensions to include
     * @param array $exception Directories to exclude from the search
     * @param bool $preg Use regular expressions in the search
     * @return array Array of asset file paths
     */
    public static function GetNamespaceAssets(string $path, array $exts, array $exception = [], bool $preg = false): array
    {
        $files = [];

        $di = new Finder();
        $foundFiles = $di->Files($path, '/^\..*/', 'name', SORT_ASC);

        foreach ($foundFiles as $file) {
            /** @var File $file */
            if ($file->extension && !in_array($file->name, $exception) && in_array($file->extension, $exts)) {
                $files[] = $file->path;
            }
        }

        $foundDirectories = $di->Directories($path, 'name', SORT_ASC);
        foreach ($foundDirectories as $dir) {
            /** @var Directory $dir */

            if (!in_array($dir->name, $exception)) {
                $files = array_merge($files, self::GetNamespaceAssets($dir->path . '/', $exts, $exception, $preg));
            }
        }

        return $files;
    }

    /**
     * Retrieves child assets.
     *
     * @param string $path The path to search for assets
     * @param array $exts File extensions to include
     * @param array $exception Directories to exclude from the search
     * @param bool $preg Use regular expressions in the search
     * @return array Array of asset file paths
     */
    public static function GetChildAssets(string $path, array $exts, array $exception = [], bool $preg = false): array
    {
        $files = [];

        $di = new Finder();
        $foundFiles = $di->Files($path, '/^[^\.]/', 'name', SORT_ASC);
        $foundDirectories = $di->Directories($path, 'name', SORT_ASC);

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
     * Automates the process of bundling scripts and styles.
     *
     * @param string $domain The domain name
     * @param string $name The name of the bundle
     * @param string $ext The file extension to include
     * @param array $ar Array of settings for bundling assets
     * @param string|null $useDomainsInUrls The domain to use in URLs
     * @return string The path to the compiled bundle
     */
    public static function Automate(
        string $domain,
        string $name,
        string $ext,
        array $ar,
        ?string $useDomainsInUrls = null
    ): string {

        $name = $domain . '.' . $name;

        $jpweb = App::$webRoot . App::$config->Query('cache')->GetValue() . 'code/' . $name;
        if (File::Exists($jpweb)) {
            if (App::$isDev) {
                $lastModified = 0;
                foreach ($ar as $settings) {
                    if (!isset($settings['path']) || !$settings['path']) {
                        continue;
                    }
                    $lastModified = max(
                        $lastModified,
                        self::LastModified(
                            isset($settings['name']) ? $settings['name'] : '',
                            isset($settings['exts']) ? $settings['exts'] : [$ext],
                            $settings['path'],
                            isset($settings['exception']) ? $settings['exception'] : array(),
                            isset($settings['preg']) ? $settings['preg'] : false
                        )
                    );
                }
                if (filemtime($jpweb) > $lastModified) {
                    return str_replace(App::$webRoot, '/', $jpweb . '?' . md5_file($jpweb));
                }
            } else {
                return str_replace(App::$webRoot, '/', $jpweb . '?' . md5_file($jpweb));
            }
        }

        $content = [];

        $args = App::$instance->DispatchEvent(EventsContainer::BundleStart, (object) ['exts' => [$ext]]);
        if (isset($args['content'])) {
            $content[] = $args['content'];
        }

        foreach ($ar as $settings) {
            if (!isset($settings['path']) || !$settings['path']) {
                continue;
            }
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

        $args = App::$instance->DispatchEvent(EventsContainer::BundleComplete, (object) ['content' => $content, 'exts' => [$ext]]);
        if (isset($args->content)) {
            $content = $args->content;
        }
        $recacheKey = md5($content);

        if ($useDomainsInUrls) {
            $content = str_replace('url(', 'url(' . $useDomainsInUrls, $content);
        }

        File::Write($jpweb, $content, true, '777');

        self::Export($domain, $ext, $name, $content);

        return str_replace(App::$webRoot, '/', $jpweb . '?' . $recacheKey);
    }

    /**
     * Exports the compiled bundle.
     *
     * @param string $domain The domain name
     * @param string $ext The file extension
     * @param string $name The name of the bundle
     * @param string $content The compiled content
     */
    public static function Export(string $domain, string $ext, string $name, string $content)
    {
        try {

            $generateForMobile = App::$config->Query('mobile.bundler.for')->ToArray();
            if (in_array($domain, $generateForMobile)) {
                // надо залить в мобильный проект
                $exportPath = App::$config->Query('mobile.bundler.export')->GetValue();
                $base = App::$config->Query('mobile.bundler.base')->GetValue();
                $content = str_replace('url("/', 'url("' . $base . '/', $content);


                $paths = App::$config->Query('mobile.bundler.paths')->ToArray();
                foreach ($paths as $settings) {
                    if (in_array($ext, (array) $settings['types'])) {
                        $bundle = $name;
                        if($settings['convert']) {
                            $res = preg_match_all(
                                '/'.$settings['convert']['from'].'/',
                                $name,
                                $matches,
                                PREG_SET_ORDER
                            );
                            if($res > 0) {
                                $bundle = $settings['convert']['to'];
                                foreach($matches[0] as $index => $match) {
                                    $bundle = str_replace('$' . $index, $match, $bundle);
                                }
                            }
                        }
                        File::Write($exportPath . $settings['path'] . $bundle, $content, true, '777');
                        break;
                    }
                }

            }

        } catch (ConfigException $e) {

        }

    }



}
