<?php

/**
 * Web
 *
 * This abstract class represents a template for web content generation.
 *
 * @package Colibri\Web
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab 
 */
namespace Colibri\Web;

use Colibri\Common\StringHelper;
use Colibri\Utils\Cache\Mem;
use Colibri\App;

/**
 * Controller Handler Class
 *
 * This abstract class serves as a base for processing web requests.
 * Inherit from it and create functions that can be called, for example:
 *
 * @example
 * ```php
 * 
 * class PageController extends Colibri\Web\Controller {
 *
 *     public function Method1($get, $post, $payload) {
 *
 *         Write what is needed here and finish the function with the Finish method.
 *
 *         Attention:
 *         Modifying $get, $post, $payload is meaningless, as they are copies of the data passed in the request.
 *
 *         PROHIBITED:
 *         1. Output anything using echo, print_r, var_dump, etc.
 *         2. Requesting other RPC Handlers at the same level.
 *         3. Implementing business logic in the handler class (inheritors of RpcHandler).
 *
 *         $code = 200 | 400, etc.
 *         $message = any message
 *         $result = response payload, can be a string in the case of html/xml
 *
 *         ! NO ECHO ALLOWED !!!!
 *
 *         Example of result:
 *
 *         div => [
 *             span => test
 *         ]
 *
 *         XML helper will create:
 *
 *         <div><span>test</span></div>
 *
 *         HTML helper will create:
 *
 *         <div class="div"><div class="span">test</div></div>
 *
 *         return $this->Finish(int $code, string $message, mixed $result);
 *
 *     }
 *
 * }
 * ```
 */
class Controller
{
    protected ?string $_type = null;

    protected bool $_cache = false;
    protected int $_lifetime = 600;

    /**
     * Constructor.
     *
     * @param string|null $type The type of response (e.g., json, xml, html).
     */
    public function __construct(?string $type = null)
    {
        $this->_type = $type;
    }

    /**
     * Finish handler execution.
     *
     * @param int $code The error code.
     * @param string $message The message.
     * @param mixed $result Additional parameters.
     * @param string $charset The character encoding.
     * @param array $headers Additional headers.
     * @param array $cookies Cookies to be set.
     * @param bool $forceNoCache Whether to force no caching.
     * @return object The finished result.
     */
    public function Finish(
        int $code,
        string $message,
        mixed $result = null,
        string $charset = 'utf-8',
        array $headers = [],
        array $cookies = [],
        bool $forceNoCache = false
    ): object {
        $res = (object) [];
        $res->code = $code;
        $res->message = $message;
        $res->result = $result;
        $res->charset = $charset;
        $res->headers = $headers;
        $res->cookies = $cookies;
        $res->forceNoCache = $forceNoCache;
        return $res;
    }

    /**
     * Generate a URL for entry point addition.
     *
     * @param string $method The name of the function in the controller class.
     * @param string $type The type of return value: json, xml, html.
     * @param array $params GET parameters.
     * @return string The generated URL.
     */
    public static function GetEntryPoint(string $method = '', string $type = '', array $params = []): string
    {
        $class = static::class;
        // если контроллер в модуле
        if (strpos($class, 'App\\Modules\\') === 0) {
            $class = str_replace('App\\', '', $class);
            $class = str_replace('Controllers\\', '', $class);
        } else {
            $class = str_replace('App\\Controllers\\', '', $class);
        }
        $class = str_replace('\\', '/', $class);
        $class = substr($class, 0, -1 * strlen('Controller'));
        $parts = explode('/', trim($class, '/'));
        $newParts = [];
        foreach ($parts as $c) {
            $newParts[] = StringHelper::FromCamelCaseAttr($c);
        }

        $path = implode('/', $newParts) . '/';

        if (!$method && !$type) {
            $url = StringHelper::AddToQueryString($path, $params, true);
        } elseif (!$method && $type) {
            $url = $path . 'index.' . $type;
        } else {
            $url = $path . StringHelper::FromCamelCaseAttr($method) . '.' . $type;
        }

        return '/' . StringHelper::AddToQueryString($url, $params, true);
    }

    /**
     * Magic getter method.
     *
     * @param string $prop The property name.
     * @return mixed The value of the property.
     */
    public function __get($prop): mixed
    {
        return match($prop) {
            'cache' => $this->_cache,
            'lifetime' => $this->_lifetime
        };
    }

    /**
     * Invokes the specified method and handles caching if enabled.
     *
     * @param string $method The method to invoke.
     * @param RequestCollection $get The GET request collection.
     * @param RequestCollection $post The POST request collection.
     * @param PayloadCopy $payload The payload copy.
     * @return mixed The result of the method invocation.
     */
    public function Invoke(string $method, RequestCollection $get, RequestCollection $post, PayloadCopy $payload)
    {
        if($this->_cache) {
            $md5 = md5(App::$request->host);
            $cacheName = 'controller-' .
                str_replace('\\', '_', strtolower(static::class . '_' . $method)) . '-' .
                md5(json_encode($get->ToArray()) . json_encode($post->ToArray()) . json_encode($payload->ToArray()) . '-' .
                $md5);
            if(Mem::Exists($cacheName)) {
                $return = Mem::Read($cacheName);
            } else {
                $return = $this->$method($get, $post, $payload);
                if(!$return->forceNoCache) {
                    Mem::Write($cacheName, $return, $this->_lifetime);
                }
            }
        } else {
            $return = $this->$method($get, $post, $payload);
        }

        return $return;
    }

}
