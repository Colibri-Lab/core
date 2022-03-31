<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
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
 * Класс запроса
 * @testFunction testRequest
 */
class Request
{

    /** Разделитель */
    const Boundary = '---------------------------';
    /** Окончание */
    const BoundaryEnd = '--';

    /**
     * Логины и пароли
     *
     * @var Credentials
     */
    public ?Credentials $credentials;

    /**
     * Адрес
     *
     * @var string
     */
    public string $target;
    /**
     * Метод
     *
     * @var string
     */
    public string $method = Type::Get;
    /**
     * Данные
     *
     * @var Data | string | null
     */
    public Data|string|null $postData = null;
    /**
     * Шифрование
     *
     * @var string
     */
    public string $encryption = Encryption::UrlEncoded;
    /**
     * Разделитель
     *
     * @var string
     */
    public ?string $boundary = null;
    /**
     * Таймаут запроса
     *
     * @var integer
     */
    public int $timeout = 60;
    /**
     * Таймаут в миллисекундах
     *
     * @var int|null
     */
    public ?int $timeout_ms = null;
    /**
     * Индикатор ассинхронности
     *
     * @var boolean
     */
    public bool $async = false;
    /**
     * Куки
     *
     * @var array
     */
    public array $cookies = [];
    /**
     * Файл куки
     *
     * @var string
     */
    public string $cookieFile = '';
    /**
     * Реферер
     *
     * @var string
     */
    public string $referer = '';
    /**
     * Заголовки
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
     * Проверять сертификат
     *
     * @var boolean
     */
    public bool $sslVerify = true;

    /**
     * Checks if the curl module loaded
     *
     */
    /**
     * @testFunction testRequest__checkWebRequest
     */
    private static function __checkWebRequest(): bool
    {
        return function_exists('curl_init');
    }

    /**
     * Конструктор
     *
     * @param string $target
     * @param string $method
     * @param string $encryption
     * @param Data $postData
     * @param string $boundary
     */
    public function __construct(
        string $target,
        string $method = Type::Get,
        string $encryption = Encryption::UrlEncoded,
        mixed $postData = null,
        string $boundary = ''
        )
    {

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
     * Создает данные запроса типа Multipart/Formdata
     *
     * @param string $boundary разделитель
     * @param mixed $files данные
     * @return string|array
     * @testFunction testRequest_createMultipartRequestBody
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
                    . 'Content-Type: ' . $content->mime . $eol
                    . 'Content-Transfer-Encoding: binary' . $eol;

                $data .= $eol;
                $data .= $content->value . $eol;
            }
            else if ($content instanceof DataItem) {
                $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $content->name . "\"" . $eol . $eol
                    . $content->value . $eol;
            }
        }

        return $data . "--" . $delimiter . Request::BoundaryEnd . $eol;
    }

    /**
     * Собирает пост
     *
     * @return string|array
     * @testFunction testRequest_joinPostData
     */
    private function _joinPostData(): string|array
    {
        $return = null;
        $data = array();

        if ($this->encryption == Encryption::Multipart) {
            return $this->_createMultipartRequestBody($this->boundary, $this->postData);
        }
        else if ($this->encryption == Encryption::XmlEncoded) {
            $return = VariableHelper::IsString($this->postData) ? $this->postData : XmlHelper::Encode($this->postData, null, false);
        }
        else if ($this->encryption == Encryption::JsonEncoded) {
            $return = VariableHelper::IsString($this->postData) ? $this->postData : json_encode($this->postData);
        }
        else {

            foreach ($this->postData as $value) {
                $data[] = $value->name . '=' . rawurlencode($value->value);
            }
            $return = implode("&", $data);
        }

        return $return;
    }

    /**
     * Обрабатывает полученный из curl результат и вырезает заголовки
     * @param string $body тело результата вместе с заголовками
     * @param int $header_size размер заголовков
     * @return (string|array)[] 
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
     * Выполняет запрос
     *
     * @param mixed $postData
     * @return Result
     * @testFunction testRequestExecute
     */
    public function Execute(mixed $postData = null) : Result
    {

        if (!VariableHelper::IsNull($postData)) {
            $this->postData = $postData;
        }

        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $this->target);
        if (!$this->async) {
            curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
        }
        else {
            if ($this->timeout_ms) {
                curl_setopt($handle, CURLOPT_TIMEOUT_MS, $this->timeout_ms ? $this->timeout_ms : 100);
            }
            else {
                curl_setopt($handle, CURLOPT_TIMEOUT_MS, $this->timeout ? $this->timeout * 1000 : 100);
            }
            curl_setopt($handle, CURLOPT_NOSIGNAL, 1);
        }

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);

        if (!empty($this->referer)) {
            curl_setopt($handle, CURLOPT_REFERER, $this->referer);
        }
        else {
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
            $_headers[] = "Cookie: " . is_array($this->cookies) ? http_build_query($this->cookies, '', '; ') : $this->cookies;
        }

        if ($this->encryption == Encryption::Multipart) {
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($handle, CURLOPT_SAFE_UPLOAD, true);
            $_headers[] = "Content-Type: multipart/form-data";
        }
        else if ($this->encryption == Encryption::JsonEncoded) {
            $_headers[] = "Content-Type: application/json";
        }
        else if ($this->encryption == Encryption::XmlEncoded) {
            $_headers[] = "Content-Type: application/xml";
        }

        if ($this->method == Type::Post) {
            curl_setopt($handle, CURLOPT_POST, true);
            if (!VariableHelper::IsNull($this->postData)) {
                $data = $this->_joinPostData($this->postData);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
            }
        }
        else if ($this->method == Type::Get) {
            curl_setopt($handle, CURLOPT_HTTPGET, true);
        }
        else {
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, StringHelper::ToUpper($this->method));
            if (!VariableHelper::IsNull($this->postData)) {
                $data = $this->_joinPostData($this->postData);
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
}
