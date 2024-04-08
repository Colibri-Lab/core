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

/**
 * Request File Collection Class
 *
 * Represents a collection of files sent in a request.
 * Read-only.
 *
 *
 */
class RequestFileCollection extends RequestCollection
{

    /**
     * Constructor.
     *
     * @param mixed $data The data to initialize the collection.
     * @param bool $stripSlashes Whether to strip slashes from values (default: true).
     */
    public function __construct(mixed $data = array(), bool $stripSlashes = true)
    {
        // $data [key => value]
        // value может быть массивом упакованным в поля
        $add = [];
        foreach ($data as $key => $value) {
            if (is_array($value['name'])) {
                $add[$key] = [];
                for ($i = 0; $i < count($value['name']); $i++) {
                    $add[$key][] = (object) [
                        'name' => $value['name'][$i],
                        'type' => $value['type'][$i],
                        'tmp_name' => $value['tmp_name'][$i],
                        'error' => $value['error'][$i],
                        'size' => $value['size'][$i]
                    ];
                }
            } else {
                $add[$key] = (object) $value;
            }
        }
        parent::__construct($add, $stripSlashes);
    }

    /**
     * Override the method to prevent stripping slashes.
     *
     * @param mixed $obj The value to process.
     * @param bool $strip Whether to strip slashes.
     * @return mixed The processed value.
     */
    protected function _stripSlashes(mixed $obj, bool $strip = false): mixed
    {
        return $obj;
    }

    /**
     * Retrieves the file at the specified key.
     *
     * @param mixed $key The key of the file.
     * @return mixed|null The requested file or null if not found.
     */
    public function Item(mixed $key): mixed
    {
        if ($this->Exists($key)) {
            if (is_array($this->data[$key])) {
                $ret = [];
                foreach ($this->data[$key] as $file) {
                    $ret[] = new RequestedFile($file);
                }
                return $ret;
            }

            return new RequestedFile($this->data[$key]);
        }
        return null;
    }

    /**
     * Retrieves the file at the specified index.
     *
     * @param int $index The index of the file.
     * @return mixed|null The requested file or null if not found.
     */
    public function ItemAt(int $index): mixed
    {
        $key = $this->Key($index);
        if (!$key) {
            return null;
        }

        if (is_array($this->data[$key])) {
            $ret = [];
            foreach ($this->data[$key] as $file) {
                $ret[] = new RequestedFile($file);
            }
            return $ret;
        }

        return new RequestedFile($this->data[$key]);
    }
}