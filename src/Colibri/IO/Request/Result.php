<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\Request
 */

namespace Colibri\IO\Request;

/**
 * Результат запроса
 */
class Result
{

    /**
     * Статус запроса
     *
     * @var int
     */
    public $status;

    /**
     * Данные
     *
     * @var string
     */
    public $data;

    /**
     * Массив заголовков
     *
     * @var object
     */
    public $headers;
}
