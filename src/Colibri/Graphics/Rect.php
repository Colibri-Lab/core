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
 * Represents rectangular areas on the screen.
 *
 * This class facilitates operations involving rectangular areas on the screen.
 */
class Rect
{
    /**
     * The bottom-left corner.
     *
     * @var Point|null
     */
    public ?Point $lowerleft = null;

    /**
     * The bottom-right corner.
     *
     * @var Point|null
     */
    public ?Point $lowerright = null;

    /**
     * The top-left corner.
     *
     * @var Point|null
     */
    public ?Point $upperleft = null;

    /**
     * The top-right corner.
     *
     * @var Point|null
     */
    public ?Point $upperright = null;

}
