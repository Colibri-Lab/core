<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

use Colibri\AppException;
use Colibri\Collections\ICollection;

/**
 * File system security properties.
 * 
 * @class
 *
 * @property bool $denied Indicates if access is denied.
 * @property bool $grant Indicates if access is granted.
 * @property bool $read Indicates if read access is granted.
 * @property bool $write Indicates if write access is granted.
 * @property bool $delete Indicates if delete access is granted.
 * @property bool $execute Indicates if execute access is granted.
 * @property string $owner The owner of the file.
 * 
 * @example
 * ```
 * $file = new File('/path/to/file.txt');
 * $security = $file->access; // Loads the security settings of the file
 * ```
 */
class Security
{
    /**
     * The source (File or Directory).
     *
     * @var File|Directory|null
     * @protected
     */
    protected File|Directory|null $source = null;

    /**
     * Access flags.
     *
     * @var array
     * @protected
     */
    protected array $flags;

    /**
     * Constructor.
     *
     * @param File|Directory|null $source The source.
     * @param mixed $flags The flags.
     * @constructor
     * @public
     * @example
     * ```
     * $file = new File('/path/to/file.txt');
     * $security = new Security($file, ['read' => true, 'write' => false]);
     * ```
     */
    public function __construct(File|Directory|null $source, mixed $flags = null)
    {
        $this->source = $source;
        if ($flags === null) {
            return;
        }

        if ($flags instanceof ICollection) {
            $this->flags = $flags->Item('rawArray');
        } elseif (\is_array($flags)) {
            $this->flags = $flags;
        } else {
            throw new AppException('illegal arguments: ' . __CLASS__);
        }
    }

    /**
     * Get flags by magic method
     *
     * @param string $property The flag
     * @return mixed
     * @public
     * @magic
     */
    public function __get(string $property): mixed
    {
        return $this->flags[$property];
    }

    /**
     * Set flags by magic method
     *
     * @param string $property The flag
     * @param mixed $value The value.
     * @public
     * @magic
     */
    public function __set(string $property, mixed $value): void
    {
        $this->flags[$property] = $value;
    }
}
