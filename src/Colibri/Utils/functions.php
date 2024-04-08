<?php
use Colibri\Utils\Debug;

if (!function_exists('dd')) {

    function dd(...$args) 
    {
        Debug::Out($args);
        exit;
    }

}

if (!function_exists('ddx')) {

    function ddx(...$args) 
    {
        Debug::Out($args);
    }

}

if (!function_exists('ddd')) {

    function ddd(...$args) 
    {
        Debug::IOut($args);
        exit;
    }

}

if (!function_exists('dddx')) {

    function dddx(...$args) 
    {
        Debug::IOut($args);
    }

}