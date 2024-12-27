<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

/**
 * File system attributes.
 *
 * Represents attributes of the file system.
 *
 * @property int $created The creation date of the file.
 * @property int $modified The last modification date of the file.
 * @property int $lastaccess The last access date of the file.
 * @property int $readonly Indicates if the file is read-only.
 * @property int $hidden Indicates if the file is hidden.
 */
class Attributes
{
    /**
     * The source file or directory.
     *
     * @var File|Directory|null
     */
    protected File|Directory|null $source = null;

    /**
     * The list of attributes.
     *
     * @var array
     */
    protected array $attributes = array();

    /**
     * Constructor.
     *
     * @param File|Directory $source The source file or directory.
     */
    public function __construct(File|Directory $source)
    {
        $this->source = $source;
    }

    /**
     * Getter.
     *
     * @param string $property The property name.
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        $return = null;
        switch ($property) {
            case 'created': {
                if (!array_key_exists('created', $this->attributes)) {
                    $this->attributes['created'] = filectime($this->source->path);
                }

                $return = $this->attributes['created'];
                break;
            }
            case 'modified': {
                if (!array_key_exists('created', $this->attributes)) {
                    $this->attributes['created'] = filemtime($this->source->path);
                }

                $return = $this->attributes['created'];
                break;
            }
            case 'lastaccess': {
                if (!array_key_exists('created', $this->attributes)) {
                    $this->attributes['created'] = fileatime($this->source->path);
                }

                $return = $this->attributes['created'];
                break;
            }
            default:
                if (array_key_exists($property, $this->attributes)) {
                    $return = $this->attributes[$property];
                }
        }
        return $return;
    }

    /**
     * Setter.
     *
     * @param string $property The property name.
     * @param mixed $value The property value.
     */
    public function __set(string $property, mixed $value): void
    {
        if (array_key_exists($property, $this->attributes)) {
            $this->update($property, $value);
        }
    }

    /**
     * Updates the value of the attribute.
     *
     * @param string $property The property name.
     * @param mixed $value The new value.
     */
    private function update(string $property, mixed $value): void
    {
        // Do nothing
    }
}
