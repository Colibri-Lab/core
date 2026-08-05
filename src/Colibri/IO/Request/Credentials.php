<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\Request
 */

namespace Colibri\IO\Request;

/**
 * Credentials
 * @class
 * @example
 * ```
 * $credentials = new Credentials('login', 'password', true);
 * ```
*/
class Credentials
{
    /**
     * Login
     *
     * @var string
     * @public
     */
    public string $login = '';

    /**
     * Password
     *
     * @var string
     * @public
     */
    public string $secret = '';

    /**
     * Use SSL
     *
     * @var boolean
     * @public
     */
    public bool $ssl = false;

    /**
     * Constructor
     *
     * @param string $login
     * @param string $password
     * @param boolean $ssl
     * @constructor
     * @public
     * @example
     * ```
     * $credentials = new Credentials('login', 'password', true);
     * ```
     */
    public function __construct(string $login = '', string $password = '', bool $ssl = false)
    {
        $this->login = $login;
        $this->secret = $password;
        $this->ssl = $ssl;
    }
}
