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
 */
class Result
{

    /**
     * Status of the request.
     *
     * @var int
     */
    public int $status;

    /**
     * Data of the result.
     *
     * @var string
     */
    public string $data;

    /**
     * Error message, if any.
     *
     * @var string
     */
    public string $error;

    /**
     * Array or object containing headers.
     *
     * @var object|array
     */
    public object|array $headers;
    
    /**
     * Array or object containing HTTP headers.
     *
     * @var object|array
     */
    public object|array $httpheaders;
}