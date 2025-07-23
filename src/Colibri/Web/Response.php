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

use Colibri\Common\Encoding;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Common\MimeType;
use Colibri\Common\StringHelper;
use Colibri\Utils\Singleton;
use IteratorAggregate;
use Colibri\App;

/**
 * Response Class
 *
 * Represents a class responsible for output.
 *
 */
final class Response extends Singleton
{
    // Event dispatcher functionality
    use TEventDispatcher;

    /**
     * HTTP response status codes and their descriptions.
     *
     * @var array
     */
    public static $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // WebDAV; RFC 2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        // since HTTP/1.1
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        // WebDAV; RFC 4918
        208 => 'Already Reported',
        // WebDAV; RFC 5842
        226 => 'IM Used',
        // RFC 3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        // since HTTP/1.1
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // since HTTP/1.1
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        // since HTTP/1.1
        308 => 'Permanent Redirect',
        // approved as experimental RFC
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
        418 => 'I\'m a teapot',
        // RFC 2324
        419 => 'Authentication Timeout',
        // not in RFC 2616
        420 => 'Enhance Your Calm',
        // Twitter
        422 => 'Unprocessable Entity',
        // WebDAV; RFC 4918
        423 => 'Locked',
        // WebDAV; RFC 4918
        424 => 'Failed Dependency',
        // WebDAV; RFC 4918
        425 => 'Unordered Collection',
        // Internet draft
        426 => 'Upgrade Required',
        // RFC 2817
        428 => 'Precondition Required',
        // RFC 6585
        429 => 'Too Many Requests',
        // RFC 6585
        431 => 'Request Header Fields Too Large',
        // RFC 6585
        444 => 'No Response',
        // Nginx
        449 => 'Retry With',
        // Microsoft
        450 => 'Blocked by Windows Parental Controls',
        // Microsoft
        451 => 'Redirect',
        // Microsoft
        494 => 'Request Header Too Large',
        // Nginx
        495 => 'Cert Error',
        // Nginx
        496 => 'No Cert',
        // Nginx
        497 => 'HTTP to HTTPS',
        // Nginx
        499 => 'Client Closed Request',
        // Nginx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        // RFC 2295
        507 => 'Insufficient Storage',
        // WebDAV; RFC 4918
        508 => 'Loop Detected',
        // WebDAV; RFC 5842
        509 => 'Bandwidth Limit Exceeded',
        // Apache bw/limited extension
        510 => 'Not Extended',
        // RFC 2774
        511 => 'Network Authentication Required',
        // RFC 6585
        598 => 'Network read timeout error',
        // Unknown
        599 => 'Network connect timeout error', // Unknown
    ];

    /**
     * Constructor (private to enforce singleton pattern).
     */
    protected function __construct()
    {
        $this->DispatchEvent(EventsContainer::ResponseReady);
    }


    /**
     * Add a header to the HTTP response.
     *
     * @param string $name The name of the header.
     * @param string $value The value of the header.
     * @return void
     * @testFunction testResponse_addHeader
     */
    private function _addHeader(string $name, string $value): void
    {
        header($name . ': ' . $value);
    }

    /**
     * Add multiple headers to the HTTP response.
     *
     * @param array $headers An associative array of headers (name => value).
     * @return void
     */
    private function _addHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            if ($value) {
                $this->_addHeader($name, $value);
            }
        }
    }

    /**
     * Disable caching for the HTTP response.
     *
     * @return Response The Response instance.
     */
    public function NoCache(): Response
    {
        // $this->_addHeader('Pragma', 'no-cache');
        $this->_addHeader('X-Accel-Expires', '0');
        return $this;
    }

    /**
     * Set the Content-Type header for the HTTP response.
     *
     * @param string $type The MIME type of the content.
     * @param string|null $encoding The character encoding of the content.
     * @return Response The Response instance.
     */
    public function ContentType(string $type, ?string $encoding = null): Response
    {
        $this->_addHeader('Content-type', $type . ($encoding ? "; charset=" . $encoding : ""));
        $this->_addHeader('X-Content-Type-Options', 'nosniff');
        return $this;
    }

    /**
     * Set the expiration time for the HTTP response.
     *
     * @param int $seconds The number of seconds until expiration.
     * @return Response The Response instance.
     */
    public function ExpiresAfter(int $seconds): Response
    {
        $this->_addHeader('Expires', gmdate("D, d M Y H:i:s GMT", time() - $seconds));
        return $this;
    }

    /**
     * Set the expiration time for the HTTP response.
     *
     * @param int $date The expiration date as a Unix timestamp.
     * @return Response The Response instance.
     */
    public function ExpiresAt(int $date): Response
    {
        $this->_addHeader('Expires', gmdate("D, d M Y H:i:s GMT", $date));
        return $this;
    }

    /**
     * Set caching options for the HTTP response.
     *
     * @param int $seconds The number of seconds to cache the response.
     * @return Response The Response instance.
     */
    public function Cache(int $seconds): Response
    {
        // $this->_addHeader('Pragma', 'no-cache');
        $this->_addHeader('X-Accel-Expires', $seconds);
        return $this;
    }

    /**
     * Set the P3P header for the HTTP response.
     *
     * @return Response The Response instance.
     */
    public function P3P(): Response
    {
        $this->_addHeader('P3P', 'CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
        return $this;
    }

    /**
     * Redirect the client to another URL.
     *
     * @param string $url The URL to redirect to.
     * @param int $status The HTTP status code for the redirect.
     * @return Response The Response instance.
     */
    public function Redirect(string $url, int $status = 301): Response
    {
        if ($status) {
            header('HTTP/1.1 ' . $status . ' ' . Response::$codes[$status]);
        }
        header('Location: ' . $url);
        return $this;
    }

    /**
     * Refresh the current page.
     *
     * @return Response The Response instance.
     */
    public function Refresh(): Response
    {
        header("Refresh:0");
        return $this;
    }

    /**
     * Add the Content-Description header to the HTTP response.
     *
     * @return Response The Response instance.
     */
    public function FileTransfer(): Response
    {
        $this->_addHeader('Content-Description', 'File Transfer');
        return $this;
    }

    /**
     * Set the Content-Disposition header for the HTTP response.
     *
     * @param string $type The type of disposition ('attachment', 'inline').
     * @param string $name The filename to be sent to the client.
     * @return Response The Response instance.
     */
    public function ContentDisposition(string $type, string $name): Response
    {
        $this->_addHeader('Content-Disposition', $type . '; filename="' . urlencode(StringHelper::Replace($name, '"', '')) . '"');
        return $this;
    }

    /**
     * Set the Content-Transfer-Encoding header for the HTTP response.
     *
     * @param string $type The transfer encoding type ('binary', 'base64', etc.).
     * @return Response The Response instance.
     */
    public function ContentTransferEncoding(string $type = 'binary'): Response
    {
        $this->_addHeader('Content-Transfer-Encoding', $type);
        return $this;
    }

    /**
     * Set the Pragma header for the HTTP response.
     *
     * @param string $type Type of pragma header
     * @return Response The Response instance.
     */
    public function Pragma(string $type = 'binary'): Response
    {
        // $this->_addHeader('Pragma', $type);
        return $this;
    }

    /**
     * Set the Content-Length header for the HTTP response.
     *
     * @param int $length The length of the content in bytes.
     * @return Response The Response instance.
     */
    public function ContentLength(int $length): Response
    {
        $this->_addHeader('Content-Length', $length);
        return $this;
    }

    /**
     * Set the Cache-Control header for the HTTP response.
     *
     * @param string $type The value of the Cache-Control header.
     * @return Response The Response instance.
     */
    public function CacheControl(string $type): Response
    {
        $this->_addHeader('Cache-Control', $type);
        return $this;
    }

    /**
     * Set a cookie in the HTTP response.
     *
     * @param string $name The name of the cookie.
     * @param string $value The value of the cookie.
     * @param int|null $expires The expiration time of the cookie in seconds.
     * @param string|null $path The path on the server in which the cookie will be available.
     * @param string|null $domain The domain that the cookie is available to.
     * @param bool|null $secure Indicates if the cookie should only be transmitted over a secure HTTPS connection.
     * @param bool|null $httponly Indicates if the cookie should only be accessible through HTTP protocol.
     * @return Response The Response instance.
     */
    public function Cookie(
        string $name,
        string $value,
        ?int $expires = 0,
        ?string $path = '',
        ?string $domain = '',
        ?bool $secure = false,
        ?bool $httponly = false
    ): Response {
        setcookie($name, $value, time() + $expires * 86400, $path, $domain, $secure, $httponly);
        return $this;
    }

    /**
     * Saves UTM cookie values to cookie
     * @param IteratorAggregate $utm
     * @return Response
     */
    public function SaveUTMCookies(IteratorAggregate $utm): Response
    {

        foreach ($utm as $key => $value) {
            $this->Cookie($key, $value, 7);
        }
        return $this;

    }

    /**
     * Returns error and stops
     *
     * @param string $content
     * @return void
     */
    public function Error404(string $content = ''): void
    {
        $this->Close(404, $content);
    }

    /**
     * Echo result and stop
     *
     * @param int $status status of response
     * @param string $content content of response
     * @return void
     */
    public function Close(int $status, string $content = '', string $type = 'text/html', string $encoding = 'utf-8', ?array $headers = [], ?array $cookies = []): void
    {
        try {
            header('HTTP/1.1 ' . $status . ' ' . (isset(Response::$codes[$status]) ? Response::$codes[$status] : 500));
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

                } else {
                    $this->_addHeader($header, urlencode($value));
                }
            }

            foreach ($cookies as $cookie) {
                $cookie = (object) $cookie;
                // (object)['name' => 'ss-jwt', 'value' => $session->jwt, 'expire' => time() + 365 * 86400, 'domain' => Request::$i->server->host, 'path' => '/', 'secure' => true]
                setcookie($cookie->name, $cookie->value, [
                    'expires' => $cookie->expire ?? 0,
                    'path' => $cookie->path ?? '',
                    'domain' => $cookie->domain ?? '',
                    'secure' => $cookie->secure ?? false,
                    'samesite' => $cookie->samesite ?? 'None'
                ]);
            }

            $this->ContentType($type, $encoding);
            echo Encoding::Convert($content, $encoding, Encoding::UTF8);
            exit;
        } catch (\Throwable $e) {
            exit;
        }
    }

    /**
     * Complex method to return a file
     *
     * @param string $filename a file name
     * @param string $filecontent file content
     * @return void
     */
    public function DownloadFile(string $filename, string $filecontent, bool $isPath = false): void
    {
        $mime = MimeType::Create($filename);
        $this->FileTransfer();
        $this->ContentDisposition('attachment', $filename);
        $this->ContentTransferEncoding('binary');
        $this->ExpiresAt(0);
        $this->CacheControl('must-revalidate');
        if($isPath) {
            $this->ContentType($mime->data ?: 'application/octet-stream', 'utf-8');
            readfile($filecontent);
        } else {
            $this->ContentLength(strlen($filecontent));
            $this->Close(200, $filecontent, $mime->data ?: 'application/octet-stream', 'utf-8');
        }
    }

    /**
     * Echo function analogue
     *
     * @return void
     */
    public function Write(): void
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            echo $arg;
        }
        flush();
    }

    /**
     * Writes origin to response
     */
    public function Origin(): void
    {
        $this->_addHeaders([
            'Access-Control-Allow-Origin' => App::$request->server->{'http_origin'},
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Headers' => App::$request->headers->{'access-control-request-headers'},
            'Access-Control-Allow-Method' => App::$request->headers->{'access-control-request-method'},
        ]);

    }

    public function FinishRequest(): void
    {
        $this->_addHeaders([
            "Connection: close",
            "Content-Encoding: none",
            "Content-Length: 21"
        ]);
        ignore_user_abort(true);
        echo 'Finished without exit';
        fastcgi_finish_request();
    }

}
