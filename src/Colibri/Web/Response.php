<?php

/**
 * Класс отвечающий за вывод
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Web
 * @version 1.0.0
 * 
 */

namespace Colibri\Web;

use Colibri\Common\Encoding;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Common\MimeType;
use Colibri\Common\StringHelper;

/**
 * Респонс 
 * @testFunction testResponse
 */
class Response
{

    // подключаем функционал событийной модели
    use TEventDispatcher;

    /**
     * Синглтон
     *
     * @var Response
     */
    static $instance;

    /**
     * Коды ответов
     */
    static $codes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing', // WebDAV; RFC 2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information', // since HTTP/1.1
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status', // WebDAV; RFC 4918
        208 => 'Already Reported', // WebDAV; RFC 5842
        226 => 'IM Used', // RFC 3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other', // since HTTP/1.1
        304 => 'Not Modified',
        305 => 'Use Proxy', // since HTTP/1.1
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect', // since HTTP/1.1
        308 => 'Permanent Redirect', // approved as experimental RFC
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot', // RFC 2324
        419 => 'Authentication Timeout', // not in RFC 2616
        420 => 'Enhance Your Calm', // Twitter
        422 => 'Unprocessable Entity', // WebDAV; RFC 4918
        423 => 'Locked', // WebDAV; RFC 4918
        424 => 'Failed Dependency', // WebDAV; RFC 4918
        425 => 'Unordered Collection', // Internet draft
        426 => 'Upgrade Required', // RFC 2817
        428 => 'Precondition Required', // RFC 6585
        429 => 'Too Many Requests', // RFC 6585
        431 => 'Request Header Fields Too Large', // RFC 6585
        444 => 'No Response', // Nginx
        449 => 'Retry With', // Microsoft
        450 => 'Blocked by Windows Parental Controls', // Microsoft
        451 => 'Redirect', // Microsoft
        494 => 'Request Header Too Large', // Nginx
        495 => 'Cert Error', // Nginx
        496 => 'No Cert', // Nginx
        497 => 'HTTP to HTTPS', // Nginx
        499 => 'Client Closed Request', // Nginx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates', // RFC 2295
        507 => 'Insufficient Storage', // WebDAV; RFC 4918
        508 => 'Loop Detected', // WebDAV; RFC 5842
        509 => 'Bandwidth Limit Exceeded', // Apache bw/limited extension
        510 => 'Not Extended', // RFC 2774
        511 => 'Network Authentication Required', // RFC 6585
        598 => 'Network read timeout error', // Unknown
        599 => 'Network connect timeout error', // Unknown
    );

    /**
     * Конструктор
     */
    private function __construct()
    {
        $this->DispatchEvent(EventsContainer::ResponseReady);
    }

    /**
     * Статический конструктор
     *
     * @return Response
     * @testFunction testResponseCreate
     */
    public static function Create()
    {
        if (!Response::$instance) {
            Response::$instance = new Response();
        }
        return Response::$instance;
    }

    /**
     * Добавить хедер
     *
     * @param string $name
     * @param string $value
     * @return void
     * @testFunction testResponse_addHeader
     */
    private function _addHeader($name, $value)
    {
        header($name . ': ' . $value);
    }

    /**
     * Добавить NoCache
     *
     * @return Response
     * @testFunction testResponseNoCache
     */
    public function NoCache()
    {
        $this->_addHeader('Pragma', 'no-cache');
        $this->_addHeader('X-Accel-Expires', '0');
        return $this;
    }

    /**
     * Добавить content-type
     *
     * @param string $type
     * @param string $encoding
     * @return Response
     * @testFunction testResponseContentType
     */
    public function ContentType($type, $encoding = false)
    {
        $this->_addHeader('Content-type', $type . ($encoding ? "; charset=" . $encoding : ""));
        return $this;
    }

    /**
     * Добавить expires
     *
     * @param int $seconds
     * @return Response
     * @testFunction testResponseExpiresAfter
     */
    public function ExpiresAfter($seconds)
    {
        $this->_addHeader('Expires', gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time() - $seconds));
        return $this;
    }

    /**
     * Добавить expires
     *
     * @param int $date
     * @return Response
     * @testFunction testResponseExpiresAt
     */
    public function ExpiresAt($date)
    {
        $this->_addHeader('Expires', gmstrftime("%a, %d %b %Y %H:%M:%S GMT", $date));
        return $this;
    }

    /**
     * Добавить cache-control и все остальные приблуды
     *
     * @param int $seconds
     * @return Response
     * @testFunction testResponseCache
     */
    public function Cache($seconds)
    {
        $this->_addHeader('Pragma', 'no-cache');
        $this->_addHeader('X-Accel-Expires', $seconds);
        return $this;
    }

    /**
     * Что то полезное
     *
     * @return Response
     * @testFunction testResponseP3P
     */
    public function P3P()
    {
        $this->_addHeader('P3P', 'CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
        return $this;
    }

    /**
     * Переадресация
     *
     * @param string $url
     * @return Response
     * @testFunction testResponseRedirect
     */
    public function Redirect($url)
    {
        header('Location: ' . $url);
        return $this;
    }

    /**
     * Перезагрузить страницу
     */
    public function Refresh()
    {
        header("Refresh:0");
        return $this;
    }

    /**
     * Добавить хедер Content-Description: File Transfer
     *
     * @return Response
     * @testFunction testResponseFileTransfer
     */
    public function FileTransfer()
    {
        $this->_addHeader('Content-Description', 'File Transfer');
        return $this;
    }

    /**
     * Добавить content-disposition
     *
     * @param string $type
     * @param string $name
     * @return Response
     * @testFunction testResponseContentDisposition
     */
    public function ContentDisposition($type, $name)
    {
        $this->_addHeader('Content-Disposition', $type . '; filename="' . StringHelper::Replace($name, '"', '') . '"');
        return $this;
    }

    /**
     * Добавтиь content-transfer-encoding
     *
     * @param string $type
     * @return Response
     * @testFunction testResponseContentTransferEncoding
     */
    public function ContentTransferEncoding($type = 'binary')
    {
        $this->_addHeader('Content-Transfer-Encoding', $type);
        return $this;
    }

    /**
     * Добавить pragma
     *
     * @param string $type
     * @return Response
     * @testFunction testResponsePragma
     */
    public function Pragma($type = 'binary')
    {
        $this->_addHeader('Pragma', $type);
        return $this;
    }

    /**
     * Добавтить content-length
     *
     * @param int $length
     * @return Response
     * @testFunction testResponseContentLength
     */
    public function ContentLength($length)
    {
        $this->_addHeader('Content-Length', $length);
        return $this;
    }

    /**
     * Добавить cache-control
     *
     * @param string $type
     * @return Response
     * @testFunction testResponseCacheControl
     */
    public function CacheControl($type)
    {
        $this->_addHeader('Cache-Control', $type);
        return $this;
    }

    /**
     * Выставляет куки
     * @param mixed $name Название cookie
     * @param mixed $value Значение cookie
     * @param int $expires Количество дней жизни куки
     * @param string $path Путь к директории на сервере, из которой будут доступны cookie
     * @param string $domain (Под)домен, которому доступны cookie.
     * @param bool $secure Указывает на то, что значение cookie должно передаваться от клиента по защищённому соединению HTTPS
     * @param bool $httponly Если задано true, cookie будут доступны только через HTTP-протокол
     * @return Response 
     */
    public function Cookie($name, $value, $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    {
        setcookie($name, $value, time() + $expires * 86400, $path, $domain, $secure, $httponly);
        return $this;
    }

    /**
     * Сохраняет UTM в куки
     * @param RequestCollection|array|object $utm 
     * @return void 
     */
    public function SaveUTMCookies($utm)
    {

        foreach ($utm as $key => $value) {
            $this->Cookie($key, $value, 7);
        }

    }

    /**
     * Вернуть ошибку и остановится 
     *
     * @param string $content
     * @return void
     * @testFunction testResponseError404
     */
    public function Error404($content = '')
    {
        $this->Close(404, $content);
    }

    /**
     * Выдать ответ с результатом
     *
     * @param int $status
     * @param string $content
     * @return void
     * @testFunction testResponseClose
     */
    public function Close($status, $content = '', $type = 'text/html', $encoding = 'utf-8', $headers = [])
    {
        try {
            header('HTTP/1.1 ' . $status . ' ' . Response::$codes[$status]);
            foreach ($headers as $header => $value) {
                if (is_object($value)) {
                    // значит это обьект, и мы передаем еще нужно ли кодировать значение
                    if (!isset($value->value)) {
                        $value->value = '';
                    }

                    if (isset($value->encode) && $value->encode) {
                        $value->value = urlencode($value->value);
                    }

                    $this->_addHeader($header, $value->value);

                }
                else {
                    $this->_addHeader($header, urlencode($value));
                }
            }
            $this->ContentType($type, $encoding);
            echo Encoding::Convert($content, $encoding, Encoding::UTF8);
            exit;
        }
        catch (\Throwable $e) {
            exit;
        }
    }

    /**
     * Переадресация на загрузку файла
     *
     * @param string $filename
     * @param string $filecontent
     * @return void
     * @testFunction testResponseDownloadFile
     */
    public function DownloadFile($filename, $filecontent)
    {
        $mime = MimeType::Create($filename);
        $this->FileTransfer();
        $this->ContentDisposition('attachment', $filename);
        $this->ContentTransferEncoding('binary');
        $this->ExpiresAt(0);
        $this->CacheControl('must-revalidate');
        $this->Pragma('public');
        $this->ContentLength(strlen($filecontent));
        $this->Close(200, $filecontent, $mime->data, 'utf-8');
    }

    /**
     * Аналог функции echo
     *
     * @return void
     * @testFunction testResponseWrite
     */
    public function Write()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            echo $arg;
        }
        flush();
    }
}
