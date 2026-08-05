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
 * 
 * @class
 * @example
 * ```
 * $rect = new Rect();
 * $rect->lowerleft = new Point(0, 0);
 * $rect->lowerright = new Point(100, 0);
 * $rect->upperleft = new Point(0, 100);
 * $rect->upperright = new Point(100, 100);
 * ```
 */
class Rect
{
    /**
     * The bottom-left corner.
     *
     * @var Point|null
     * @public
     */
    public ?Point $lowerleft = null;

    /**
     * The bottom-right corner.
     *
     * @var Point|null
     * @public
     */
    public ?Point $lowerright = null;

    /**
     * The top-left corner.
     *
     * @var Point|null
     * @public
     */
    public ?Point $upperleft = null;

    /**
     * The top-right corner.
     *
     * @var Point|null
     * @public
     */
    public ?Point $upperright = null;

}
