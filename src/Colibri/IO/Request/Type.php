<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Storages
 */

namespace Colibri\IO\Request;

/**
 * Types of requests.
 */
class Type
{

    /** POST request */
    const Post = 'post';

    /** GET request */
    const Get = 'get';

    /** HEAD request */
    const Head = 'head';

    /** DELETE request */
    const Delete = 'delete';

    /** PUT request */
    const Put = 'put';

    /** PATCH request */
    const Patch = 'patch';
}