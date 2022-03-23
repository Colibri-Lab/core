<?php

/**
 * Класс менеджера Assets-ов
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Cache\Assets
 * @version 1.0.0
 * 
 */

namespace Colibri\Utils\Cache\Assets;

use Colibri\Web\Request;
use Colibri\Web\Response;
use Colibri\App;

/**
 * Менеджер ассетов
 * 
 * AssetsManager::AddContainer('assets.styles', AssetsTypes::Styles)
 * AssetsManager::AddContainer('assets.scripts', AssetsTypes::Javascript)
 * AssetsManager::AddBlock('assets.styles', 'file path relative to root or content')
 * 
 * @property-read Assets[] $assets
 * 
 * @testFunction testAssetsManager
 */
class AssetsManager
{

    /**
     * Синглтон
     *
     * @var AssetsManager
     */
    public static $instance = null;


    /**
     * Место расположение кэша на диске
     * ! НЕ ИСПОЛЬЗОВАТЬ
     * @deprecated
     * @var string
     */
    public static $cacheRoot = '';


    /**
     * Храним и собираем assets
     *
     * @var array
     */
    private $_assets;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->_assets = [];
    }

    /**
     * Статический конструктор
     *
     * @return AssetsManager
     * @testFunction testAssetsManagerCreate
     */
    public static function Create()
    {

        if (!self::$instance) {
            self::$instance = new AssetsManager();
            self::$cacheRoot = App::$webRoot . App::$config->Query('cache')->GetValue();
        }

        return self::$instance;
    }

    /**
     * Гет
     *
     * @param string $property
     * @return mixed
     * @testFunction testAssetsManager__get
     */
    public function __get($property)
    {

        $property = strtolower($property);
        if ($property == 'assets') {
            return $this->_assets;
        }

        return isset($this->_assets[$property]) ? $this->_assets[$property] : null;
    }

    /**
     * Создаем контейнер ассетов
     *
     * @param string $name
     * @param string $type
     * @return Assets
     * @testFunction testAssetsManagerAdd
     */
    public function Add($name, $type = AssetsTypes::Javascript)
    {

        if (!isset($this->_assets[$name])) {
            $this->_assets[$name] = new Assets($name, $type);
        }

        return $this->_assets[$name];
    }

    /**
     * Создаем контейнер ассетов
     *
     * @param string $name
     * @param string $type
     * @return Assets
     * @testFunction testAssetsManagerAddContainer
     */
    public static function AddContainer($name, $type = AssetsTypes::Javascript)
    {
        return self::Create()->Add($name, $type);
    }

    /**
     * Добавляет данные в нужный ассет
     *
     * @param string $assetsName
     * @param string $block
     * @return void
     * @testFunction testAssetsManagerAddBlock
     */
    public static function AddBlock($assetsName, $block)
    {
        $asset = self::AddContainer($assetsName);
        $asset->AddBlock($block);
    }

    /**
     * Добавляет данные из массива
     *
     * @param string $assetsName
     * @param string[] $blocks
     * @return void
     * @testFunction testAssetsManagerAddBlocks
     */
    public static function AddBlocks($assetsName, $blocks)
    {
        $asset = self::AddContainer($assetsName);
        $asset->AddBlocks($blocks);
    }

    /**
     * Собрать ассеты
     *
     * @param string $name
     * @param boolean $recompile
     * @param boolean $echo 
     * @return void
     * @testFunction testAssetsManagerCompile
     */
    public static function Compile($name, $echo = true)
    {

        $asset = self::Create()->$name;
        if (!$asset) {
            throw new AssetsException('Block is not found');
        }

        $content = $asset->Compile();

        // выводим
        if($echo) {
            Response::Create()->Write($content);
        }
        else {
            return $content;
        }
    }

    /**
     * Собрать все ассеты
     *
     * @param boolean $recompile
     * @return void
     * @testFunction testAssetsManagerAutomate
     */
    public static function Automate()
    {

        $assets = self::Create()->assets;
        foreach ($assets as $asset) {
            $content = $asset->Compile() . "\n\n";
            // выводим
            Response::Create()->Write($content);
        }
    }
}
