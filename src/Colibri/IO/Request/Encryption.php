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
 * Types of form data transmission.
 */
class Encryption
{
    /** Multipart */
    public const Multipart = 'multipart/form-data';

    /** URL Encoded */
    public const UrlEncoded = 'application/x-www-form-urlencoded';

    /** Request with XML payload */
    public const XmlEncoded = 'application/x-www-form-xmlencoded';

    /** Request with JSON payload */
    public const JsonEncoded = 'application/json';
}
