<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\Request
 */

namespace Colibri\IO\Request;

use Colibri\App;
use Colibri\IO\FileSystem\File;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Common\XmlHelper;
use Colibri\Utils\Debug;

/**
 * Class for handling web requests.
 *
 */
class Request
{
    /** Separator */
    public const Boundary = '---------------------------';
    /** Ending */
    public const BoundaryEnd = '--';

    /**
     * Logins and passwords
     *
     * @var Credentials|null
     */
    public ?Credentials $credentials;

    /**
     * Target address
     *
     * @var string
     */
    public string $target;

    /**
     * Method
     *
     * @var string
     */
    public string $method = Type::Get;

    /**
     * Data
     *
     * @var Data|string|null
     */
    public mixed $postData = null;

    /**
     * Encryption
     *
     * @var string
     */
    public string $encryption = Encryption::UrlEncoded;

    /**
     * Separator
     *
     * @var string|null
     */
    public ?string $boundary = null;

    /**
     * Request timeout
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * Timeout in milliseconds
     *
     * @var int|null
     */
    public ?int $timeout_ms = null;

    /**
     * Asynchronous indicator
     *
     * @var bool
     */
    public bool $async = false;

    /**
     * Cookies
     *
     * @var array
     */
    public array $cookies = [];

    /**
     * Cookie file
     *
     * @var string
     */
    public string $cookieFile = '';

    /**
     * Referer
     *
     * @var string
     */
    public string $referer = '';

    /**
     * Headers
     *
     * @var array|null
     */
    public ?array $headers = null;

    /**
     * UserAgent
     *
     * @var string
     */
    public string $useragent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30';

    /**
     * Check SSL certificate
     *
     * @var bool
     */
    public bool $sslVerify = true;

    /**
     * Content Type
     *
     * @var string|null
     */
    public ?string $contentType = null;

    /**
     * SSH security level
     *
     * @var int|null
     */
    public ?int $sshSecurityLevel = null;

    /**
     * Checks if the curl module loaded
     *
     */
    private static function __checkWebRequest(): bool
    {
        return function_exists('curl_init');
    }

    /**
     * Constructor
     *
     * @param string $target
     * @param string $method
     * @param string $encryption
     * @param Data|string|null $postData
     * @param string $boundary
     * @throws Exception
     */
    public function __construct(
        string $target,
        string $method = Type::Get,
        string $encryption = Encryption::UrlEncoded,
        mixed $postData = null,
        string $boundary = ''
    ) {

        if (!self::__checkWebRequest()) {
            throw new Exception('Can not load module curl.', 500);
        }

        // create boundary
        $this->boundary = !empty($boundary) ? $boundary : StringHelper::Randomize(8);

        $this->target = $target;
        $this->method = $method;
        $this->postData = $postData;

        $this->credentials = null;

        $this->encryption = $encryption;
    }

    /**
     * Creates request data of type Multipart/Formdata.
     *
     * @param string $boundary The boundary delimiter.
     * @param mixed $files The data.
     * @return string|array
     */
    private function _createMultipartRequestBody(string $boundary, mixed $files): string|array
    {
        $data = '';
        $eol = "\r\n";


        $delimiter = Request::Boundary . $boundary;
        foreach ($files as $content) {
            if ($content instanceof DataFile) {
                $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $content->name . '"; filename="' . $content->file . '"' . $eol
                    . 'Content-Transfer-Encoding: binary' . $eol;

                $data .= $eol;
                $data .= $content->value . $eol;
            } elseif ($content instanceof DataItem) {
                $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $content->name . "\"" . $eol . $eol
                    . $content->value . $eol;
            }
        }

        return $data . "--" . $delimiter . Request::BoundaryEnd . $eol;
    }

    /**
     * Constructs the POST data.
     *
     * @return string|array
     */
    private function _joinPostData(): string|array
    {
        $return = null;
        $data = array();

        if ($this->encryption == Encryption::Multipart) {
            return $this->_createMultipartRequestBody($this->boundary, $this->postData);
        } elseif ($this->encryption == Encryption::XmlEncoded) {
            $return = VariableHelper::IsString($this->postData) ?
                $this->postData : XmlHelper::Encode($this->postData);
        } elseif ($this->encryption == Encryption::JsonEncoded) {
            $return = VariableHelper::IsString($this->postData) ?
                $this->postData : json_encode($this->postData, JSON_UNESCAPED_UNICODE);
        } else {

            foreach ($this->postData as $value) {
                $data[] = $value->name . '=' . rawurlencode($value->value);
            }
            $return = implode("&", $data);
        }

        return $return;
    }

    /**
     * Processes the result received from cURL and extracts headers.
     *
     * @param string $body The result body along with headers
     * @param int $header_size The size of the headers
     * @return (string|array)[] An array containing the body and headers
     */
    private function _parseBody(string $body, int $header_size = 0): array
    {
        $httpheaders = substr($body, 0, $header_size);
        $body = substr($body, $header_size);

        $httpheaders = trim($httpheaders, "\r\n");
        $httpheaders = explode("\r\n\r\n", $httpheaders);
        $httpheaders = end($httpheaders);
        $httpheaders = explode("\r\n", $httpheaders);

        $headers_arr = array();
        array_shift($httpheaders);

        foreach ($httpheaders as $value) {
            if ($value && (($matches = explode(':', $value, 2)) !== false)) {
                $headers_arr[$matches[0]] = trim($matches[1]);
            }
        }

        return [$body, $headers_arr];

    }

    /**
     * Executes the request.
     *
     * @param mixed $postData The data to be posted
     * @return Result The result of the request
     */
    public function Execute(mixed $postData = null): Result
    {

        if (!VariableHelper::IsNull($postData)) {
            $this->postData = $postData;
        }

        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $this->target);
        if (!$this->async) {
            curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
        } else {
            if ($this->timeout_ms) {
                curl_setopt($handle, CURLOPT_TIMEOUT_MS, $this->timeout_ms ? $this->timeout_ms : 100);
            } else {
                curl_setopt($handle, CURLOPT_TIMEOUT_MS, $this->timeout ? $this->timeout * 1000 : 100);
            }
            curl_setopt($handle, CURLOPT_NOSIGNAL, 1);
        }

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        if($this->sshSecurityLevel) {
            curl_setopt($handle, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=' . $this->sshSecurityLevel);
        }

        if (!empty($this->referer)) {
            curl_setopt($handle, CURLOPT_REFERER, $this->referer);
        } else {
            curl_setopt($handle, CURLOPT_REFERER, $_SERVER['SERVER_NAME']);
        }
        if (!empty($this->cookieFile)) {
            if (!file_exists($this->cookieFile)) {
                touch($this->cookieFile);
            }
            curl_setopt($handle, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($handle, CURLOPT_COOKIEFILE, $this->cookieFile);
        }

        if (!VariableHelper::IsNull($this->credentials)) {
            curl_setopt($handle, CURLOPT_USERPWD, $this->credentials->login . ':' . $this->credentials->secret);
            if ($this->credentials->ssl) {
                curl_setopt($handle, CURLOPT_USE_SSL, true);
            }
        }

        $_headers = array(
            "Connection: Keep-Alive",
            'HTTP_X_FORWARDED_FOR: ' . App::$request->remoteip,
            'Expect:'
        );

        if ($this->cookies) {
            $_headers[] = "Cookie: " . is_array($this->cookies) ?
                http_build_query($this->cookies, '', '; ') : $this->cookies;
        }

        if ($this->encryption == Encryption::Multipart) {
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'POST');
            $_headers[] = "Content-Type: multipart/form-data; boundary=" . Request::Boundary . $this->boundary;
        } elseif ($this->encryption == Encryption::JsonEncoded) {
            $_headers[] = "Content-Type: application/json";
        } elseif ($this->encryption == Encryption::XmlEncoded) {
            $_headers[] = "Content-Type: " . ($this->contentType ?: "application/xml");
        }

        if ($this->method == Type::Post) {
            curl_setopt($handle, CURLOPT_POST, true);
            if (!VariableHelper::IsNull($this->postData)) {
                $data = $this->_joinPostData();
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($this->method == Type::Get) {
            if (!VariableHelper::IsNull($this->postData)) {
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'GET');
                $data = $this->_joinPostData();
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($handle, CURLOPT_HTTPGET, true);
            }
        } else {
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, StringHelper::ToUpper($this->method));
            if (!VariableHelper::IsNull($this->postData)) {
                $data = $this->_joinPostData();
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
            }
        }

        if (is_array($this->headers)) {
            $_headers = array_merge($this->headers, $_headers);
        }

        curl_setopt($handle, CURLOPT_HTTPHEADER, $_headers);

        if ($this->useragent) {
            curl_setopt($handle, CURLOPT_USERAGENT, $this->useragent);
        }

        curl_setopt($handle, CURLOPT_HEADER, true);

        if (!$this->sslVerify) {
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, $this->sslVerify);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $this->sslVerify);
        }

        $result = new Result();


        $body = curl_exec($handle);
        $header_size = curl_getinfo($handle, CURLINFO_HEADER_SIZE);

        list($body, $httpheaders) = $this->_parseBody($body, $header_size);

        $result->data = $body;
        $result->status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $result->error = curl_error($handle);
        $result->headers = curl_getinfo($handle);
        $result->httpheaders = $httpheaders;

        curl_close($handle);

        return $result;

    }

    /**
     * Sends a GET request.
     *
     * @param string $target The target URL
     * @param int $timeout The timeout for the request (default is 0)
     * @param bool $sslVerify Whether to verify SSL certificates (default is true)
     * @param array $headers Additional headers to be included in the request (default is an empty array)
     * @return Result The result of the request
     */
    public static function Get(
        string $target,
        int $timeout = 0,
        bool $sslVerify = true,
        array $headers = []
    ): Result {
        $req = new Request($target, Type::Get);
        $req->timeout = $timeout;
        $req->sslVerify = $sslVerify;
        if(!empty($headers)) {
            $req->headers = $headers;
        }
        return $req->Execute();
    }

}
