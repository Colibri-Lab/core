<?php

/**
 * Main ReactPhp server class.
 *
 * This class represents the core of the application.
 *
 * @author Vagan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package App
 * @version 1.0.0
 */

namespace Colibri;

use Colibri\App;
use Colibri\Common\Encoding;
use Colibri\Common\ErrorHelper;
use Colibri\Common\HtmlHelper;
use Colibri\Common\MimeType;
use Colibri\Common\NoLangHelper;
use Colibri\Common\VariableHelper;
use Colibri\Common\XmlHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Data\Storages\Fields\DateTimeField;
use Colibri\Events\Event;
use Colibri\Events\EventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Web\Request;
use Colibri\Web\RequestCollection;
use Colibri\Web\Response;
use Colibri\Web\StringStream;
use Colibri\Web\WebUtils;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response as MessageResponse;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Socket\SecureServer;
use React\Socket\SocketServer;

class ReactServer
{
    public static function HandleRequest(ServerRequestInterface $psrRequest): MessageResponse
    {
        $request = new Request($psrRequest);
        $response = new Response();
        App::Instance()->Initialize($request, $response, true);

        $cmd = $request->uri;
        [
            $type,
            $class,
            $method,
            $isRequestTyped
        ] = WebUtils::ParseCommand($cmd);

        if ((!class_exists($class) || !method_exists($class, $method))) {

            // if the request is not exists and it's a file in web root then return it as file
            if(File::Exists(App::$webRoot . $cmd)) {
                $path = App::$webRoot . $cmd;
                $md5 = File::Md5($path);

                // ETag = md5 от файла
                $etag = '"etag' . $md5 . '"';

                // Проверка If-None-Match
                $ifNoneMatch = $request->headers->{'If-None-Match'};
                if ($ifNoneMatch === $etag) {
                    return static::ServerFinish(
                        $psrRequest,
                        WebUtils::Stream,
                        [
                            'code' => 304,
                            'result' => File::Read($path),
                            'message' => basename($cmd),
                            'headers' => [
                                'ETag' => $etag,
                                'Cache-Control' => 'private',
                                'Access-Control-Allow-Origin' => '*',
                            ]
                        ]
                    );
                }

                return static::ServerFinish(
                    $psrRequest,
                    WebUtils::Stream,
                    [
                        'code' => 200,
                        'result' => File::Read($path),
                        'message' => basename($cmd),
                        'headers' => [
                            'ETag' => $etag
                        ]
                    ]
                );
            }
            // если не нашли чего делать то пробуем по умолчанию
            [$type, $class, $method, $isRequestTyped] = WebUtils::ParseCommand('/');
        }

        $headers = App::$request->headers;
        $requestMethod = App::$request->server->{'request_method'} ?? 'GET';
        $get = App::$request->get;
        $post = App::$request->post;
        $payload = App::$request->GetPayloadCopy();
        
        if($requestMethod === 'OPTIONS') {
            return new MessageResponse(
                200,
                [
                    'Access-Control-Allow-Origin' =>  $headers->origin ?? '*',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Allow-Headers' => $headers->{'access-control-request-headers'} ?? '*',
                    'Access-Control-Allow-Method' => $headers->{'access-control-request-method'} ?? '*'
                ],
                ''
            );
        }

        if(App::HasCsfrInRequest($headers) && !App::CsfrIsCorrect($headers)) {

            $message = 'CSFR token is incorrect';

            EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcRequestError), (object) [
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'message' => $message
            ]);

            return self::ServerFinish($psrRequest, $type, (object) [
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
        EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcGotRequest), $args);
        if (isset($args->cancel) && $args->cancel === true) {
            $result = isset($args->result) ? $args->result : (object) [];
            return self::ServerFinish($psrRequest, $type, $result);
        }

        if (!class_exists($class)) {

            $message = 'Unknown class ' . $class;
            EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcRequestError), (object) [
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'message' => $message
            ]);

            return self::ResponseWithError($psrRequest, $type, $message, WebUtils::IncorrectCommandObject, $cmd, [
                'message' => $message,
                'code' => WebUtils::IncorrectCommandObject,
                'get' => $get,
                'post' => $post,
                'payload' => $payload
            ]);

        }

        if (!method_exists($class, $method)) {
            $message = 'Unknown method ' . $method . ' in object ' . $class;
            EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcRequestError), (object) [
                'class' => $class,
                'method' => $method,
                'get' => $get,
                'post' => $post,
                'payload' => $payload,
                'message' => $message
            ]);

            return self::ResponseWithError(
                $psrRequest,
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
        }

        if ($requestMethod === 'OPTIONS') {
            // если это запрос на опции то вернуть
            return self::ServerFinish($psrRequest, $type, (object) ['code' => 200, 'message' => 'ok', 'options' => true]);
        } else {



            try {
                $obj = new $class($type, $isRequestTyped);
                // ! нужно понять как выпустить его в мир
                // if(!$obj->waitForAnswer) {

                //     $payload->Cache();

                //     App::$response->Origin();
                //     App::$response->FinishRequest();

                // }

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
            EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcRequestProcessed), $args);

            if((($result?->type ?? null) ?: $type) !== WebUtils::Stream) {
                $args->result = NoLangHelper::ParseArray($args->result);
            }

            return self::ServerFinish($psrRequest, (($result?->type ?? null) ?: $type), $args->result);
        }

    }

    /**
     * Finishes the process and sends response.
     *
     * @param string $type The response type.
     * @param mixed $result The result to send.
     * @return MessageResponse
     */
    public static function ServerFinish(ServerRequestInterface $request, string $type, mixed $result): MessageResponse
    {
        $result = (object) $result;
        if (!isset($result->headers)) {
            $result->headers = [];
        }
        if (!isset($result->cookies)) {
            $result->cookies = [];
        }
        

        $headers = [
            'Access-Control-Allow-Origin' =>  $request->getHeaderLine('Origin') ?? '*',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Headers' => $request->getHeaderLine('access-control-request-headers') ?: '*',
            'Access-Control-Allow-Method' => $request->getHeaderLine('access-control-request-method') ?: '*'
        ];

        $headers = VariableHelper::Extend($headers, $result->headers ?? []);

        
        $mime = new MimeType($type);

        // if we responsing with file
        if (
            $type == WebUtils::Stream && $result?->result &&
            (\is_string($result->result) && \is_string($result->message))
        ) {
            $ext = strtolower(pathinfo($result->message, PATHINFO_EXTENSION));
            $mime = MimeType::Create($result->message);

            $headers = VariableHelper::Extend($headers, [
                'Content-Description' => 'File Transfer',
                'Content-Disposition' => 'attachment; filename="' . $result->message . '"',
                'Content-Transfer-Encoding' => 'binary',
                'Expires' => '0',
                'Cache-Control' => 'must-revalidate',
                'Content-Length' => \strlen($result->result),
                'Content-Type' => $mime->data ?: 'application/octet-stream'
            ]);

            $staticExtensions = [
                'jpg', 'jpeg', 'gif', 'png', 'ico',
                'mp3', 'css', 'zip', 'tgz', 'gz',
                'rar', 'bz2', 'doc', 'xls', 'exe',
                'pdf', 'dat', 'avi', 'ppt', 'txt',
                'tar', 'mid', 'midi', 'wav', 'bmp',
                'rtf', 'wmv', 'mpeg', 'mpg', 'tbz',
                'js', 'woff', 'ttf', 'eot', 'svg',
                'swf', 'webp', 'wasm'
            ];

            if (in_array($ext, $staticExtensions, true)) {

                $expires = gmdate(
                    'D, d M Y H:i:s',
                    time() + (168 * 3600)
                ) . ' GMT';

                $headers['Expires'] = $expires;
                $headers['Cache-Control'] = 'public';

            }

            $gzipExtensions = [
                'js',
                'css',
                'html',
                'json',
                'svg',
                'txt'
            ];

            $acceptEncoding = $request->getHeaderLine('Accept-Encoding');

            if (
                in_array($ext, $gzipExtensions, true) &&
                str_contains($acceptEncoding, 'gzip')
            ) {

                $result->result = gzencode($result->result, 6);
                $headers['Content-Encoding'] = 'gzip';
                $headers['Vary'] = 'Accept-Encoding';

            }

            return new MessageResponse($result->code ?: 200, $headers, new StringStream($result->result));

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

        $setCookies = [];
        $cookies = $result?->cookies ?? [];
        $cookies[] = (object)[
            'name' => App::$config->Query('session.name', 'sid')->GetValue(),
            'value' => App::$session->sid,
            'expire' => time() + App::$session->ttl,
            'domain' => App::$request->host,
            'path' => '/',
            'secure' => true,
            'samesite' => 'None'
        ];

        foreach($cookies as $cookie) {
            $cookie = (object)$cookie;
            $setCookies[] = '' . $cookie->name . '=' . $cookie->value . '; ' .
                'Expires=' . (isset($cookie->expire) ? gmdate('D, d M Y H:i:s T', $cookie->expire) : 'Session') . '; ' .
                'Path=' . ($cookie->path ?? '/') . '; ' .
                'Domain=' . ($cookie->domain ?? $request->getUri()->getHost()) . '; ' .
                (($cookie->secure ?? false) ? 'Secure; ' : '') .
                (($cookie->httponly ?? false) ? 'HttpOnly; ' : '');
        }

        $encoding = ($result?->charset ?? 'utf-8');
        $headers['Set-Cookie'] = $setCookies;
        $headers['Content-Type'] = ($mime->data ?? 'application/octet-stream') . '; charset=' . $encoding;
        $content = Encoding::Convert($content, $encoding, Encoding::UTF8);

        return new MessageResponse(
            $result->code ?: 200,
            VariableHelper::Extend($headers, $result?->headers ?? []),
            $content
        );

    }

    public static function ResponseWithError(
        ServerRequestInterface $request,
        string $type,
        string $message,
        int $code = -1,
        string $cmd = '',
        mixed $data = null
    ): MessageResponse {

        ErrorHelper::Telegram('@colibri_core_errors', 'Code: 404\nMessage: ' . $message . '\nResult: ' . ddrx([
            'code' => $code,
            'command' => $cmd,
            'data' => $data
        ]));

        return static::ServerFinish($request, $type, (object) [
            'code' => 404,
            'message' => $message,
            'result' => (object) [
                'code' => $code,
                'command' => $cmd,
                'data' => $data
            ]
        ]);
    }

    public static function Initialize(array $config, string $webPath)
    {

        $loop = Loop::get();
        
        $connectionCount = 0;
        if(!isset($config['limits'])) {
            $config['limits'] = [
                'concurent_connections' => 100,
                'body_buffer_size' => 1073741824,
                'body_parser' => [
                    'max_size' => 1073741824,
                    'max_files' => 100
                ]
            ];
        }

        $http = new HttpServer(
            new StreamingRequestMiddleware(),
            new LimitConcurrentRequestsMiddleware($config['limits']['concurent_connections'] ?? 100), 
            new RequestBodyBufferMiddleware($config['limits']['body_buffer_size'] ?? 1073741824), 
            new RequestBodyParserMiddleware($config['limits']['body_parser']['max_size'] ?? 1073741824, $config['limits']['body_parser']['max_files'] ?? 100),
            function (ServerRequestInterface $psrRequest) use ($webPath, &$connectionCount): ?MessageResponse {
                try {

                    $connectionCount ++;
                    $return = null;

                    $microtime = microtime(true);

                    $request = new Request($psrRequest);
                    $response = new Response();
                    App::Instance()->Initialize($request, $response, $webPath, true);

                    //app_debug("New request received: " . App::$request->address . ' ' . App::$request->uri);

                    echo "New request received: " . App::$request->address . ' ' . App::$request->uri . "\n";
                    flush();
                    $waitForAnswer = (App::$server->{'http_waitforanswer'} ?? 'true') === 'true';
                    if(!$waitForAnswer) {
                        Loop::futureTick(function () use ($psrRequest, &$connectionCount) {
                            self::HandleRequest($psrRequest);
                            $connectionCount--;
                        });
                    } else {
                        $return = self::HandleRequest($psrRequest);
                        $connectionCount--;
                    }

                    //app_debug("Response time: " . ((int)((microtime(true) - $microtime) * 1000)) . "ms");
                    echo "Response time: " . ((int)((microtime(true) - $microtime) * 1000)) . "ms\n";
                    flush();

                    return $return;

                } catch(\Throwable $e) {
                    echo "Error: " . $e->getMessage() . ' ' . $e->getTraceAsString() . "\n";
                    flush();

                    app_debug('Error in request handler: ' . $e->getMessage());
                    app_debug($e->getTraceAsString());
                    return new MessageResponse(500, ['Content-Type' => 'text/plain'], 'Internal Server Error');
                }
            }
        );


        if(isset($config['secure']) && \is_array($config['secure'])) {
            $socket = new SocketServer($config['secure']['listen'], [], $loop); // listen - ip:port
            $secure = new SecureServer($socket, $loop, [
                'local_cert' => $config['secure']['crt'],
                'local_pk'   => $config['secure']['key'],
                'allow_self_signed' => $config['secure']['selfsigned'] ?? true,
                'verify_peer' => $config['secure']['verifypeer'] ?? false,
            ]);
            $http->listen($secure);
        }

        if(isset($config['nonsecure']) && \is_array($config['nonsecure'])) {
            $socket80 = new SocketServer($config['nonsecure']['listen'], [], $loop);
            $http->listen($socket80);
        }

        if(isset($config['status']) && \is_array($config['status'])) {
            $httpStatus = new HttpServer(function (ServerRequestInterface $psrRequest) use (&$connectionCount): ?MessageResponse {
                try {
                    $result = [];
                    $load = sys_getloadavg();
                    $result['load'] = [
                        'last_1_minute' => $load[0],
                        'last_5_minutes' => $load[1],
                        'last_15_minutes' => $load[2]
                    ];
                    $result['memory'] = [
                        'internal' => memory_get_usage(false),
                        'real' => memory_get_usage(true)
                    ];
                    $result['connections'] = $connectionCount;

                    return new MessageResponse(
                        200,
                        ['Content-Type' => 'application/json'],
                        json_encode($result)
                    );

                } catch(\Throwable $e) {
                    echo "Error: " . $e->getMessage() . ' ' . $e->getTraceAsString() . "\n";
                    flush();

                    app_debug('Error in request handler: ' . $e->getMessage());
                    app_debug($e->getTraceAsString());
                    return new MessageResponse(500, ['Content-Type' => 'text/plain'], 'Internal Server Error');
                }
            });
            $socketStatus = new SocketServer($config['status']['listen'], [], $loop);
            $httpStatus->listen($socketStatus);
        }


        $loop->run();

    }




}
