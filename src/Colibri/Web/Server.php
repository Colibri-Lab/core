<?php

/**
 * Веб сервер
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Config
 * @version 1.0.0
 * 
 */

namespace Colibri\Web;

use Colibri\App;
use Colibri\Common\Encoding;
use Colibri\Common\XmlHelper;
use Colibri\Common\HtmlHelper;
use Colibri\Common\MimeType;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Debug;
use PHPUnit\TextUI\XmlConfiguration\Variable;

/**
 * Веб сервер
 * @testFunction testServer
 */
class Server
{


    use TEventDispatcher;

    /**
     * Список ошибок
     */
    const IncorrectCommandObject = 1;
    const UnknownMethodInObject = 2;

    /**
     * Список типов
     */
    const JSON = 'json';
    const XML = 'xml';
    const HTML = 'html';
    const CSS = 'css';
    const JS = 'js';
    const Stream = 'stream';

    /**
     * Конструктор
     */
    public function __construct()
    {
        // Do nothing
    }

    private function _convertDataToCharset(mixed $data, string $charset): mixed
    {
        $data = (array)$data;
        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $data[$key] = $this->_convertDataToCharset($value, $charset);
            }
            else {
                $data[$key] = Encoding::Convert($value, $charset);
            }
        }

        return $data;

    }

    /**
     * Завершает процесс
     */
    protected function Finish(string $type, mixed $result)
    {
        if (!isset($result->headers)) {
            $result->headers = [];
        }

        App::$response->Origin();

        if ($result?->result ?? null) {
            if ($type == Server::JSON) {
                App::$response->Close($result->code, json_encode($result->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'application/json', (isset($result->charset) ? $result->charset : 'utf-8'), $result->headers);
            }
            elseif ($type == Server::XML) {
                App::$response->Close($result->code, XmlHelper::Encode($result->result), 'application/xml', (isset($result->charset) ? $result->charset : 'utf-8'), $result->headers);
            }
            elseif ($type == Server::HTML) {
                App::$response->Close($result->code, $result->message ? $result->message : HtmlHelper::Encode($result->result), 'text/html', (isset($result->charset) ? $result->charset : 'utf-8'), $result->headers);
            }
            elseif ($type == Server::Stream) {
                // если запросили например PDF или еще что то 
                if (is_string($result->result) && is_string($result->message)) {
                    App::$response->DownloadFile($result->message, $result->result);
                }
                else {
                    App::$response->Close(500, 'Ошибка формирования ответа');
                }
            }
        }
        else {
            if ($type == Server::CSS) {
                App::$response->Close($result->code, $result->message, 'text/css', 'utf-8', $result->headers);
            }
            else {
                App::$response->Close($result->code, $result->message, 'text/html', 'utf-8', $result->headers);
            }
        }
    }

    /**
     * Отправляет ответ об ошибке в виде XML
     */
    protected function _responseWithError(string $type, string $message, int $code = -1, string $cmd = '', mixed $data = null)
    {

        $this->Finish($type, (object)[
            'code' => 404,
            'message' => $message,
            'result' => (object)[
                'code' => $code,
                'command' => $cmd,
                'data' => $data
            ]
        ]);
    }

    /**
     * Возвращает название класса с окружением
     */
    protected function _getControllerFullName(string $class): string
    {
        $class = StringHelper::UrlToNamespace($class);
        if (strpos($class, 'Modules') === 0) {

            // это модуль, значит должно быть modules/«название модуля»[/«название контроллера»]
            $parts = explode('\\', $class);
            if (count($parts) >= 3) {
                $parts[count($parts) - 1] = 'Controllers\\' . $parts[count($parts) - 1];
            }
            else {
                $parts[] = 'Controllers\\';
            }
            $class = implode('\\', $parts);

            return '\\App\\' . $class . 'Controller';
        }
        return '\\App\\Controllers\\' . $class . 'Controller';
    }

    /**
     * Определяет тип, класс и метод по url
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
        }
        else if(preg_match('/\/([^\/]+)$/', $cmd, $matches) > 0) {
            $method = $matches[1];
            $type = Server::JSON;
            $class = preg_replace('/'.$method.'$/', '', $cmd);
        }

        $class = $this->_getControllerFullName($class);
        $method = StringHelper::ToCamelCaseAttr($method, true);

        return [$type, $class, $method];
    }

    /**
     * Запускает команду
     * 
     * Команда должна быть сформирована следующим образом
     * папки, после \App\Controllers превращаются в namespace
     * т.е. /buh/test-rpc/test-query.json 
     * будет превращено в \App\Controllers\Buh\TestRpcController
     * а метод будет TestQuery 
     * 
     * т.е. нам нужно получить lowercase url в котором все большие 
     * буквы заменяются на - и маленькая буква, т.е. test-rpc = TestRpc
     *
     * @return void
     * @testFunction testServerRun
     */
    public function Run(string $cmd, string $default = ''): void
    {

        // /namespace[/namespace]/command[.type]
        list($type, $class, $method) = $this->__parseCommand($cmd);

        if (!VariableHelper::IsNull($default) && (!class_exists($class) || !method_exists($class, $method))) {
            // если не нашли чего делать то пробуем по умолчанию
            list($type, $class, $method) = $this->__parseCommand($default);
        }

        $requestMethod = App::$request->server->request_method;
        $get = App::$request->get;
        $post = App::$request->post;
        $payload = App::$request->GetPayloadCopy();

        $args = (object)['class' => $class, 'method' => $method, 'get' => $get, 'post' => $post, 'payload' => $payload];
        $this->DispatchEvent(EventsContainer::RpcGotRequest, $args);
        if (isset($args->cancel) && $args->cancel === true) {
            $result = isset($args->result) ? $args->result : (object)[];
            $this->Finish($type, $result);
        }



        if (!class_exists($class)) {
            $message = 'Unknown class ' . $class;
            $this->DispatchEvent(EventsContainer::RpcRequestError, (object)[
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
            $this->DispatchEvent(EventsContainer::RpcRequestError, (object)[
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

        if($requestMethod === 'OPTIONS') {
            // если это запрос на опции то вернуть
            $this->Finish($type, (object)['code' => 200, 'message' => 'ok', 'options' => true]);
        }
        else {
            $obj = new $class();
            $result = (object)$obj->$method($get, $post, $payload);    
            
            $this->DispatchEvent(EventsContainer::RpcRequestProcessed, (object)[
                'object' => $obj,
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'result' => $result
            ]);

            $this->Finish($type, $result);
        }

    }

}
