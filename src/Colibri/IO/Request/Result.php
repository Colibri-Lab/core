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
    public int $status;

    /**
     * Данные
     *
     * @var string
     */
    public string $data;

    /**
     * Ошибка
     *
     * @var string
     */
    public string $error;

    /**
     * Массив заголовков
     *
     * @var object|array
     */
    public object|array $headers;
    
    /**
     * Массив заголовков HTTP
     *
     * @var object|array
     */
    public object|array $httpheaders;
}