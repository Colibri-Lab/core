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
 * Result of a request.
 * @class
 * @example
 * ```
 * $result = new Result();
 * $result->status = 200;
 * $result->data = 'Response data';
 * $result->error = '';
 * $result->headers = ['Content-Type' => 'application/json'];
 * $result->httpheaders = ['HTTP/1.1 200 OK'];
 * ```
 */
class Result
{
    /**
     * Status of the request.
     *
     * @var int
     * @public
     */
    public int $status;

    /**
     * Data of the result.
     *
     * @var string
     * @public
     */
    public string $data;

    /**
     * Error message, if any.
     *
     * @var string
     * @public
     */
    public string $error;

    /**
     * Array or object containing headers.
     *
     * @var object|array
     * @public
     */
    public object|array $headers;

    /**
     * Array or object containing HTTP headers.
     *
     * @var object|array
     * @public
     */
    public object|array $httpheaders;
}
