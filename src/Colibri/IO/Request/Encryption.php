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
 * Types of form data transmission.
 */
class Encryption
{

    /** Multipart */
    const Multipart = 'multipart/form-data';

    /** URL Encoded */
    const UrlEncoded = 'application/x-www-form-urlencoded';

    /** Request with XML payload */
    const XmlEncoded = 'application/x-www-form-xmlencoded';

    /** Request with JSON payload */
    const JsonEncoded = 'application/json';
}