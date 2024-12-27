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
 * Represents a point on the screen.
 *
 * This class encapsulates the coordinates of a point on the screen.
 */
class Point
{
    /**
     * The X coordinate position.
     *
     * @var int
     */
    public int $x;

    /**
     * The Y coordinate position.
     *
     * @var int
     */
    public int $y;

    /**
     * Constructs a new Point instance.
     *
     * @param int $x The X coordinate position.
     * @param int $y The Y coordinate position.
     */
    public function __construct(int $x = 0, int $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

}
