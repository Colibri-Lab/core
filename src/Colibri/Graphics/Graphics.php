<?php

/**
 * Graphics
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Graphics
 */
namespace Colibri\Graphics;

use Colibri\IO\FileSystem\File;
use Colibri\Common\RandomizationHelper;
use Colibri\Utils\ExtendedObject;

/**
 * Работа с изображениями
 *
 * @property-read bool $isvalid
 * @property-read Size $size
 * @property string $type
 * @property-read string $data
 * @property-read int $transparency
 * @property-read string $name
 *
 */
class Graphics
{
    /**
     * Изображение
     *
     * @var mixed
     */
    private $_img;

    /**
     * Размеры
     *
     * @var Size
     */
    private $_size;

    /**
     * Тип изображения
     *
     * @var string
     */
    private $_type;

    /**
     * Файл, где хранится изображение
     *
     * @var string
     */
    private $_file;
    
    /**
     * История
     *
     * @var array
     */
    private $_history = array();
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->_img = null;
        $this->_size = new Size(0, 0);
        $this->_type = 'unknown';
    }
    
    /**
     * Деструктор
     */
    public function __destruct()
    {
        if (is_resource($this->_img)) {
            @\imagedestroy($this->_img);
        }
    }
    
    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        $return = null;
        switch (strtolower($property)) {
            case 'isvalid':{
                $return = !is_null($this->_img);
                break;
            }
            case 'size':{
                $return = $this->_size;
                break;
            }
            case 'type':{
                $return = $this->_type;
                break;
            }
            case 'data':{
                $return = $this->_getImageData();
                break;
            }
            case 'transparency':{
                if (!is_null($this->_img)) {
                    $return = @\imagecolortransparent($this->_img);
                }
                break;
            }
            case 'name':{
                $return = $this->_file;
                break;
            }
            default: {
                break;
            }
        }
        return $return;
    }
    
    /**
     * Сеттер
     *
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        if (strtolower($property) == 'type') {
            $this->_type = $value;
        }
    }
    
    /**
     * Загружает изображение из строки
     *
     * @param string $data
     * @return void
     */
    public function LoadFromData($data)
    {
        $this->_file = basename(RandomizationHelper::Mixed(20));
        $this->_img = @\imagecreatefromstring($data);
        $this->_size = new Size(\imagesx($this->_img), \imagesy($this->_img));
        $this->_history = array();
        $this->_safeAlpha();
    }
    
    /**
     * Загружает изображение из файла
     *
     * @param string $file
     * @return void
     */
    public function LoadFromFile($file)
    {
        $this->_file = basename($file);
        $pp = explode('.', $file);
        $this->_type = strtolower($pp[count($pp) - 1]);
        
        switch ($this->_type) {
            case 'png':
                $this->_img = \imagecreatefrompng($file);
                break;
            case 'gif':
                $this->_img = \imagecreatefromgif($file);
                break;
            case 'jpg':
            case 'jpeg':
                $this->_img = \imagecreatefromjpeg($file);
                break;
            default: {
                break;
            }
        }

        $this->_size = new Size(\imagesx($this->_img), \imagesy($this->_img));
        $this->_history = array();
        $this->_safeAlpha();
    }
    
    /**
     * Создает пустое изображение
     *
     * @param Size $size
     * @return void
     */
    public function LoadEmptyImage($size)
    {
        $this->_type = "unknown";
        $this->_img = \imagecreatetruecolor($size->width, $size->height);
        $this->_size = $size;
        $this->_history = array();
        $this->_safeAlpha();
    }
    
    /**
     * Меняет размер изображения
     *
     * @param Size $size
     * @return void
     */
    public function Resize($size)
    {
        if ($this->isValid) {
            $newImage = \imagecreatetruecolor($size->width, $size->height);
            \imagealphablending($newImage, false);
            \imagesavealpha($newImage, true);
            ImageCopyResampled($newImage, $this->_img, 0, 0, 0, 0, $size->width, $size->height, $this->_size->width, $this->_size->height);
            ImageDestroy($this->_img);
            $this->_img = $newImage;
            $this->_size = $size;
            $this->_history[] = array('operation' => 'resize', 'postfix' => 'resized-'.$size->width.'x'.$size->height);
        }
    }
    
    /**
     * Переворачивает изображение
     *
     * @param integer $degree
     * @return void
     */
    public function Rotate($degree = 90)
    {
        $this->_img = \imagerotate($this->_img, $degree, -1);
        \imagealphablending($this->_img, true);
        \imagesavealpha($this->_img, true);
    }
    
    /**
     * Вырезает кусок изображения
     *
     * @param Size $size
     * @param Point $start
     * @return void
     */
    public function Crop($size, $start = null)
    {
        if ($this->isValid) {
            if (is_null($start)) {
                $start = new Point(0, 0);
            }
            $newImage = ImageCreateTrueColor($size->width, $size->height);
            ImageCopyResampled(
                $newImage,
                $this->_img,
                0,
                0,
                $start->x,
                $start->y,
                $size->width,
                $size->height,
                $size->width,
                $size->height
            );
            ImageDestroy($this->_img);
            $this->_img = $newImage;
            $this->size = $size;
            
            $this->_history[] = array('operation' => 'crop', 'postfix' => 'croped-'.$start->x.'x'.$start->y.'.'.$size->width.'x'.$size->height);
        }
    }

    /**
     * Применяет фильтр
     *
     * @param integer $filter
     * @param integer $arg1
     * @param integer $arg2
     * @param integer $arg3
     * @return void
     */
    public function ApplyFilter($filter, $arg1 = 0, $arg2 = 0, $arg3 = 0)
    {
        $return = null;
        switch ($filter) {
            case IMG_FILTER_NEGATE:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'negate');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_GRAYSCALE:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'grayscale');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_BRIGHTNESS:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'brightness-'.$arg1);
                $return = \imagefilter($this->_img, $filter, $arg1);
                break;
            }
            case IMG_FILTER_CONTRAST:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'contrast-'.$arg1);
                $return = \imagefilter($this->_img, $filter, $arg1);
                break;
            }
            case IMG_FILTER_COLORIZE:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'colorize-'.$arg1.'x'.$arg2.'x'.$arg3);
                $return = \imagefilter($this->_img, $filter, $arg1, $arg2, $arg3);
                break;
            }
            case IMG_FILTER_EDGEDETECT:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'edgedetect');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_EMBOSS:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'emboss');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_GAUSSIAN_BLUR:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'gausian-blur');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_SELECTIVE_BLUR:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'blur');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_MEAN_REMOVAL:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'mean-removal');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_SMOOTH:{
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'smooth-'.$arg1);
                $return = \imagefilter($this->_img, $filter, $arg1);
                break;
            }
            default: {
                break;
            }
        }
        return $return;
    }
    
    /**
     * Сохраняет в файл
     *
     * @param string $file
     * @return void
     */
    public function Save($file)
    {
        $fi = new File($file);
        switch ($fi->extension) {
            case 'png':
                \imagepng($this->_img, $file);
                break;
            case 'gif':
                \imagegif($this->_img, $file);
                break;
            case 'jpg':
            case 'jpeg':
                \imagejpeg($this->_img, $file);
                break;
            default:
                \imagegd2($this->_img, $file);
                break;
        }
    }
    
    /**
     * Устанавливает алфа канал
     *
     * @return void
     */
    private function _safeAlpha()
    {
        // save alpha
        \imagealphablending($this->_img, 1);
        \imagesavealpha($this->_img, 1);
    }
    
    /**
     * Возвращает данные изображения
     *
     * @return string
     */
    private function _getImageData()
    {
        $tempFile = tempnam(null, null);
        switch ($this->_type) {
            case 'png':
                \imagepng($this->_img, $tempFile);
                break;
            case 'gif':
                \imagegif($this->_img, $tempFile);
                break;
            case 'jpg':
            case 'jpeg':
                \imagejpeg($this->_img, $tempFile);
                break;
            default:
                \imagegd2($this->_img, $tempFile);
                break;
        }
        
        $c = file_get_contents($tempFile);
        unlink($tempFile);
        return $c;
    }
    
    /**
     * Возвращает информацию об изображении
     *
     * @param string $path
     * @return ExtendedObject
     */
    public static function Info($path)
    {
        list($width, $height, $type, $attr) = \getimagesize($path);
        $o = new ExtendedObject();
        $o->size = new Size($width, $height);
        $o->type = $type;
        $o->attr = $attr;
        return $o;
    }
    
    /**
     * Статический конструктор
     *
     * @param string $data
     * @return Graphics
     */
    public static function Create($data)
    {
        $g = new Graphics();
        
        if ($data instanceof Size) {
            $g->LoadEmptyImage($data);
        } elseif (File::Exists($data)) {
            $g->LoadFromFile($data);
        } else {
            $g->LoadFromData($data);
        }
        
        return $g;
    }
}


