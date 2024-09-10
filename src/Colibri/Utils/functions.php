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

if (!function_exists('ddrx')) {

    /**
     * Prints collapsable debug information without exiting
     */
    function ddrx(...$args)
    {
        return Debug::ROut($args);
    }

}

if(!function_exists('runx')) {
    function runx(string $command, object|array $args = []) {
        $sargs = [];
        foreach($args as $key => $value) {
            $sargs[] = (is_string($key) ?  $key . '=' : '') . '"' . $value . '"';
        }
        return shell_exec($command . ' ' . implode(' ', $sargs).' > /dev/null & echo $!');
    }
}