<?php

/**
 * Класс для работы со всякой клиентской шнягой
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Cache\Assets
 * @version 1.0.0
 *
 */

namespace Colibri\Utils\Cache\Assets;

use Colibri\App;
use Colibri\Common\Encoding;
use Colibri\Events\EventsContainer;
use Colibri\Events\TEventDispatcher;
use Colibri\IO\FileSystem\Directory;
use Colibri\IO\FileSystem\File;
use MatthiasMullie\Minify;
use Colibri\Utils\Debug;


/**
 * Класс автоматизации ассетов
 * @testFunction testAssets
 */
class Assets
{

    use TEventDispatcher;

    /**
     * Храним все, вхождения для последующего вывода, ключ => массив из блоков/файлов
     *
     * @var array
     */
    private $_blocks;

    /**
     * Тип
     *
     * @var string
     */
    private $_type;

    /**
     * Название
     *
     * @var string
     */
    private $_name;

    /**
     * Конструктопр
     */
    public function __construct($name, $type = AssetsTypes::Javascript)
    {
        $this->_name = $name;
        $this->_type = $type;
        $this->_blocks = [];
    }

    /**
     * Статический конструктор
     *
     * @return Assets
     */
    /**
     * @testFunction testAssetsCreate
     */
    public static function Create($name, $type = AssetsTypes::Javascript)
    {
        return new Assets($name, $type);
    }

    /**
     * Добавляет блок
     * Добавить можно как файл так и кусок стилей или скриптов
     *
     * @param string $block
     * @return void
     * @testFunction testAssetsAddBlock
     */
    public function AddBlock($block, $charset = 'utf-8', $handler = null)
    {
        if($charset != Encoding::UTF8) {
            $block = Encoding::Convert($block, Encoding::UTF8, $charset);
        }
        $this->_blocks[] = $handler ? [$handler, $block] : $block;
    }

    /**
     * Добавляет блоки из массива
     * Добавить можно как файлы так и куски стилей или скриптов, но строго одного типа,
     * т.е. если это файлы, тогда файлы, если блоки то блоки
     *
     * @param string[] $blocks
     * @return void
     * @testFunction testAssetsAddBlocks
     */
    public function AddBlocks($blocks, $charset = 'utf-8')
    {
        foreach ($blocks as $block) {
            if($charset != Encoding::UTF8) {
                $block = Encoding::Convert($block, Encoding::UTF8, $charset);
            }
            $this->_blocks[] = $block;
        }
    }

    /**
     * Получить URI кэша
     *
     * @return string
     * @testFunction testAssets_cacheUrl
     */
    private function _cacheUrl($etag = '')
    {
        $path = '/' . substr($etag, 0, 2) . '/' . substr($etag, 2, 2) . '/';
        $cacheUrl = App::$config->Query('cache')->GetValue();     

        return '/' . $cacheUrl . $this->_type . $path . $this->_name . '.' . $etag . '.' . $this->_type;
    }

    /**
     * Проверка существования кэша
     *
     * @return boolean
     * @testFunction testAssets_cacheExists
     */
    private function _cacheExists($etag = '')
    {
        $cachePath = App::$webRoot . $this->_cacheUrl($etag);
        return File::Exists($cachePath);
    }

    /**
     * Формирует хэш файлов
     *
     * @return string
     * @testFunction testAssets_cacheETag
     */
    private function _cacheETag()
    {
        $mtimes = [];
        $filesLoaded = [];
        foreach ($this->_blocks as $block) {
            if (File::Exists(App::$webRoot . $block)) {
                // Проверяем не загружали ли, и если загружаели не загружаем
                if (in_array(App::$webRoot . $block, $filesLoaded)) {
                    continue;
                }
                $mtimes[] = filemtime(App::$webRoot . $block);
            } else {
                $mtimes[] = time();
            }
        }
        return md5(implode('', $mtimes));
    }

    /**
     * Создает кэш
     *
     * @return string
     * @testFunction testAssets_createCache
     */
    private function _createCache($etag = '')
    {

        $filesLoaded = [];

        $cacheContent = [];
        foreach ($this->_blocks as $block) {
            $fileContent = '';
            if (File::Exists(App::$webRoot . $block)) {
                // Проверяем не загружали ли, и если загружаели не загружаем
                if (in_array(App::$webRoot . $block, $filesLoaded)) {
                    continue;
                }

                $content = File::Read(App::$webRoot . $block);
                $encoding = Encoding::Detect($content);
                if($encoding != Encoding::UTF8) {
                    $content = Encoding::Convert($content, Encoding::UTF8, Encoding::CP1251);
                }

                $fileContent = "\n\n\n/* " . (App::$webRoot . $block) . ", original encoding: " . $encoding . " */ \n\n\n" . $content . "\n\n\n";

            } else {
                $encoding = Encoding::Detect($block);
                if($encoding != Encoding::UTF8) {
                    $block = Encoding::Convert($block, Encoding::UTF8, Encoding::CP1251);
                }
                $fileContent = "\n\n\n/* Script block, original encoding: " . $encoding . " */\n\n\n" . $block . "\n\n\n";
            }

            $args = $this->DispatchEvent(EventsContainer::AssetsCompiled, (object) ['name' => $this->_name, 'type' => $this->_type, 'content' => $fileContent]);
            if(isset($args->content)) {
                $fileContent = $args->content;
            }

            $cacheContent[] = $fileContent;
        }

        $cachePath = App::$webRoot . $this->_cacheUrl($etag);

        $cacheContent = implode('', $cacheContent);

        if ($this->_type == AssetsTypes::Styles && App::$config->Query('mode')->GetValue() === App::ModeRelease) {
            try {
                $minifier = new Minify\CSS();
                $minifier->add($cacheContent);
                $cacheContent = $minifier->minify();
            } catch (\Exception$e) {
                echo $e->getMessage();
            }
        }
        
        if($this->_type == AssetsTypes::Styles) {
            $cacheContent = '@charset "utf-8";'."\n\n\n".$cacheContent;
        }

        if (File::Exists($cachePath)) {
            File::Delete($cachePath);
        } 
        File::Write($cachePath, $cacheContent, true, '777');

        return $this->_cacheUrl($etag);
    }

    /**
     * Скомпилировать блоки и закэшировать
     *
     * @param boolean $recompile
     * @return string
     * @throws AssetsException
     * @testFunction testAssetsCompile
     */
    public function Compile()
    {

        if (count($this->_blocks) === 0) {
            return '';
        }

        // поднимаем событие перед компиляцией, тут можно добавить блоки
        $args = (object) ['name' => $this->_name, 'type' => $this->_type, 'blocks' => $this->_blocks];
        $this->DispatchEvent(EventsContainer::AssetsCompiling, $args);
        $this->_blocks = $args->blocks;

        $creationTime = 0;

        $etag = $this->_cacheETag();
        if (!$this->_cacheExists($etag)) {
            // Берем текущее время для последующего рассчета времени обработки
            $time = microtime(true);

            $cacheUrl = $this->_createCache($etag);

            // Рассчитываем время обработки
            $creationTime = microtime(true) - $time;
        } else {
            $cacheUrl = $this->_cacheUrl($etag);
        }

        $this->DispatchEvent(EventsContainer::AssetsCompiled, (object) ['name' => $this->_name, 'type' => $this->_type, 'cacheUrl' => $cacheUrl]);

        if ($this->_type == AssetsTypes::Javascript) {
            return '<!-- created at: ' . $creationTime . ' ms  --><script type="text/javascript" src="' . $cacheUrl . '" charset="utf-8"></script>';
        } else if ($this->_type == AssetsTypes::Styles) {
            return '<!-- created at: ' . $creationTime . ' ms  --><link type="text/css" rel="stylesheet" href="' . $cacheUrl . '" charset="utf-8" />';
        }

        throw new AssetsException('This unreachable code can be reached');
    }
}
