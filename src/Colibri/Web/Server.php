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

use Colibri\App;
use Colibri\Common\Encoding;
use Colibri\Common\MimeType;
use Colibri\Common\XmlHelper;
use Colibri\Common\HtmlHelper;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Common\NoLangHelper;
use Colibri\Utils\Debug;

/**
 * Web server
 */
class Server
{
    use TEventDispatcher;

     /**
     * List of errors
     */
    public const IncorrectCommandObject = 1;
    public const UnknownMethodInObject = 2;

    /**
     * List of types
     */
    public const JSON = 'json';
    public const XML = 'xml';
    public const HTML = 'html';
    public const CSS = 'css';
    public const JS = 'js';
    public const Stream = 'stream';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Do nothing
    }

    /**
     * Converts data to specified charset recursively.
     *
     * @param mixed $data The data to convert.
     * @param string $charset The charset to convert to.
     * @return mixed The converted data.
     */
    private function _convertDataToCharset(mixed $data, string $charset): mixed
    {
        $data = (array) $data;
        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $data[$key] = $this->_convertDataToCharset($value, $charset);
            } else {
                $data[$key] = Encoding::Convert($value, $charset);
            }
        }

        return $data;

    }

    /**
     * Finishes the process and sends response.
     *
     * @param string $type The response type.
     * @param mixed $result The result to send.
     * @return void
     */
    protected function Finish(string $type, mixed $result)
    {
        $result = (object) $result;
        if (!isset($result->headers)) {
            $result->headers = [];
        }
        if (!isset($result->cookies)) {
            $result->cookies = [];
        }

        App::$response->Origin();

        $mime = new MimeType($type);

        // if we responsing with file
        if (
            $type == Server::Stream && $result?->result &&
            (is_string($result->result) && is_string($result->message))
        ) {
            App::$response->DownloadFile($result->message, $result->result);
        }

        $content = $result?->message ?? HtmlHelper::Encode($result?->result ?? []);
        if ($type == Server::JSON || $type == Server::Stream) {
            $content = json_encode($result?->result ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ($type === Server::XML) {
            $content = XmlHelper::Encode($result?->result ?? []);
        } elseif ($type == Server::HTML) {
            $content = $result?->message ?? HtmlHelper::Encode($result?->result ?? []);
        } elseif ($type == Server::CSS) {
            $content = $result?->message ?? [];
        }

        App::$response->Close(
            $result->code ?: 500,
            $content,
            $mime->data ?? 'application/octet-stream',
            $result?->charset ?? 'utf-8',
            $result?->headers ?? [],
            $result?->cookies ?? []
        );

    }

    /**
     * Sends an error response in XML format.
     *
     * @param string $type The response type.
     * @param string $message The error message.
     * @param int $code The error code.
     * @param string $cmd The command.
     * @param mixed $data Additional data.
     * @return void
     */
    protected function _responseWithError(
        string $type,
        string $message,
        int $code = -1,
        string $cmd = '',
        mixed $data = null
    ) {

        $this->Finish($type, (object) [
            'code' => 404,
            'message' => $message,
            'result' => (object) [
                'code' => $code,
                'command' => $cmd,
                'data' => $data
            ]
        ]);
    }

    /**
     * Gets the full controller class name with namespace.
     *
     * @param string $class The class name.
     * @return string The full class name.
     */
    protected function _getControllerFullName(string $class): string
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
     * Parses command URL to determine type, class, and method.
     *
     * @param string $cmd The command URL.
     * @return array An array containing type, class, and method.
     */
    private function __parseCommand(string $cmd): array
    {
        $cmd = explode('?', $cmd);
        $cmd = reset($cmd);

        $method = 'index';
        $type = Server::HTML;
        $class = $cmd;
        if (preg_match('/\/([^\/]+)\.([^\?]+)/', $cmd, $matches) > 0) {
            $method = $matches[1];
            $type = $matches[2];
            $class = str_replace($method . '.' . $type, '', $cmd);
        } elseif (preg_match('/\/([^\/]+)$/', $cmd, $matches) > 0) {
            $method = $matches[1];
            $type = Server::JSON;
            $class = preg_replace('/' . $method . '$/', '', $cmd);
        }

        $class = $this->_getControllerFullName($class);
        $method = StringHelper::ToCamelCaseAttr($method, true);

        return [$type, $class, $method];
    }

    /**
     * Runs the specified command.
     *
     * The command should be in the following format:
     * /namespace[/namespace]/command[.type]
     *
     * @param string $cmd The command to execute.
     * @param string $default The default command to execute if the specified command is not found.
     * @return void
     */
    public function Run(string $cmd, string $default = ''): void
    {

        // /namespace[/namespace]/command[.type]
        list($type, $class, $method) = $this->__parseCommand($cmd);

        if (!VariableHelper::IsNull($default) && (!class_exists($class) || !method_exists($class, $method))) {
            // если не нашли чего делать то пробуем по умолчанию
            list($type, $class, $method) = $this->__parseCommand($default);
        }

        $requestMethod = App::$request->server->{'request_method'};
        $waitForAnswer = ((App::$request->server?->{'http_waitforanswer'} ?? 'true') === 'true');
        $get = App::$request->get;
        $post = App::$request->post;
        $payload = App::$request->GetPayloadCopy();

        if(!$waitForAnswer) {
            header("Connection: close\r\n");
            header("Content-Encoding: none\r\n");
            header("Content-Length: 1");
            ignore_user_abort(true);
            echo '1';
            fastcgi_finish_request();
        }

        $args = (object) [
            'class' => $class,
            'method' => $method,
            'get' => $get,
            'post' => $post,
            'payload' => $payload
        ];
        $this->DispatchEvent(EventsContainer::RpcGotRequest, $args);
        if (isset($args->cancel) && $args->cancel === true) {
            $result = isset($args->result) ? $args->result : (object) [];
            $this->Finish($type, $result);
        }

        if (!class_exists($class)) {
            $message = 'Unknown class ' . $class;
            $this->DispatchEvent(EventsContainer::RpcRequestError, (object) [
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'message' => $message
            ]);

            $this->_responseWithError($type, $message, Server::IncorrectCommandObject, $cmd, [
                'message' => $message,
                'code' => Server::IncorrectCommandObject,
                'get' => $get,
                'post' => $post,
                'payload' => $payload
            ]);
            return;
        }

        if (!method_exists($class, $method)) {
            $message = 'Unknown method ' . $method . ' in object ' . $class;
            $this->DispatchEvent(EventsContainer::RpcRequestError, (object) [
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'message' => $message
            ]);

            $this->_responseWithError(
                $type,
                $message,
                Server::UnknownMethodInObject,
                $cmd,
                [
                    'message' => $message,
                    'code' => Server::UnknownMethodInObject,
                    'get' => $get,
                    'post' => $post,
                    'payload' => $payload
                ]
            );
            return;
        }

        if ($requestMethod === 'OPTIONS') {
            // если это запрос на опции то вернуть
            $this->Finish($type, (object) ['code' => 200, 'message' => 'ok', 'options' => true]);
        } else {

            App::$monitoring->StartTimer('web-request');

            try {
                $obj = new $class($type);
                $result = (object) $obj->Invoke($method, $get, $post, $payload);
            } catch (\Throwable $e) {

                $errorResult = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage()
                ];

                if(method_exists($e, 'getExceptionDataAsArray')) {
                    $errorResult['data'] = $e->{'getExceptionDataAsArray'}();
                }

                if(App::$isDev || App::$isLocal) {
                    $errorResult['line'] = $e->getLine();
                    $errorResult['file'] = $e->getFile();
                    $errorResult['trace'] = $e->getTrace();
                }

                // если что то не так то выводим ошибку
                $result = (object)[
                    'code' => $e->getCode() ?: 500,
                    'result' => $errorResult
                ];

                $code = $e->getCode() ?: 500;

                $message = $e->getMessage();
                App::$log->debug($code . ': ' . $message);
                App::$log->debug($e->getTraceAsString());
            }

            $args = (object) [
                'object' => $obj,
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'result' => $result,
                'type' => $type
            ];
            $this->DispatchEvent(EventsContainer::RpcRequestProcessed, $args);

            if($type !== self::Stream) {
                // на случай, если не включен модуль языков
                $args->result = NoLangHelper::ParseArray($args->result);
            }

            App::$monitoring->EndTimer('web-request');

            $this->Finish($type, $args->result);
        }

    }
}
