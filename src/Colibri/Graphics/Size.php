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
 * Represents a size.
 *
 * This class encapsulates width and height dimensions.
 *
 * @property-read string $style Get text for the style attribute.
 * @property-read string $attributes Get text as attributes.
 * @property-read string $params Get text as query parameters.
 * @property-read bool $isNull Check if the size is null.
 */
class Size
{
    /**
     * The width.
     *
     * @var int
     */
    public int $width;

    /**
     * The height.
     *
     * @var int
     */
    public int $height;

    /**
     * Constructs a new Size instance.
     *
     * @param int $width The width.
     * @param int $height The height.
     */
    public function __construct(int $width = 0, int $height = 0)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Magic getter method.
     *
     * @param string $nm The property name.
     * @return mixed The value of the property.
     */
    public function __get(string $nm): mixed
    {
        $return = null;
        switch ($nm) {
            case "style": {
                $return = ($this->width != 0 ? "width:" . intval($this->width) . "px;" : "") . ($this->height != 0 ? "height:" . intval($this->height) . "px;" : "");
                break;
            }
            case "attributes": {
                $return = ($this->width != 0 ? " width=\"" . intval($this->width) . "\"" : "") . ($this->height != 0 ? " height=\"" . intval($this->height) . "\"" : "");
                break;
            }
            case "params": {
                $return = ($this->width != 0 ? "&w=" . intval($this->width) : "") . ($this->height != 0 ? "&h=" . intval($this->height) : "");
                break;
            }
            case "isNull": {
                $return = ($this->width == 0 && $this->height == 0);
                break;
            }
            default: {
                break;
            }
        }
        return $return;
    }

    /**
     * Transforms the size.
     *
     * @param Size $size The target size.
     * @return Size The transformed size.
     */
    public function TransformTo(Size $size): Size
    {

        $_width = $size->width;
        $_height = $size->height;

        if ($_width == 0 && $_height == 0) {
            return new Size(0, 0);
        } elseif ($_width == 0) {
            $_height = $_height <= $this->height ? $_height : $this->height;
            $_width = $_height / ($this->height / $this->width);
        } elseif ($_height == 0) {
            $_width = ($_width <= $this->width ? $_width : $this->width);
            $_height = $_width / ($this->width / $this->height);
        } elseif ($this->width <= $_width && $this->height <= $_height) {
            $_width = $this->width;
            $_height = $this->height;
        } elseif ($this->width / $_width > $this->height / $_height) {
            $_height = $this->height * ($_width / $this->width);
        } else {
            $_width = $this->width * ($_height / $this->height);
        }

        return new Size($_width, $_height);

    }

    /**
     * Transforms the size to fill a given area.
     *
     * @param Size $size The target size.
     * @return Size The transformed size.
     */
    public function TransformToFill(Size $size): Size
    {

        $_width = $size->width;
        $_height = $size->height;

        if ($_width == 0 && $_height == 0) {
            return new Size(0, 0);
        } elseif ($_width == 0) {
            $_height = ($_height <= $this->height ? $_height : $this->height);
            $_width = $_height / ($this->height / $this->width);
        } elseif ($_height == 0) {
            $_width = ($_width <= $this->width ? $_width : $this->width);
            $_height = $_width / ($this->width / $this->height);
        } elseif ($this->width <= $_width && $this->height <= $_height) {
            $_width = $this->width;
            $_height = $this->height;
        } elseif ($this->width / $_width > $this->height / $_height) {
            $_width = $this->width * ($_height / $this->height);
        } else {
            $_height = $this->height * ($_width / $this->width);
        }

        return new Size($_width, $_height);
    }

    /**
     * Expands the size.
     *
     * @param int $w The width increment.
     * @param int $h The height increment.
     * @return void
     */
    public function Expand(int $w, int $h): void
    {
        $this->width += $w;
        $this->height += $h;
    }

}
