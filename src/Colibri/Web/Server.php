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
use Colibri\Common\ErrorHelper;
use Colibri\Common\MimeType;
use Colibri\Common\XmlHelper;
use Colibri\Common\HtmlHelper;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Common\NoLangHelper;
use Colibri\Utils\Debug;
use Psr\Http\Message\ResponseInterface;

/**
 * Web server
 */
class Server
{
    use TEventDispatcher;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Do nothing
    }

    /**
     * Finishes the process and sends response.
     *
     * @param string $type The response type.
     * @param mixed $result The result to send.
     * @return void
     */
    protected function Finish(string $type, mixed $result): void
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
            $type == WebUtils::Stream && $result?->result &&
            (is_string($result->result) && is_string($result->message))
        ) {
            App::$response->DownloadFile($result->message, $result->result);
            return;
        }

        $content = $result?->message ?? $result?->result ?? '';
        if ($type == WebUtils::JSON || $type == WebUtils::Stream) {
            $content = json_encode($result?->result ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ($type === WebUtils::XML) {
            $content = XmlHelper::Encode($result?->result ?? []);
        } elseif ($type == WebUtils::HTML) {
            $content = $result?->message ?: HtmlHelper::Encode($result?->result ?? []);
        } elseif ($type == WebUtils::CSS) {
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

        ErrorHelper::Telegram('@colibri_core_errors', 'Code: 404\nMessage: ' . $message . '\nResult: ' . ddrx([
            'code' => $code,
            'command' => $cmd,
            'data' => $data
        ]));

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
        list($type, $class, $method, $isRequestTyped) = WebUtils::ParseCommand($cmd);

        if (!VariableHelper::IsNull($default) && (!class_exists($class) || !method_exists($class, $method))) {
            // если не нашли чего делать то пробуем по умолчанию
            list($type, $class, $method, $isRequestTyped) = WebUtils::ParseCommand($default);
        }

        $requestMethod = App::$request->server->{'request_method'};
        $waitForAnswer = (App::$request->server?->{'http_waitforanswer'} ?? 'true') === 'true';
        $get = App::$request->get;
        $post = App::$request->post;
        $payload = App::$request->GetPayloadCopy();

        
        if(!$waitForAnswer) {
            $payload->Cache();

            App::$response->Origin();
            App::$response->FinishRequest();

        }

        
        if(App::HasCsfrInRequest() && !App::CsfrIsCorrect()) {
            $message = 'CSFR token is incorrect';
            $this->DispatchEvent(EventsContainer::RpcRequestError, (object) [
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'message' => $message
            ]);
            $this->Finish($type, [
                'code' => 403,
                'message' => $message
            ]);
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

            $this->_responseWithError($type, $message, WebUtils::IncorrectCommandObject, $cmd, [
                'message' => $message,
                'code' => WebUtils::IncorrectCommandObject,
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
                WebUtils::UnknownMethodInObject,
                $cmd,
                [
                    'message' => $message,
                    'code' => WebUtils::UnknownMethodInObject,
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
                $obj = new $class($type, $isRequestTyped);
                if(!$obj->waitForAnswer) {

                    $payload->Cache();

                    App::$response->Origin();
                    App::$response->FinishRequest();

                }
                
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

                $sendToTelegram = true;
                $arrayExclude = App::$config->Query('errors.exclude', [])->ToArray();
                if($arrayExclude && !empty($arrayExclude)) {
                    foreach($arrayExclude as $classFilter) {
                        if(strstr(get_class($e), $classFilter) !== false) {
                            $sendToTelegram = false;
                            break;
                        }
                    }
                }

                $sendToTelegram && ErrorHelper::Telegram(
                    '@colibri_core_errors',
                    '<b style="color: red">' . $class . '\\' . $method . '.' . $type . "</b>\n".
                    '<b>Server:</b> ' . App::$request->host . "\n\n" .
                    '<b>Trace:</b> ' . $e->getTraceAsString() . "\n\n" .
                    '<b>Params:</b> ' . json_encode([$get->ToArray(), $post->ToArray(), $payload->ToArray()]) . "\n" .
                    '<b>Response:</b> ' . $code . ', ' . $message . "\n" .
                    '<b>Result:</b> ' . json_encode($result) . "\n"
                );

            }

            $args = (object) [
                'object' => $obj,
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'result' => $result,
                'type' => ($result?->type ?? null) ?: $type
            ];
            $this->DispatchEvent(EventsContainer::RpcRequestProcessed, $args);

            if((($result?->type ?? null) ?: $type) !== WebUtils::Stream) {
                $args->result = NoLangHelper::ParseArray($args->result);
            }

            App::$monitoring->EndTimer('web-request');

            $this->Finish((($result?->type ?? null) ?: $type), $args->result);
        }

    }
}
