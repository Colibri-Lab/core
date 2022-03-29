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
 * Класс для работы с прямоугольными областями на экране
 */
class Quadro {

    /**
     * Нижний левый угол
     *
     * @var Point
     */
    public ?Point $lowerleft = null;

    /**
     * Нижний правый угол
     *
     * @var Point
     */
    public ?Point $lowerright = null;

    /**
     * Верхний левый угол
     *
     * @var Point
     */
    public ?Point $upperleft = null;

    /**
     * Верхний правый угол
     *
     * @var Point
     */
    public ?Point $upperright = null;

}

