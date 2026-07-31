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
use Colibri\Utils\Cache\Mem;

/**
 * Class Session
 * @class
 * @implements \ArrayAccess
 * 
 * @property-readonly string $sid
 * @property int $ttl
 */
class Session implements \ArrayAccess
{
    /**
     * @var int The time-to-live for the session.
     */
    private int $_ttl;
    /**
     * @var string|null The session ID.
     */
    private ?string $_sid = null;

    /**
     * @var array|null The session data.
     */
    private ?array $_data = null;

    /**
     * Session constructor.
     *
     * @param int $ttl The time-to-live for the session.
     */
    public function __construct(int $ttl) {
        $this->_ttl = $ttl;
        $this->_load();
    }

    /**
     * Loads the session data from the cache.
     */
    private function _load() {
        $requestedSessionCookie = App::$request->cookie->{'sid'};
        if(!$requestedSessionCookie) {
            $this->_sid = $this->_create();
        } else {
            $this->_sid = $requestedSessionCookie;
        }
        $data = Mem::Read($this->_sid);
        if(!$data) {
            $this->_data = [];
        } else {
            $this->_data = json_decode($data, true);
        }
    }

    /**
     * Saves the session data to the cache.
     */
    private function _save() {
        Mem::Write(
            (string)$this->_sid,
            json_encode($this->_data),
            $this->_ttl
        );
    }

    /**
     * Magic getter for session properties.
     *
     * @param string $name The property name.
     * @return mixed The property value.
     */
    public function __get(string $name): mixed
    {
        if ($name === 'sid') {
            return $this->_sid;
        } else if($name === 'ttl') {
            return $this->_ttl;
        } 
        return $this->Get($name);
    }

    /** 
     * Magic setter for session properties.
     *
     * @param string $name The property name.
     * @param mixed $value The property value.
     * @return void
     */
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

    /** 
     * Creates a new session ID and initializes the session data in the cache.
     *
     * @return string The newly created session ID.
     */
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

    /** 
     * Retrieves the value of a session variable.
     *
     * @param string $name The session variable name.
     * @return mixed The session variable value.
     */
    public function Get(string $name): mixed
    {
        if(!$this->_sid) {
            $this->_load();
        }
        return $this->_data[$name] ?? null;
    }

    /**
     * Sets the value of a session variable.
     *
     * @param string $name The session variable name.
     * @param mixed $value The session variable value.
     * @return void
     */
    public function Set(string $name, mixed $value): void
    {
        $this->_data[$name] = $value;
        $this->_save();
    }

    /**
     * Deletes a session variable.
     *
     * @param string $name The session variable name.
     * @return void
     */
    public function Delete(string $name): void
    {
        unset($this->_data[$name]);
        $this->_save();
    }

    /**
     * Checks if a session variable exists.
     *
     * @param string $name The session variable name.
     * @return bool True if the session variable exists, false otherwise.
     */
    public function Exists(string $name): bool
    {
        return isset($this->_data[$name]);
    }

    /**
     * Checks the offset is exists
     */
    public function offsetExists(mixed $offset): bool 
    {
        return $this->Exists($offset);
    }
    /**
     * Gets the value at the specified offset.
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed The value at the specified offset.
     */
    public function offsetGet(mixed $offset): mixed 
    {
        return $this->Get($offset);
    }
    /**
     * Sets the value at the specified offset.
     *
     * @param mixed $offset The offset to set.
     * @param mixed $value The value to set at the specified offset.
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void 
    {
        $this->Set($offset, $value);
    }
    /**
     * Unsets the value at the specified offset.
     *
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset(mixed $offset): void 
    {
        $this->Delete($offset);
    }
    
}