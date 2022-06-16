<?php

/**
 * Класс запроса
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Web
 * @version 1.0.0
 * 
 */

namespace Colibri\Web;

use Colibri\Common\XmlHelper;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Common\VariableHelper;

/**
 * Класс запроса
 * 
 * @property-read RequestCollection $get
 * @property-read RequestCollection $post
 * @property-read RequestFileCollection $files
 * @property-read RequestCollection $session
 * @property-read RequestCollection $server
 * @property-read RequestCollection $cookie
 * @property-read RequestCollection $headers
 * @property-read RequestCollection $utm
 * @property-read string $remoteip
 * @property-read string $uri
 * @property-read string $host
 * @property-read string $address
 * @property-read string $type
 * @property-read bool $insecure
 *  
 * @testFunction testRequest
 */
class Request
{

    // подключаем функционал событийной модели
    use TEventDispatcher;

    /**
     * Singlton
     *
     * @var Request
     *
     */
    static ?Request $instance = null;

    /** Тип запроса JSON */
    const PAYLOAD_TYPE_JSON = 'json';
    /** Тип запроса XML */
    const PAYLOAD_TYPE_XML = 'xml';

    private bool $_encodedAsJson = false;

    /**
     * Конструктор
     */
    private function __construct()
    {
        $this->DispatchEvent(EventsContainer::RequestReady);
        $this->_detectJsonEncodedData();
    }

    private function _detectJsonEncodedData(): void
    {
        if(isset($_POST['json_encoded_data'])) {
            $json = $_POST['json_encoded_data'];
            $data = json_decode(base64_decode($json), true);
            $_POST = VariableHelper::Extend($_POST, $data);
            unset($_POST['json_encoded_data']);
            $this->_encodedAsJson = true;
        }
    }

    /**
     * Статический контруктор
     *
     * @return Request
     * @testFunction testRequestCreate
     */
    public static function Create(): Request
    {
        if (!Request::$instance) {
            Request::$instance = new Request();
        }
        return Request::$instance;
    }

    /**
     * Возвращает URI с добавлением или удалением параметров
     *
     * @param array $add
     * @param array $remove
     * @return string
     * @testFunction testRequestUri
     */
    public function Uri(array $add = array(), array $remove = array()): string
    {
        $get = $this->get->ToArray();
        foreach ($remove as $v) {
            unset($get[$v]);
        }
        foreach ($add as $k => $v) {
            $get[$k] = $v;
        }
        $url = '';
        foreach ($get as $k => $v) {
            $url .= '&' . $k . '=' . $v;
        }
        return '?' . substr($url, 1);
    }

    /**
     * Магический метод
     *
     * @param string $prop
     * @return mixed
     * @testFunction testRequest__get
     */
    public function __get(string $prop): mixed
    {
        $prop = strtolower($prop);
        $return = null;
        switch ($prop) {
            case 'get': {
                    $return = new RequestCollection($_GET);
                    break;
                }
            case 'post': {
                    $return = new RequestCollection($_POST, !$this->_encodedAsJson);
                    break;
                }
            case 'files': {
                    $return = new RequestFileCollection($_FILES);
                    break;
                }
            case 'session': {
                    if (isset($_SESSION)) {
                        $return = new RequestCollection($_SESSION);
                    }
                    else {
                        $return = new RequestCollection([]);
                    }
                    break;
                }
            case 'server': {
                    $return = new RequestCollection($_SERVER);
                    break;
                }
            case 'cookie': {
                    $return = new RequestCollection($_COOKIE);
                    break;
                }
            case 'utm': {
                    $utm = [];
                    if ($this->get->utm_medium ? $this->get->utm_medium : $this->cookie->utm_medium) {
                        $utm['utm_medium'] = $this->get->utm_medium ? $this->get->utm_medium : $this->cookie->utm_medium;
                    }
                    if ($this->get->utm_source ? $this->get->utm_source : $this->cookie->utm_source) {
                        $utm['utm_source'] = $this->get->utm_source ? $this->get->utm_source : $this->cookie->utm_source;
                    }
                    if ($this->get->utm_term ? $this->get->utm_term : $this->cookie->utm_term) {
                        $utm['utm_term'] = $this->get->utm_term ? $this->get->utm_term : $this->cookie->utm_term;
                    }
                    if ($this->get->utm_content ? $this->get->utm_content : $this->cookie->utm_content) {
                        $utm['utm_content'] = $this->get->utm_content ? $this->get->utm_content : $this->cookie->utm_content;
                    }
                    if ($this->get->utm_campaign ? $this->get->utm_medium : $this->cookie->utm_campaign) {
                        $utm['utm_campaign'] = $this->get->utm_campaign ? $this->get->utm_medium : $this->cookie->utm_campaign;
                    }
                    $return = new RequestCollection($utm);
                    break;
                }
            case 'remoteip': {
                    if ($this->server->HTTP_X_FORWARDED_FOR) {
                        $return = $this->server->HTTP_X_FORWARDED_FOR;
                    }
                    else if ($this->server->REMOTE_ADDR) {
                        $return = $this->server->REMOTE_ADDR;
                    }
                    else if ($this->server->X_REAL_IP) {
                        $return = $this->server->X_REAL_IP;
                    }
                    else if ($this->server->HTTP_FORWARDED) {
                        $return = $this->server->HTTP_FORWARDED;
                    }
                    else {
                        $return = '';
                    }
                    break;
                }
            case 'uri': {
                    $return = $this->server->request_uri ? $this->server->request_uri : '';
                    break;
                }
            case 'host': {
                    $return = $this->server->http_host ? $this->server->http_host : '';
                    break;
                }
            case 'address': {
                    $proto = $this->server->https ? 'https://' : 'http://';
                    $return = $this->server->http_host ? $proto . $this->server->http_host : '';
                    break;
                }
            case 'insecure': {
                    $return = !$this->server->https;
                    break;
                }
            case 'headers': {
                    if (function_exists('apache_request_headers')) {
                        $headers = apache_request_headers();
                    }
                    else {
                        $headers = [];
                        foreach ($this->server as $key => $value) {
                            if (strpos($key, 'http_') === 0) {
                                $headers[substr($key, 5)] = $value;
                            }
                        }
                    }
                    $return = new RequestCollection($headers);
                    break;
                }
            case 'type': {
                    $return = $this->server->request_method ? $this->server->request_method : 'get';
                    break;
                }
            default: {
                    return null;
                }
        }
        return $return;
    }

    /**
     * Возвращает копию RequestPayload в виде обьекта или SimpleXMLElement
     * 
     * @param string $type тип результата
     * @return PayloadCopy
     * 
     * @testFunction testRequestGetPayloadCopy
     */
    public function GetPayloadCopy(string $type = Request::PAYLOAD_TYPE_JSON): PayloadCopy
    {
        return new PayloadCopy($type);
    }


}
