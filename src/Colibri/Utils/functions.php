<?php

use Colibri\App;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;

if (!function_exists('dd')) {

    /**
     * Prints debug information and exits
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
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
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
     */
    function ddx(...$args)
    {
        Debug::Out($args);
    }

}

if (!function_exists('ddd')) {

    /**
     * Prints collapsable debug information and exits
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
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
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
     */
    function dddx(...$args)
    {
        Debug::IOut($args);
    }

}

if (!function_exists('ddrx')) {

    /**
     * Prints collapsable debug information without exiting
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
     */
    function ddrx(...$args)
    {
        return Debug::ROut($args);
    }

}

if(!function_exists('runx')) {
    /**
     * Runs a command in shell
     * @global
     * @param string $command command to run
     * @param object|array $args arguments
     * @return bool|string|null
     */
    function runx(string $command, object|array $args = [])
    {
        $sargs = [];
        foreach($args as $key => $value) {
            $sargs[] = (is_string($key) ? $key . '=' : '') . '"' . $value . '"';
        }
        return shell_exec($command . ' ' . implode(' ', $sargs).' > /dev/null & echo $!');
    }
}

if(!function_exists('killx')) {

    /**
     * Kills a command by PID
     * @param int $pid pid of command process
     * @global
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
     * @global
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
            $pids[] = (int)$k[0];
        }
        return count($pids) > 1 ? $pids : (int)$pids[0];
    }

}

if(!function_exists('app_debug')) {
    /**
     * Logs debug information to the application log.
     *
     * @global
     * @param mixed ...$args The arguments to log.
     * @return void
     */
    function app_debug(...$args)
    {
        $message = Debug::ROut($args);
        App::$log->debug($message);
    }
}

if(!function_exists('app_info')) {
    /**
     * Logs informational messages to the application log.
     *
     * @global
     * @param mixed ...$args The arguments to log.
     * @return void
     */
    function app_info(...$args)
    {
        $message = Debug::ROut($args);
        App::$log->info($message);
    }
}

if(!function_exists('app_emergency')) {
    /**
     * Logs emergency messages to the application log.
     *
     * @global
     * @param mixed ...$args The arguments to log.
     * @return void
     */
    function app_emergency(...$args)
    {
        $message = Debug::ROut($args);
        App::$log->info($message);
    }
}

if(!function_exists('file_ext')) {
    /**
     * Returns the file extension from a given path or filename.
     *
     * @global
     * @param string $pathOrName The file path or name.
     * @return string The file extension.
     */
    function file_ext($pathOrName)
    {
        $f = new File($pathOrName);
        return $f->extension;
    }
}

if(!function_exists('parse_ml_annotation')) {
    /**
     * Parses multi-line annotations from a docstring and appends a value to each annotation.
     *
     * @global
     * @param string $doc The docstring containing annotations.
     * @param string $value The value to append to each annotation.
     * @return array An associative array of annotations with the appended value.
     */
    function parse_ml_annotation($doc, $value){
        preg_match_all('/@([a-z]+?):\s+(.*?)\n/i', $doc, $annotations);
        if(!isset($annotations[1]) OR count($annotations[1]) == 0){
            return [];
        }
        return array_combine(array_map("trim",$annotations[1]), array_map(fn($v) => $v . ' ' . $value, array_map("trim",$annotations[2])));
    }
}

if(!function_exists('class_uses_recursive')) {
    /**
     * Recursively retrieves all traits used by a class, including traits used by parent classes and traits used within traits.
     *
     * @global
     * @param string|object $class The class name or object instance.
     * @return array An array of trait names used by the class.
     */
    function class_uses_recursive($class) {
        $traits = [];
    
        // если передан объект, берем его класс
        if (is_object($class)) {
            $class = get_class($class);
        }
    
        // собираем трейты текущего класса
        $traits = class_uses($class) ?: [];
    
        // рекурсивно собираем трейты родителей
        $parent = get_parent_class($class);
        if ($parent) {
            $traits = array_merge($traits, class_uses_recursive($parent));
        }
    
        // рекурсивно собираем трейты внутри трейтов
        foreach ($traits as $trait) {
            $traits = array_merge($traits, class_uses_recursive($trait));
        }
    
        return array_unique($traits);
    }

}

if(!function_exists('class_basename')) {
    /**
     * Returns the "basename" of a class, which is the class name without the namespace.
     *
     * @global
     * @param string|object $class The class name or object instance.
     * @return string The basename of the class.
     */
    function class_basename($class) {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $class = trim($class, '\\'); // на случай, если namespace начинается с '\'
        $parts = explode('\\', $class);
        return array_pop($parts);
    }

}