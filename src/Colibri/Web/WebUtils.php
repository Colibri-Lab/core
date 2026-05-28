<?php

namespace Colibri\Web;

use Colibri\Common\Encoding;
use Colibri\Common\ErrorHelper;
use Colibri\Common\StringHelper;
use React\Http\Message\Response as MessageResponse;
use Psr\Http\Message\ServerRequestInterface;

class WebUtils
{
    /**
     * List of types
     */
    public const JSON = 'json';
    public const XML = 'xml';
    public const HTML = 'html';
    public const Text = 'txt';
    public const CSS = 'css';
    public const JS = 'js';
    public const Stream = 'stream';


    /**
    * List of errors
    */
    public const IncorrectCommandObject = 1;
    public const UnknownMethodInObject = 2;

    /**
     * Parses command URL to determine type, class, and method.
     *
     * @param string $cmd The command URL.
     * @return array An array containing type, class, and method.
     */
    public static function ParseCommand(string $cmd): array
    {
        $cmd = explode('?', $cmd);
        $cmd = reset($cmd);

        $isRequestTyped = true;
        $method = 'index';
        $type = self::HTML;
        $class = $cmd;
        if (preg_match('/\/([^\/]+)\.([^\?]+)/', $cmd, $matches) > 0) {
            $method = $matches[1];
            $type = $matches[2];
            $class = str_replace($method . '.' . $type, '', $cmd);
        } elseif (preg_match('/\/([^\/]+)$/', $cmd, $matches) > 0) {
            $method = $matches[1];
            $type = self::JSON;
            $class = preg_replace('/' . $method . '$/', '', $cmd);
            $isRequestTyped = false;
        }

        $class = self::GetControllerFullName($class);
        $method = StringHelper::ToCamelCaseAttr($method, true);

        return [$type, $class, $method, $isRequestTyped];
    }

    
    /**
     * Gets the full controller class name with namespace.
     *
     * @param string $class The class name.
     * @return string The full class name.
     */
    public static function GetControllerFullName(string $class): string
    {
        $class = StringHelper::UrlToNamespace($class);
        if (strpos($class, 'Modules') === 0) {

            // это модуль, значит должно быть modules/«название модуля»[/«название контроллера»]
            $parts = explode('\\', $class);
            if (count($parts) >= 3) {
                array_splice($parts, 2, 0, 'Controllers');
            } else {
                $parts[] = 'Controllers\\';
            }
            $class = implode('\\', $parts);

            return '\\App\\' . $class . 'Controller';
        }
        return '\\App\\Controllers\\' . $class . 'Controller';
    }

    /**
     * Converts data to specified charset recursively.
     *
     * @param mixed $data The data to convert.
     * @param string $charset The charset to convert to.
     * @return mixed The converted data.
     */
    public static function ConvertDataToCharset(mixed $data, string $charset): mixed
    {
        $data = (array) $data;
        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $data[$key] = self::ConvertDataToCharset($value, $charset);
            } else {
                $data[$key] = Encoding::Convert($value, $charset);
            }
        }

        return $data;

    }


}