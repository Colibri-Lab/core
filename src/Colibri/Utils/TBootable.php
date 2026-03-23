<?php


/**
 * Data
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Cache
 */

namespace Colibri\Utils;

/**
 * Basic Event Dispatcher trait.
 */
trait TBootable
{
    
    protected function boot()
    {
        foreach (\class_uses_recursive(static::class) as $trait) {
            $method = 'boot' . \class_basename($trait);
            if (method_exists(static::class, $method)) {
                $this->$method();
            }
        }
    }

}
