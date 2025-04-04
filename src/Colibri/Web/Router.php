<?php

/**
 * Web
 *
 * @package Colibri\Web
 * @author K. Tigay
 * @copyright 2020 ColibriLab
 */

namespace Colibri\Web;

use Colibri\App;
use Throwable;
use Colibri\Utils\Config\ConfigException;
use Colibri\Utils\Config\Config;
use Colibri\Common\VariableHelper;

/**
 * Class Router
 *
 * This abstract class represents a template for web content generation.
 *
 */
class Router
{
    /**
     * @var array Holds the configuration array for routes.
     */
    private array $_configArray = [];

    /**
     * Constructor. Initializes the Router instance.
     */
    public function __construct()
    {
        $this->_configArray = [];
        try {
            $this->_configArray = App::$config->Query('routes', (object) [])->AsArray();
        } catch (ConfigException $e) {
            $this->_configArray = [];
        }

        try {
            $modules = App::$config->Query('modules.entries');
        } catch (ConfigException $e) {
            $modules = [];
        }

        foreach ($modules as $moduleConfig) {
            if (!$moduleConfig->Query('enabled')->GetValue()) {
                continue;
            }
            /** @var Config $moduleConfig */
            try {

                $configArray = $moduleConfig->Query('config.routes', [])->AsArray();
                $this->_configArray = VariableHelper::Extend($this->_configArray, $configArray, true);
            } catch (ConfigException $e) {
                // do nothing
            }
        }

    }

    /**
     * Update the current request URI based on defined routes.
     */
    public function UpdateRequest()
    {
        $command = $_SERVER['REQUEST_URI'] ?? '';
        $_SERVER['REQUESTED_URI'] = $command;
        $command = str_contains($command, '?') ? substr($command, 0, strpos($command, '?')) : $command;

        foreach ($this->_configArray as $rule => $route) {
            $variables = [];
            $regexp = preg_quote($rule, '/');
            if (preg_match_all("/\\\{(.+?)(?:\\\:(.+?))?\\\}/", $regexp, $matchesAll)) {
                [$all, $variables, $values] = $matchesAll;

                foreach ($values as $i => $_var) {
                    $regexp = str_replace($all[$i], "(" . ($_var ? stripslashes($_var) : '.+?') . ")", $regexp);
                    $variables[$i] = '{' . $variables[$i] . '}';
                }
            }


            if (preg_match("/^$regexp$/", $command, $matches)) {
                //shift full match
                array_shift($matches);
                //replace from rule, array of tpl variables => matches
                $command = str_replace($variables, $matches, $route);

                //get query params string
                if ($query = parse_url($command, PHP_URL_QUERY)) {
                    //query string to array
                    parse_str($query, $params);
                    array_walk($params, function ($param, $key) {
                        //put params to $_GET and $_REQUEST
                        $_GET[$key] = $_REQUEST[$key] = $param;
                    });
                }

                foreach ($_SERVER as $key => $value) {
                    $command = str_replace('{' . strtolower($key) . '}', $value, $command);
                }

                break;
            }
        }

        $command = str_contains($command, '?') ? substr($command, 0, strpos($command, '?')) : $command;
        $_SERVER['REQUEST_URI'] = $command;
    }

    /**
     * Get the modified URI based on defined routes.
     *
     * @param string $command The original URI.
     * @return string The modified URI.
     */
    public function Uri(string $command): string
    {

        foreach ($this->_configArray as $route => $rule) {
            $variables = [];
            $regexp = preg_quote($rule, '/');
            if (preg_match_all("/\\\{(.+?)(?:\\\:(.+?))?\\\}/", $regexp, $matchesAll)) {
                [$all, $variables, $values] = $matchesAll;

                foreach ($values as $i => $_var) {
                    $regexp = str_replace($all[$i], "(" . ($_var ? stripslashes($_var) : '.+?') . ")", $regexp);
                    $variables[$i] = '{' . $variables[$i] . '}';
                }
            }


            if (preg_match("/^$regexp$/", $command, $matches)) {
                //shift full match
                array_shift($matches);
                //replace from rule, array of tpl variables => matches
                $command = str_replace($variables, $matches, $route);

                //get query params string
                if ($query = parse_url($command, PHP_URL_QUERY)) {
                    //query string to array
                    parse_str($query, $params);
                    array_walk($params, function ($param, $key) {
                        //put params to $_GET and $_REQUEST
                        $_GET[$key] = $_REQUEST[$key] = $param;
                    });
                }

                foreach ($_SERVER as $key => $value) {
                    if (!is_string($value)) {
                        continue;
                    }
                    $command = str_replace('{' . strtolower($key) . '}', $value, $command);
                }

                break;
            }
        }

        return $command;
    }


}
