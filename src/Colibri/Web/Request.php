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
use Colibri\Common\XmlHelper;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Singleton;
use Psr\Http\Message\ServerRequestInterface;
use Clue\React\Multicast\Factory;
use Colibri\Common\MimeType;

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
final class Request extends Singleton
{
    // Event dispatcher trait
    use TEventDispatcher;

    /** @var string Type of payload: JSON */
    public const PAYLOAD_TYPE_JSON = 'json';
    /** @var string Type of payload: XML */
    public const PAYLOAD_TYPE_XML = 'xml';

    private bool $_encodedAsJson = false;

    private ?ServerRequestInterface $_psrRequest = null;

    private ?array $_post = null;
    private ?array $_get = null;
    private ?array $_files = null;
    private ?array $_server = null;
    private ?array $_cookie = null;

    /**
     * Constructor.
     */
    public function __construct(?ServerRequestInterface $request = null)
    {
        $this->_psrRequest = $request;

        $this->_post = $this->_psrRequest ? $this->_psrRequest->getParsedBody() : $_POST;
        $this->_get = $this->_psrRequest ? $this->_psrRequest->getQueryParams() : $_GET;
        $this->_files = $this->_psrRequest ? $this->_parseFilesFromPsrRequest($this->_psrRequest) : $_FILES;

        $server = $this->_psrRequest ? $this->_psrRequest->getServerParams() : $_SERVER;
        if($this->_psrRequest) {
            $server['request_method'] = $this->_psrRequest->getMethod();
            $server['request_uri'] = $this->_psrRequest->getUri()->getPath();
            $server['document_uri'] = '/index.php';
            $server['server_protocol'] = 'HTTP/2.0';
            $server['document_root'] = App::$webRoot;
            $server['http_host'] = $this->_psrRequest->getUri()->getHost();
            $server['https'] = $this->_psrRequest->getUri()->getScheme() === 'https';
        }
        $this->_server = $server;
        $this->_cookie = $this->_psrRequest ? $this->_psrRequest->getCookieParams() : $_COOKIE;
        $this->DispatchEvent(EventsContainer::RequestReady);
        $this->_detectJsonEncodedData();
    }

    private function _parseFilesFromPsrRequest(ServerRequestInterface $request): array
    {
        $files = [];
        foreach ($request->getUploadedFiles() as $key => $uploadedFile) {
            if (is_array($uploadedFile)) {
                $files[$key] = [];
                foreach ($uploadedFile as $subKey => $subFile) {
                    $tmpName = $this->_saveUploadedFileToTemp($subFile);
                    $files[$key][$subKey] = [
                        'ext' => pathinfo($subFile->getClientFilename(), PATHINFO_EXTENSION),
                        'name' => $subFile->getClientFilename(),
                        'type' => $subFile->getClientMediaType(),
                        'tmp_name' => $tmpName,
                        'error' => $subFile->getError(),
                        'size' => $subFile->getSize()
                    ];
                }
            } else {
                $tmpName = $this->_saveUploadedFileToTemp($uploadedFile);
                $files[$key] = [
                    'ext' => pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION),
                    'name' => $uploadedFile->getClientFilename(),
                    'type' => $uploadedFile->getClientMediaType(),
                    'tmp_name' => $tmpName,
                    'error' => $uploadedFile->getError(),
                    'size' => $uploadedFile->getSize()
                ];
            }
        }
        return $files;
    }

    private function _saveUploadedFileToTemp($uploadedFile): string
    {
        $tmpName = tempnam(sys_get_temp_dir(), 'colibri_');
        if ($tmpName === false) {
            return '';
        }

        $stream = $uploadedFile->getStream();
        $stream->rewind();
        file_put_contents($tmpName, $stream->getContents());

        return $tmpName;
    }


    /**
     * Detects if the request payload is JSON encoded.
     *
     * @return void
     */
    private function _detectJsonEncodedData(): void
    {
        if (isset($this->_post['json_encoded_data'])) {
            $json = $this->_post['json_encoded_data'];
            $data = json_decode(base64_decode($json), true);
            unset($this->_post['json_encoded_data']);
            $this->_post = VariableHelper::Extend($this->_post, $data);
            $this->_encodedAsJson = true;
        }
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
                $return = new RequestCollection($this->_get);
                break;
            }
            case 'post': {
                $return = new RequestCollection($this->_post, !$this->_encodedAsJson);
                break;
            }
            case 'files': {
                $return = new RequestFileCollection($this->_files);
                break;
            }
            case 'session': {
                $return = self::$session;
                break;
            }
            case 'server': {
                $return = new RequestCollection($this->_server);
                break;
            }
            case 'cookie': {
                $return = new RequestCollection($this->_cookie);
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
                if($this->_psrRequest) {
                    $return = $this->_psrRequest->getUri()->getHost();
                } else {
                    $return = $this->server->{'http_host'} ? $this->server->{'http_host'} : '';
                }
                break;
            }
            case 'address': {
                $proto = $this->server->{'https'} ? 'https://' : 'http://';
                $return = $proto . $this->host;
                break;
            }
            case 'insecure': {
                $return = !$this->server->{'https'};
                break;
            }
            case 'headers': {
                if($this->_psrRequest) {
                    $headers = $this->_psrRequest->getHeaders();
                    foreach($headers as $k => $v) {
                        $headers[$k] = implode(', ', $v);
                    }
                } else {
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
                }
                $return = new RequestCollection($headers);
                break;
            }
            case 'type': {
                if($this->_psrRequest) {
                    $return = $this->_psrRequest->getMethod();
                } else {
                    $return = $this->server->{'request_method'} ? $this->server->{'request_method'} : 'get';
                }
                break;
            }
            default: {
                return null;
            }
        }
        return $return;
    }

    public function UpdateServerVariable(string $name, string $value) {
        $this->_server[$name] = $value;
        $_SERVER[$name] = $value;
    }

    public function UpdateGetParam(string $name, string $value) {
        $this->_get[$name] = $value;
        $_GET[$name] = $value;
    }

    public function UpdatePostParam(string $name, string $value) {
        $this->_post[$name] = $value;
        $_POST[$name] = $value;
    }

    public function UpdateFilesParam(string $name, array $value) {
        $this->_files[$name] = $value;
        $_FILES[$name] = $value;
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
            $this->_post,
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
            if(is_string($fileKey)) {
                $files[] = $this->ExtractFile($fileKey);
            } else {
                $files[] = $fileKey;
            }
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
