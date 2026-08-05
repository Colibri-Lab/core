<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */

namespace Colibri\Data\Storages\Fields;

use Colibri\Collections\ArrayList;

/**
 * Class file list field
 * @class
 * @extends ArrayList
 */
class FileListField extends ArrayList
{
    /**
     * Constructor
     * @param string $data data from the field
     * @return void
     * @constructor
     * @public
     */
    public function __construct($data)
    {
        parent::__construct([]);
        if(is_string($data)) {
            $data = str_replace("\n", "", str_replace("\r", "", $data));
            if(!empty($data)) {
                $data = explode(';', $data);
            }
        }
        if (!empty($data)) {
            foreach ($data as $file) {
                $this->Add(new FileField($file));
            }
        }
    }

    /**
     * Returns the string for writing to the field
     * @param string $splitter delimiter
     * @return string concatenated string of file paths
     * @public
     */
    public function ToString(string $splitter = ';'): string
    {
        $sources = [];
        foreach ($this as $file) {
            $sources[] = $file->ToString();
        }
        return implode(';', $sources);
    }

    /**
     * Returns string value of this object
     *
     * @return string
     * @public  
     * @magic
     */
    public function __toString()
    {
        return $this->ToString();
    }

    /**
     * Returns the parameter type name for this file list field.
     *
     * @return string The parameter type name.
     * @public
     * @static
     */
    public function GetValidationData(): mixed
    {
        return array_map(fn ($item) => $item->ToArray(), $this->ToArray());
    }

    /**
     * Returns parameter type name for this file list field.
     * @return string The parameter type name.
     * @public
     * @static
     */
    public static function ParamTypeName(): string
    {
        return 'string';
    }

    /**
     * Returns null value for the field.
     *
     * @return mixed Always returns null.
     * @public
     * @static
     */
    public static function null(): mixed
    {
        return null;
    }
}
