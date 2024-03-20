<?php

namespace Colibri\Utils;


abstract class Singleton
{

    public static $instance;

    public static function Create(...$arguments): static
    {
        if (!static::$instance) {
            static::$instance = new static(...$arguments);
        }
        return static::$instance;
    }
    
}