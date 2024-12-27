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

use Colibri\Collections\ReadonlyCollection;

/**
 * Request Collection Class
 *
 * Represents a collection of data from a request.
 * Read-only.
 *
 */
class RequestCollection extends ReadonlyCollection
{
    /** @var bool Whether to strip slashes from the data. */
    private bool $_stripSlashes = true;

    /**
     * Constructor.
     *
     * @param mixed $data The data to initialize the collection with.
     * @param bool $stripSlashes Whether to strip slashes from the data.
     */
    public function __construct(mixed $data = array(), bool $stripSlashes = true)
    {
        parent::__construct($data);
        $this->_stripSlashes = $stripSlashes;
        $this->_stripSlashes($data);
    }

    /**
     * Cleans or adds slashes to values.
     *
     * @param mixed $obj The data to process.
     * @param bool $strip Whether to strip slashes.
     * @return mixed The processed data.
     *
     */
    protected function _stripSlashes(mixed $obj, bool $strip = false): mixed
    {
        if (!$this->_stripSlashes) {
            return $obj;
        }

        if (is_array($obj)) {
            foreach ($obj as $k => $v) {
                $obj[$k] = $this->_stripSlashes($v, $strip);
            }
            return $obj;
        } elseif (is_object($obj)) {
            return $obj;
        } elseif (is_string($obj)) {
            return $strip ? stripslashes($obj) : addslashes($obj);
        } else {
            return $obj;
        }
    }

    /**
     * Magic getter method.
     *
     * @param string $property The property name.
     * @return mixed The value of the property.
     *
     */
    public function __get(string $property): mixed
    {
        $val = parent::__get($property);
        return $this->_stripSlashes($val, true);
    }
}
