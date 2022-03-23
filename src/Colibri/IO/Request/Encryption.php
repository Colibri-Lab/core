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
 * Типы передачи данных формы
 */
class Encryption
{

    /** Мультипарт */
    const Multipart = 'multipart/form-data';

    /** UrlEncoded  */
    const UrlEncoded = 'application/x-www-form-urlencoded';

    /** Запрос с пейлоадом XML */
    const XmlEncoded = 'application/x-www-form-xmlencoded';

    /** Запрос с пейлоадом JSON */
    const JsonEncoded = 'application/json';
}
