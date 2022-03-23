<?php

/**
 * Graphics
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Graphics
 */
namespace Colibri\Graphics;

/**
 * Шрифт
 *
 * @property-read string $file название файла шрифта
 * @property-read string $path путь к шрифтам
 * @property-read int $angle угол
 * @property-read string $src полный путь к шрифту
 * @property-read int $size размер/кегль
 *
 */
class Font
{
    
    /**
     * Название шрифта
     *
     * @var string
     */
    private $_file;
    /**
     * Путь к файлу
     *
     * @var string
     */
    private $_path;
    /**
     * Угол наклона
     *
     * @var int
     */
    private $_angle;
    /**
     * Размер
     *
     * @var int
     */
    private $_fontSize;
    
    /**
     * Конструктор
     *
     * @param string $fontFile
     * @param string $path
     * @param integer $fontSize
     * @param integer $angle
     */
    public function __construct($fontFile, $path = '', $fontSize = 0, $angle = 0)
    {
        global $core;
        $this->_file = $fontFile;
        $this->_path = $path;

        $this->_fontSize = $fontSize;
        if ($this->_fontSize == 0) {
            $this->_fontSize = 12;
        }

        $this->_angle = $angle;
        
        if ($this->_file == '') {
            $this->_file = basename($this->_path);
            $this->_path = dirname($this->_path);
        }
    }
    
    /**
     * Геттер
     *
     * @param string $prop
     * @return mixed
     */
    public function __get($prop)
    {
        $return = null;
        switch ($prop) {
            case "file":{
                $return = $this->_file;
                break;
            }
            case "path":{
                $return = $this->_path;
                break;
            }
            case "angle":{
                $return = $this->_angle;
                break;
            }
            case "src": {
                $return = $this->_path."/".$this->_file;
                break;
            }
            case "size": {
                $return = $this->_fontSize;
                break;
            }
            default: {
                break;
            }
        }
        return $return;
    }
    
    /**
     * Возвращает размер области вывода текста
     * @param string $text
     */
    public function MeasureText($text)
    {
        $ar = imagettfbbox($this->_fontSize, $this->_angle, $this->_path."/".$this->_file, $text);

        $r = new Quadro();
        $r->lowerleft->x = $ar[0];
        $r->lowerleft->y = $ar[1];
        
        $r->lowerright->x = $ar[2];
        $r->lowerright->y = $ar[3];
        
        $r->upperright->x = $ar[4];
        $r->upperright->y = $ar[5];
        
        $r->upperleft->x = $ar[6];
        $r->upperleft->y = $ar[7];
        
        return $r;
    }

    /**
     * Возвращает размер области вывода текста
     * @param string $text
     * @param Point $startAt
     * @param Size $size
     */
    public function InscribeText($text, &$startAt, &$size)
    {
        $rect = imagettfbbox($this->_fontSize, 0, $this->_path."/".$this->_file, $text.'|');
        if (0 == $this->_angle) {
            $size->height = $rect[1] - $rect[7];
            $size->width = $rect[2] - $rect[0];
            $startAt->x = -1 - $rect[0];
            $startAt->y = -1 - $rect[7];
        } else {
            $rad = deg2rad($this->_angle);
            $sin = sin($rad);
            $cos = cos($rad);
            if ($this->_angle > 0) {
                $tmp = $rect[6] * $cos + $rect[7] * $sin;
                $startAt->x = -1 - round($tmp);
                $size->width = round($rect[2] * $cos + $rect[3] * $sin - $tmp);
                $tmp = $rect[5] * $cos - $rect[4] * $sin;
                $startAt->y = -1 - round($tmp);
                $size->height = round($rect[1] * $cos - $rect[0] * $sin - $tmp);
            } else {
                $tmp = $rect[0] * $cos + $rect[1] * $sin;
                $startAt->x = abs(round($tmp));
                $size->width = round($rect[4] * $cos + $rect[5] * $sin - $tmp) + 2;
                $tmp = $rect[7] * $cos - $rect[6] * $sin;
                $startAt->y = abs(round($tmp));
                $size->height = round($rect[3] * $cos - $rect[2] * $sin - $tmp) + 5;
            }
        }
    }
}


