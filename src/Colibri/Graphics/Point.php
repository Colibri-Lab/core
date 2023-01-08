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
 * Класс представляющий точку на экране
 */
class Point
{

    /**
     * Позиция X
     *
     * @var int
     */
    public int $x;
    /**
     * Позиция Y
     *
     * @var int
     */
    public int $y;

    /**
     * Конструктор
     *
     * @param integer $x
     * @param integer $y
     */
    public function __construct(int $x = 0, int $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

}