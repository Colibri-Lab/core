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
 * Типы запросов
 */
class Type
{

    /** POST запрос */
    const Post = 'post';

    /** GET запрос */
    const Get = 'get';

    /** HEAD запрос */
    const Head = 'head';

    /** DELETE запрос */
    const Delete = 'delete';

    /** PUT запрос */
    const Put = 'put';

    /** PATCH запрос */
    const Patch = 'patch';
}
