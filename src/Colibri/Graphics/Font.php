<?php

/**
 * Graphics
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Graphics
 */
namespace Colibri\Graphics;

/**
 * Represents a font.
 *
 * This class provides properties and methods for handling fonts.
 *
 * @property-read string $file The name of the font file.
 * @property-read string $path The path to the fonts.
 * @property-read int $angle The angle of the font.
 * @property-read string $src The full path to the font.
 * @property-read int $size The font size.
 */
class Font
{

    /**
     * The font name.
     *
     * @var string
     */
    private string $_file;

    /**
     * The file path.
     *
     * @var string
     */
    private string $_path;

    /**
     * The tilt angle.
     *
     * @var int
     */
    private int $_angle;

    /**
     * The font size.
     *
     * @var int
     */
    private int $_fontSize;

    /**
     * Constructs a new Font instance.
     *
     * @param string $fontFile The font file name.
     * @param string $path The path to the font files.
     * @param int $fontSize The font size.
     * @param int $angle The angle of the font.
     */
    public function __construct(string $fontFile, string $path = '', int $fontSize = 0, int $angle = 0)
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
     * Magic getter method.
     *
     * @param string $prop The property name.
     * @return mixed The value of the property.
     */
    public function __get(string $prop): mixed
    {
        $return = null;
        switch ($prop) {
            case "file": {
                    $return = $this->_file;
                    break;
                }
            case "path": {
                    $return = $this->_path;
                    break;
                }
            case "angle": {
                    $return = $this->_angle;
                    break;
                }
            case "src": {
                    $return = $this->_path . "/" . $this->_file;
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
     * Measures the size of the text area.
     *
     * @param string $text The text to measure.
     * @return Rect The size of the text area.
     */
    public function MeasureText(string $text): Rect
    {
        $ar = imagettfbbox($this->_fontSize, $this->_angle, $this->_path . "/" . $this->_file, $text);

        $r = new Rect();
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
     * Inscribes text within a given area.
     *
     * @param string $text The text to inscribe.
     * @param Point $startAt The starting point for the text.
     * @param Size $size The size of the area.
     * @return void
     */
    public function InscribeText(string $text, Point &$startAt, Size &$size)
    {
        $rect = imagettfbbox($this->_fontSize, 0, $this->_path . "/" . $this->_file, $text . '|');
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