<?php

/**
 * Graphics
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Graphics
 */

namespace Colibri\Graphics;

use Colibri\IO\FileSystem\File;
use Colibri\Common\RandomizationHelper;
use Colibri\Utils\ExtendedObject;

/**
 * Handles image manipulation.
 *
 * This class provides functionalities for working with images.
 * 
 * @class
 *
 * @property-read bool $isValid Indicates whether the image is valid.
 * @property-read Size $size The size of the image.
 * @property string $type The type of the image.
 * @property-read string $data The image data.
 * @property-read int $transparency The transparency level of the image.
 * @property-read string $name The filename where the image is stored.
 */
class Graphics
{
    /**
     * The image resource.
     *
     * @var mixed
     * @private
     */
    private mixed $_img;

    /**
     * The size of the image.
     *
     * @var Size|null
     * @private
     */
    private ?Size $_size = null;

    /**
     * The type of the image.
     *
     * @var string
     * @private
     */
    private string $_type;

    /**
     * The filename where the image is stored.
     *
     * @var string
     * @private
     */
    private string $_file;

    /**
     * The history of image operations.
     *
     * @var array
     * @private
     */
    private array $_history = array();

    /**
     * Constructor.
     * @constructor
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * ```
     */
    public function __construct()
    {
        $this->_img = null;
        $this->_size = new Size(0, 0);
        $this->_type = 'unknown';
    }

    /**
     * Destructor.
     * @destructor
     * @public
     */
    public function __destruct()
    {
        if (is_resource($this->_img)) {
            @\imagedestroy($this->_img);
        }
    }

    /**
     * Magic getter method.
     *
     * @param string $property The property name.
     * @return mixed The value of the property.
     * @magic
     * @public
     */
    public function __get(string $property): mixed
    {
        $return = null;
        switch (strtolower($property)) {
            case 'isvalid': {
                $return = !is_null($this->_img);
                break;
            }
            case 'size': {
                $return = $this->_size;
                break;
            }
            case 'type': {
                $return = $this->_type;
                break;
            }
            case 'data': {
                $return = $this->_getImageData();
                break;
            }
            case 'transparency': {
                if (!is_null($this->_img)) {
                    $return = @\imagecolortransparent($this->_img);
                }
                break;
            }
            case 'name': {
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
     * Magic setter method.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set.
     * @magic
     * @public
     */
    public function __set(string $property, mixed $value): void
    {
        if (strtolower($property) == 'type') {
            $this->_type = $value;
        }
    }

    /**
     * Loads an image from binary data.
     *
     * This method loads an image from the provided binary data.
     *
     * @param string $data The binary data representing the image.
     * @return void
     * @public
     * @example 
     * ```
     * $graphics = new Graphics();
     * $imageData = file_get_contents('path/to/image.png');
     * $graphics->LoadFromData($imageData);
     * ```
     */
    public function LoadFromData(string $data): void
    {
        $this->_file = basename(RandomizationHelper::Mixed(20));
        $this->_img = @\imagecreatefromstring($data);
        $this->_size = new Size(\imagesx($this->_img), \imagesy($this->_img));
        $this->_history = array();
        $this->_safeAlpha();
    }

    /**
     * Loads an image from a file.
     *
     * This method loads an image from the specified file.
     *
     * @param string $file The path to the image file.
     * @return void
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * $graphics->LoadFromFile('path/to/image.png');
     * ```
     */
    public function LoadFromFile(string $file): void
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
     * Creates an empty image.
     *
     * This method creates an empty image with the specified size.
     *
     * @param Size $size The size of the empty image.
     * @return void
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * $size = new Size(200, 100);
     * $graphics->LoadEmptyImage($size);
     * ```
     */
    public function LoadEmptyImage(Size $size): void
    {
        $this->_type = "unknown";
        $this->_img = \imagecreatetruecolor($size->width, $size->height);
        $this->_size = $size;
        $this->_history = array();
        $this->_safeAlpha();
    }

    /**
     * Resizes the image.
     *
     * This method resizes the image to the specified size.
     *
     * @param Size $size The new size of the image.
     * @return void
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * $graphics->LoadFromFile('path/to/image.png');
     * $newSize = new Size(100, 50);
     * $graphics->Resize($newSize);
     * ```
     */
    public function Resize(Size $size): void
    {
        if ($this->isValid) {
            $newImage = \imagecreatetruecolor($size->width, $size->height);
            \imagealphablending($newImage, false);
            \imagesavealpha($newImage, true);
            ImageCopyResampled($newImage, $this->_img, 0, 0, 0, 0, $size->width, $size->height, $this->_size->width, $this->_size->height);
            ImageDestroy($this->_img);
            $this->_img = $newImage;
            $this->_size = $size;
            $this->_history[] = array('operation' => 'resize', 'postfix' => 'resized-' . $size->width . 'x' . $size->height);
        }
    }

    /**
     * Rotates the image.
     *
     * This method rotates the image clockwise by the specified degree.
     *
     * @param int $degree The degree by which to rotate the image. Default is 90.
     * @return void
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * $graphics->LoadFromFile('path/to/image.png');
     * $graphics->Rotate(90); // Rotates the image 90 degrees clockwise
     * ```
     */
    public function Rotate(int $degree = 90): void
    {
        $this->_img = \imagerotate($this->_img, $degree, -1);
        \imagealphablending($this->_img, true);
        \imagesavealpha($this->_img, true);
    }

    /**
     * Crops the image.
     *
     * This method crops the image to the specified size, starting from the optional start point.
     *
     * @param Size $size The size of the cropped area.
     * @param Point|null $start The starting point for cropping. If null, (0,0) is assumed.
     * @return void
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * $graphics->LoadFromFile('path/to/image.png');
     * $cropSize = new Size(100, 50);
     * $startPoint = new Point(10, 10);
     * $graphics->Crop($cropSize, $startPoint); // Crops the image to 100x50 starting from (10,10)
     * ```
     */
    public function Crop(Size $size, ?Point $start = null): void
    {
        if ($this->isValid) {
            if (is_null($start)) {
                $start = new Point(0, 0);
            }
            $newImage = ImageCreateTrueColor($size->width, $size->height);
            \imagealphablending($newImage, 0);
            \imagesavealpha($newImage, 1);
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
            $this->_size = $size;
            $this->_safeAlpha();

            $this->_history[] = array('operation' => 'crop', 'postfix' => 'croped-' . $start->x . 'x' . $start->y . '.' . $size->width . 'x' . $size->height);
        }
    }

    /**
     * Applies a filter to the image.
     *
     * This method applies the specified filter to the image with optional arguments.
     *
     * @param int $filter The filter to apply.
     * @param int $arg1 The first optional argument for the filter.
     * @param int $arg2 The second optional argument for the filter.
     * @param int $arg3 The third optional argument for the filter.
     * @return bool|null Returns true on success, false on failure, or null if the filter is not supported.
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * $graphics->LoadFromFile('path/to/image.png');
     * $graphics->ApplyFilter(IMG_FILTER_GRAYSCALE); // Applies grayscale filter
     * $graphics->ApplyFilter(IMG_FILTER_BRIGHTNESS, 50); // Increases brightness by 50
     * ```
     */
    public function ApplyFilter(int $filter, int $arg1 = 0, int $arg2 = 0, int $arg3 = 0): ?bool
    {
        $return = null;
        switch ($filter) {
            case IMG_FILTER_NEGATE: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'negate');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_GRAYSCALE: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'grayscale');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_BRIGHTNESS: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'brightness-' . $arg1);
                $return = \imagefilter($this->_img, $filter, $arg1);
                break;
            }
            case IMG_FILTER_CONTRAST: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'contrast-' . $arg1);
                $return = \imagefilter($this->_img, $filter, $arg1);
                break;
            }
            case IMG_FILTER_COLORIZE: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'colorize-' . $arg1 . 'x' . $arg2 . 'x' . $arg3);
                $return = \imagefilter($this->_img, $filter, $arg1, $arg2, $arg3);
                break;
            }
            case IMG_FILTER_EDGEDETECT: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'edgedetect');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_EMBOSS: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'emboss');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_GAUSSIAN_BLUR: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'gausian-blur');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_SELECTIVE_BLUR: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'blur');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_MEAN_REMOVAL: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'mean-removal');
                $return = \imagefilter($this->_img, $filter);
                break;
            }
            case IMG_FILTER_SMOOTH: {
                $this->_history[] = array('operation' => 'filter', 'postfix' => 'smooth-' . $arg1);
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
     * Saves the image to a file.
     *
     * This method saves the image to the specified file.
     *
     * @param string $file The path to save the image file.
     * @return void
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * $graphics->LoadFromFile('path/to/image.png');
     * $graphics->Save('path/to/save/image.png');
     * ```
     */
    public function Save(string $file): void
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
     * Sets the alpha channel for the image.
     *
     * This method ensures that the alpha channel is properly set for the image.
     *
     * @return void
     * @public
     * @example
     * ```
     * $graphics = new Graphics();
     * $graphics->LoadFromFile('path/to/image.png');
     * $graphics->_safeAlpha(); // Ensures alpha channel is set
     * ```
     */
    private function _safeAlpha(): void
    {
        // save alpha
        \imagealphablending($this->_img, 1);
        \imagesavealpha($this->_img, 1);
    }

    /**
     * Retrieves the binary data of the image.
     *
     * This method retrieves the binary data of the image.
     *
     * @return string The binary data of the image.
     * @private
     * @example
     * ```
     * $graphics = new Graphics();
     * $graphics->LoadFromFile('path/to/image.png');
     * $imageData = $graphics->_getImageData(); // Retrieves the binary data of the image
     * ```
     */
    private function _getImageData(): string
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
     * Retrieves information about an image file.
     *
     * This method retrieves information about the image file located at the specified path.
     *
     * @param string $path The path to the image file.
     * @return ExtendedObject An object containing information about the image.
     * @public
     * @example
     * ```
     * $info = Graphics::Info('path/to/image.png');
     * echo $info->size->width; // Outputs the width of the image
     * echo $info->size->height; // Outputs the height of the image
     * echo $info->type; // Outputs the type of the image (e.g., png, gif, jpg)
     * echo $info->attr; // Outputs additional attributes of the image
     * ```
     */
    public static function Info(string $path): ExtendedObject
    {
        [$width, $height, $type, $attr] = \getimagesize($path);
        $o = new ExtendedObject();
        $o->size = new Size($width, $height);
        $o->type = $type;
        $o->attr = $attr;
        return $o;
    }

    /**
     * Creates a Graphics object from data.
     *
     * This method creates a Graphics object from the provided data.
     *
     * @param string $data The data to create the Graphics object from.
     * @return Graphics A Graphics object initialized with the provided data.
     * @public
     * @example
     * ```
     * $graphics = Graphics::Create($imageData);    
     * ```
     */
    public static function Create(string $data): Graphics
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
