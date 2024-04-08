<?php
use Colibri\Utils\Debug;

if (!function_exists('dd')) {

    /**
     * Prints debug information and exits
     */
    function dd(...$args) 
    {
        Debug::Out($args);
        exit;
    }

}

if (!function_exists('ddx')) {

    /**
     * Prints debug information without exiting from script
     */
    function ddx(...$args) 
    {
        Debug::Out($args);
    }

}

if (!function_exists('ddd')) {

    /**
     * Prints collapsable debug information and exits
     */
    function ddd(...$args) 
    {
        Debug::IOut($args);
        exit;
    }

}

if (!function_exists('dddx')) {

    /**
     * Prints collapsable debug information without exiting
     */
    function dddx(...$args) 
    {
        Debug::IOut($args);
    }

}