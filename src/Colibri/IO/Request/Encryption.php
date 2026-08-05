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
 * Types of form data transmission
 * @class
 */
class Encryption
{
    /** 
     * Multipart
     * @const string
     * @public 
     */
    public const Multipart = 'multipart/form-data';

    /** 
     * URL Encoded
     * @const string
     * @public
     */
    public const UrlEncoded = 'application/x-www-form-urlencoded';

    /** 
     * Request with XML payload
     * @const string
     * @public
     */
    public const XmlEncoded = 'application/x-www-form-xmlencoded';

    /** 
     * Request with JSON payload
     * @const string
     * @public
     */
    public const JsonEncoded = 'application/json';

    /** 
     * Binary
     * @const string
     * @public
     */
    public const Binary = 'application/octet-stream';

}
