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
 * Types of requests.
 */
class Type
{
    /** POST request */
    public const Post = 'post';

    /** GET request */
    public const Get = 'get';

    /** HEAD request */
    public const Head = 'head';

    /** DELETE request */
    public const Delete = 'delete';

    /** PUT request */
    public const Put = 'put';

    /** PATCH request */
    public const Patch = 'patch';
}
