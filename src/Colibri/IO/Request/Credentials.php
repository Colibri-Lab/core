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
    public $login = '';

    /**
     * Пароль
     *
     * @var string
     */
    public $secret = '';

    /**
     * Использовать SSL
     *
     * @var boolean
     */
    public $ssl = false;

    /**
     * Конструктор
     *
     * @param string $login
     * @param string $password
     * @param boolean $ssl
     */
    public function __construct($login = '', $password = '', $ssl = false)
    {
        $this->login = $login;
        $this->secret = $password;
        $this->ssl = $ssl;
    }
}
