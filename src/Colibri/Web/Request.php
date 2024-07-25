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

use Colibri\Common\XmlHelper;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Common\VariableHelper;

/**
 * Request Class
 *
 * This class represents a web request and provides access to request parameters such as GET, POST, etc.
 *
 * @property-read RequestCollection $get The GET parameters.
 * @property-read RequestCollection $post The POST parameters.
 * @property-read RequestFileCollection $files The uploaded files.
 * @property-read RequestCollection $session The session parameters.
 * @property-read RequestCollection $server The server parameters.
 * @property-read RequestCollection $cookie The cookie parameters.
 * @property-read RequestCollection $headers The request headers.
 * @property-read RequestCollection $utm The UTM parameters.
 * @property-read string $remoteip The remote IP address.
 * @property-read string $uri The URI of the request.
 * @property-read string $host The host of the request.
 * @property-read string $address The full address of the request.
 * @property-read string $type The type of request (e.g., GET, POST).
 * @property-read bool $insecure Whether the request is insecure (not using HTTPS).
 *
 */
class Request
{

    // Event dispatcher trait
    use TEventDispatcher;

    /**
     * Singleton instance.
     *
     * @var Request|null
     */
    static ? Request $instance = null;

    /** @var string Type of payload: JSON */
    const PAYLOAD_TYPE_JSON = 'json';
    /** @var string Type of payload: XML */
    const PAYLOAD_TYPE_XML = 'xml';

    private bool $_encodedAsJson = false;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->DispatchEvent(EventsContainer::RequestReady);
        $this->_detectJsonEncodedData();
    }

    /**
     * Detects if the request payload is JSON encoded.
     *
     * @return void
     */
    private function _detectJsonEncodedData(): void
    {
        if (isset($_POST['json_encoded_data'])) {
            $json = $_POST['json_encoded_data'];
            $data = json_decode(base64_decode($json), true);
            $_POST = VariableHelper::Extend($_POST, $data);
            unset($_POST['json_encoded_data']);
            $this->_encodedAsJson = true;
        }
    }

    /**
     * Static constructor to create a new instance of Request.
     *
     * @return Request The Request instance.
     */
    public static function Create(): Request
    {
        if (!Request::$instance) {
            Request::$instance = new Request();
        }
        return Request::$instance;
    }

    /**
     * Returns the URI with added or removed parameters.
     *
     * @param array $add Parameters to add.
     * @param array $remove Parameters to remove.
     * @return string The modified URI.
     *
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
     * Magic getter method to access request properties.
     *
     * @param string $prop The property name.
     * @return mixed The value of the property.
     *
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
                    } else {
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
                    if ($this->get->{'utm_medium'} ? $this->get->{'utm_medium'} : $this->cookie->{'utm_medium'}) {
                        $utm['utm_medium'] = $this->get->{'utm_medium'} ? $this->get->{'utm_medium'} : $this->cookie->{'utm_medium'};
                    }
                    if ($this->get->{'utm_source'} ? $this->get->{'utm_source'} : $this->cookie->{'utm_source'}) {
                        $utm['utm_source'] = $this->get->{'utm_source'} ? $this->get->{'utm_source'} : $this->cookie->{'utm_source'};
                    }
                    if ($this->get->{'utm_term'} ? $this->get->{'utm_term'} : $this->cookie->{'utm_term'}) {
                        $utm['utm_term'] = $this->get->{'utm_term'} ? $this->get->{'utm_term'} : $this->cookie->{'utm_term'};
                    }
                    if ($this->get->{'utm_content'} ? $this->get->{'utm_content'} : $this->cookie->{'utm_content'}) {
                        $utm['utm_content'] = $this->get->{'utm_content'} ? $this->get->{'utm_content'} : $this->cookie->{'utm_content'};
                    }
                    if ($this->get->{'utm_campaign'} ? $this->get->{'utm_medium'} : $this->cookie->{'utm_campaign'}) {
                        $utm['utm_campaign'] = $this->get->{'utm_campaign'} ? $this->get->{'utm_medium'} : $this->cookie->{'utm_campaign'};
                    }
                    $return = new RequestCollection($utm);
                    break;
                }
            case 'remoteip': {
                    if ($this->server->{'HTTP_X_FORWARDED_FOR'}) {
                        $return = $this->server->{'HTTP_X_FORWARDED_FOR'};
                    } elseif ($this->server->{'REMOTE_ADDR'}) {
                        $return = $this->server->{'REMOTE_ADDR'};
                    } elseif ($this->server->{'X_REAL_IP'}) {
                        $return = $this->server->{'X_REAL_IP'};
                    } elseif ($this->server->{'HTTP_FORWARDED'}) {
                        $return = $this->server->{'HTTP_FORWARDED'};
                    } else {
                        $return = '';
                    }
                    break;
                }
            case 'uri': {
                    $return = $this->server->{'request_uri'} ? $this->server->{'request_uri'} : '';
                    break;
                }
            case 'host': {
                    $return = $this->server->{'http_host'} ? $this->server->{'http_host'} : '';
                    break;
                }
            case 'address': {
                    $proto = $this->server->{'https'} ? 'https://' : 'http://';
                    $return = $this->server->{'http_host'} ? $proto . $this->server->{'http_host'} : '';
                    break;
                }
            case 'insecure': {
                    $return = !$this->server->{'https'};
                    break;
                }
            case 'headers': {
                    if (function_exists('apache_request_headers')) {
                        $headers = apache_request_headers();
                    } else {
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
                    $return = $this->server->{'request_method'} ? $this->server->{'request_method'} : 'get';
                    break;
                }
            default: {
                    return null;
                }
        }
        return $return;
    }

    /**
     * Returns a copy of the request payload as a PayloadCopy object.
     *
     * @param string $type The type of result (json, xml).
     * @return PayloadCopy The payload copy.
     *
     */
    public function GetPayloadCopy(string $type = Request::PAYLOAD_TYPE_JSON): PayloadCopy
    {
        return new PayloadCopy($type);
    }

    /**
     * Generates a unique request ID based on request data.
     *
     * @return string The unique request ID.
     */
    public function GetUniqueRequestId(): string
    {

        $requestData = [
            $_GET,
            $_POST,
            $_COOKIE
        ];

        $requestData = json_encode($requestData);

        return md5($requestData);

    }

    public function ModifyHeaders(array $headers = [])
    {
        foreach($headers as $headerName => $headerValue) {
            $_SERVER['HTTP_' . $headerName] = $headerValue;
        }
    }

    public function ExtractFiles(array $fileKeys): array
    {
        $files = [];
        foreach($fileKeys as $fileKey) {
            $files[] = $this->ExtractFile($fileKey);
        }
        return $files;
    }

    public function ExtractFile(string $fileKey): ?RequestedFile
    {
        if($this->files->$fileKey) {
            return $this->files->$fileKey;
        }

        $fileKey = str_replace('file(', '', $fileKey);
        $fileKey = str_replace(')', '', $fileKey);
        if($this->files->$fileKey) {
            return $this->files->$fileKey;
        }

        return null;
    }

}