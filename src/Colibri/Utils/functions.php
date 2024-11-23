<?php

use Colibri\App;
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
    /**
     * Runs a command in shell
     * @param string $command command to run
     * @param object|array $args arguments
     * @return bool|string|null
     */
    function runx(string $command, object|array $args = []) {
        $sargs = [];
        foreach($args as $key => $value) {
            $sargs[] = (is_string($key) ?  $key . '=' : '') . '"' . $value . '"';
        }
        return shell_exec($command . ' ' . implode(' ', $sargs).' > /dev/null & echo $!');
    }
}

if(!function_exists('killx')) {

    /**
     * Kills a command by PID
     * @param int $pid pid of command process
     * @return void
     */
    function killx(int $pid) 
    {
        shell_exec('kill -KILL ' . $pid);
    }
}

if(!function_exists('pidx')) {
    /**
     * Returns a array of pids of processes matched search string
     * @param string $searchKey
     * @return int|array
     */
    function pidx(string $searchKey): int|array
    {
        $pids = [];
        exec('ps -ax | grep "'.$searchKey.'"', $console);
        foreach($console as $line) {
            if(strstr($line, 'grep') !== false) {
                continue;
            }
            $k = explode(' ', $line);
            $pids[] = (int)$k;
        }
        return count($pids) > 1 ? $pids : (int)$pids[0];
    }

}

if(!function_exists('app_debug')) {
    function app_debug(...$args) {
        $message = Debug::ROut($args);
        App::$log->debug($message);
    }
}

if(!function_exists('app_info')) {
    function app_info(...$args) {
        $message = Debug::ROut($args);
        App::$log->info($message);
    }
}

if(!function_exists('app_emergency')) {
    function app_emergency(...$args) {
        $message = Debug::ROut($args);
        App::$log->info($message);
    }
}