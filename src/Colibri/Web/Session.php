<?php

namespace Colibri\Web;

use Colibri\App;
use Colibri\Utils\Cache\Mem;

/**
 * Class Session
 * @author Vahan P. Grigoryan
 * @package Colibri\Web
 * @property-readonly string $sid
 * @property int $ttl
 */
class Session implements \ArrayAccess
{
    private int $_ttl;
    private ?string $_sid = null;

    private ?array $_data = null;

    public function __construct($ttl) {
        $this->_ttl = $ttl;
        $this->_load();
    }

    private function _load() {
        $requestedSessionCookie = App::$request->cookie->{'sid'};
        if(!$requestedSessionCookie) {
            $this->_sid = $this->_create();
        } else {
            $this->_sid = $requestedSessionCookie;
        }
        $data = Mem::Read('session:' . $this->_sid);
        if(!$data) {
            $this->_data = [];
        } else {
            $this->_data = json_decode($data, true);
        }
    }

    private function _save() {
        Mem::Write(
            (string)'session:' . $this->_sid,
            json_encode($this->_data),
            time() + $this->_ttl
        );
    }

    public function __get(string $name): mixed
    {
        if ($name === 'sid') {
            return $this->_sid;
        } else if($name === 'ttl') {
            return $this->_ttl;
        } 
        return $this->Get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        if($name == 'ttl') {
            $this->_ttl = $value;
            return;
        } else if($name == 'sid') {
            // do nothing
            return;
        }
        $this->Set($name, $value);
    }

    private function _create(): string
    {
        $sid = 'CLB_' . bin2hex(random_bytes(32));
        Mem::Write(
            'session:' . $sid,
            [],
            time() + $this->_ttl
        );
        return $sid;
    }

    public function Get(string $name): mixed
    {
        if(!$this->_sid) {
            $this->_load();
        }
        return $this->_data[$name] ?? null;
    }

    public function Set(string $name, mixed $value): void
    {
        $this->_data[$name] = $value;
        $this->_save();
    }

    public function Delete(string $name): void
    {
        unset($this->_data[$name]);
        $this->_save();
    }

    public function Exists(string $name): bool
    {
        return isset($this->_data[$name]);
    }

    public function offsetExists(mixed $offset): bool 
    {
        return $this->Exists($offset);
    }
    public function offsetGet(mixed $offset): mixed 
    {
        return $this->Get($offset);
    }
    public function offsetSet(mixed $offset, mixed $value): void 
    {
        $this->Set($offset, $value);
    }
    public function offsetUnset(mixed $offset): void 
    {
        $this->Delete($offset);
    }
    
}