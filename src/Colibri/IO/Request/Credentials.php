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
 * Полномочия
 * @testFunction testCredentials
 */
class Credentials
{

    /**
     * Логин
     *
     * @var string
     */
    public string $login = '';

    /**
     * Пароль
     *
     * @var string
     */
    public string $secret = '';

    /**
     * Использовать SSL
     *
     * @var boolean
     */
    public bool $ssl = false;

    /**
     * Конструктор
     *
     * @param string $login
     * @param string $password
     * @param boolean $ssl
     */
    public function __construct(string $login = '', string $password = '', bool $ssl = false)
    {
        $this->login = $login;
        $this->secret = $password;
        $this->ssl = $ssl;
    }
}
