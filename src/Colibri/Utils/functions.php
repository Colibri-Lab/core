<?php

use Colibri\App;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;

if (!function_exists('dd')) {

    /**
     * Prints debug information and exits
     * @public
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
     * @example
     * ```
     * dd($var1, $var2);
     * /// This will print debug information for $var1 and $var2 and exit the script.
     * ```
     */
    function dd(...$args): void
    {
        Debug::Out($args);
        exit;
    }

}

if (!function_exists('ddx')) {

    /**
     * Prints debug information without exiting from script
     * @public
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
     * @example
     * ```
     * ddx($var1, $var2);
     * /// This will print debug information for $var1 and $var2 without exiting the script.
     * ```
     */
    function ddx(...$args): void
    {
        Debug::Out($args);
    }

}

if (!function_exists('ddd')) {

    /**
     * Prints collapsable debug information and exits
     * @public
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
     * @example
     * ```
     * ddd($var1, $var2);
     * /// This will print collapsable debug information for $var1 and $var2 and exit the script.
     * ```
     */
    function ddd(...$args): void
    {
        Debug::IOut($args);
        exit;
    }

}

if (!function_exists('dddx')) {

    /**
     * Prints collapsable debug information without exiting
     * @public
     * @global
     * @param mixed ...$args The arguments to debug.
     * @return void
     * @example
     * ```
     * dddx($var1, $var2);
     * /// This will print collapsable debug information for $var1 and $var2 without exiting the script.
     * ```
     */
    function dddx(...$args): void
    {
        Debug::IOut($args);
    }

}

if (!function_exists('ddrx')) {

    /**
     * Prints collapsable debug information without exiting
     * @global
     * @public
     * @param mixed ...$args The arguments to debug.
     * @return ?string
     * @example
     * ```
     * $debugOutput = ddrx($var1, $var2);
     * /// $debugOutput will contain the debug information as a string.
     * ```
     */
    function ddrx(...$args): string
    {
        return Debug::ROut($args);
    }

}

if(!function_exists('runx')) {
    /**
     * Runs a command in shell
     * @global
     * @public
     * @param string $command command to run
     * @param object|array $args arguments
     * @return bool|string|null
     * @example
     * ```
     * $pid = runx('my_command', ['arg1' => 'value1', 'arg2' => 'value2']);
     * /// $pid will contain the PID of the command process.
     * ```
     */
    function runx(string $command, object|array $args = []): bool|string|null
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
     * @public
     * @global
     * @return void
     * @example
     * ```
     * $pid = runx('my_command');
     * killx($pid);
     * ```
     */
    function killx(int $pid):  void
    {
        shell_exec('kill -KILL ' . $pid);
    }
}

if(!function_exists('pidx')) {
    /**
     * Returns a array of pids of processes matched search string
     * @global
     * @public
     * @param string $searchKey
     * @return int|array
     * @example
     * ```
     * $pids = pidx('my_process');
     * /// $pids will contain an array of PIDs of processes that match the search string 'my_process'.
     * ```
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
     * @public
     * @param mixed ...$args The arguments to log.
     * @return void
     * @example
     * ```
     * app_debug('This is a debug message', ['key' => 'value']);
     * /// This will log the message and the array to the application log.
     * ```
     */
    function app_debug(...$args): void
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
     * @public
     * @param mixed ...$args The arguments to log.
     * @return void
     * @example
     * ```
     * app_info('This is an informational message', ['key' => 'value']);
     * /// This will log the message and the array to the application log.
     * ```
     */
    function app_info(...$args): void
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
     * @public
     * @param mixed ...$args The arguments to log.
     * @return void
     * @example
     * ```
     * app_emergency('This is an emergency message', ['key' => 'value']);
     * /// This will log the message and the array to the application log.
     * ```
     */
    function app_emergency(...$args): void
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
     * @public
     * @param string $pathOrName The file path or name.
     * @return string The file extension.
     * @example
     * ```
     * $extension = file_ext('/path/to/file.txt');
     * /// $extension will be 'txt'
     * ```
     */
    function file_ext($pathOrName): string
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
     * @example
     * ```
     * $doc = "@author: John Doe\n@version: 1.0\n";
     * $value = " - additional info";
     * $annotations = parse_ml_annotation($doc, $value);
     * /// $annotations will be:
     * /// [
     * ///     'author' => 'John Doe - additional info',
     * ///     'version' => '1.0 - additional info'
     * /// ]
     * ```
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
     * @public
     * @param string|object $class The class name or object instance.
     * @return array An array of trait names used by the class.
     * @example
     * ```
     * class MyClass {
     *     use MyTrait;
     * }
     * $traits = class_uses_recursive(MyClass::class);
     * /// $traits will contain ['MyTrait']
     * ```
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
     * @public
     * @param string|object $class The class name or object instance.
     * @return string The basename of the class.
     * @example
     * ```
     * class MyNamespace\MyClass {}
     * $basename = class_basename(MyNamespace\MyClass::class);
     * /// $basename will be 'MyClass'
     * ```
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