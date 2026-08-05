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
 * @class
 */
class Type
{
    /** 
     * POST request
     * @const string
     * @public 
     */
    public const Post = 'post';

    /** 
     * GET request
     * @const string
     * @public
     */
    public const Get = 'get';

    /** 
     * HEAD request
     * @const string
     * @public
     */
    public const Head = 'head';

    /** 
     * DELETE request
     * @const string
     * @public
     */
    public const Delete = 'delete';

    /** 
     * PUT request
     * @const string
     * @public
     */
    public const Put = 'put';

    /** 
     * PATCH request
     * @const string
     * @public
     */
    public const Patch = 'patch';
}
