<?php

/**
 * Threading
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Threading
 * 
 */

namespace Colibri\Threading;

use Colibri\AppException;

/**
 * Исключение для процессов и потоков
 */
class Exception extends AppException
{
    /**
     * Создает исключение
     *
     * @param int $code код ошибки ErrorCodes
     * @param string $message дополнительный текст ошибки
     */
    public function __construct(int $code, string $message)
    {
        parent::__construct(ErrorCodes::ToString($code) . ' ' . $message, $code);
    }
}
