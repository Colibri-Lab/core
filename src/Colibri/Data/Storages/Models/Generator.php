<?php

namespace Colibri\Data\Storages\Models;

use Colibri\App;
use Colibri\AppException;
use Colibri\Common\StringHelper;
use Colibri\Data\Storages\Storage;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;

class Generator {

    static function _convertNames(string $rootNamespace, string $table, string $row): array {
        
        $parts = explode('\\', $table);
        
        $tableClassName = array_pop($parts);
        $namespaceName = implode('\\', $parts);

        $row = str_replace($namespaceName.'\\', '', $row);

        return [$rootNamespace.$namespaceName, $tableClassName, $row];
    }

    static function GenerateModelClasses(Storage $storage): void {

        $types = [
            'bool',
            'int',
            'float',
            'double',
            'string',
            'array',
            'object',
            'callable',
            'iterable',
            'resource'
        ];

        $rootPath = App::$appRoot;
        $rootNamespace = '';
        $module = isset($storage->settings['module']) ? $storage->settings['module'] : null;
        if($module) {
            $module = StringHelper::ToLower($module);
            if(!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration '.$module);
            }
            $rootPath = App::$moduleManager->$module->modulePath;
            $rootNamespace = App::$moduleManager->$module->moduleNamespace;
        }

        $models = $storage->models;
        $table = $models['table'];
        $row = $models['row'];

        [$rootNamespace, $table, $row] = self::_convertNames($rootNamespace, $table, $row);

        $args = [
            'storage-name' => $storage->name,
            'storage-desc' => $storage->desc,
            'namespace-path' => '',
            'properties-list' => '',
            'table-class-name' => '',
            'parent-table-class-name' => '',
            'row-class-name' => '',
            'parent-row-class-name' => '',
            'uses' => '',
        ];

        $properties = [
            ' * @property-read int $id ID строки',
            ' * @property-read DateTimeField $datecreated Дата создания строки',
            ' * @property-read DateTimeField $datemodified Дата последнего обновления строки',
        ];
        
        $uses = [];
        foreach($storage->fields as $field) {
            $class = $field->class;
            if(!in_array($field->class, $types)) {
                if(class_exists($field->class)) {
                    $uses[] = 'use '.$field->class.';';
                }
                else {
                    $uses[] = 'use Colibri\\Data\\Storages\\Fields\\'.$field->class.';';
                }
                $class = explode('\\', $class);
                $class = end($class);
            }
            $properties[] = ' * @property'.($field->readonly ? '-read' : '').' '.$class.(!$field->required ? '|null' : '').' $'.$field->name.' '.$field->desc;
        }

        $fileName = str_replace('\\', '/', $models['table']);
        if(!File::Exists($rootPath.$fileName.'.php')) {
            
            $args['namespace-path'] = $rootNamespace;
            $args['table-class-name'] = $table;
            $args['parent-table-class-name'] = 'Colibri\\Data\\Storages\\Models\\DataTable';
            $args['row-class-name'] = $row;

            $templateContent = File::Read(__DIR__.'/model-templates/table-template.template');
            foreach($args as $key => $value) {
                $templateContent = str_replace('[['.$key.']]', $value, $templateContent);
            }

            File::Create($rootPath.$fileName.'.php', true, 0777);
            File::Write($rootPath.$fileName.'.php', $templateContent);

        }

        $fileName = str_replace('\\', '/', $models['row']);
        if(!File::Exists($rootPath.$fileName.'.php')) {

            $args['namespace-path'] = $rootNamespace;
            $args['table-class-name'] = $table;
            $args['parent-table-class-name'] = 'Colibri\\Data\\Storages\\Models\\DataTable';
            $args['row-class-name'] = $row;
            $args['parent-row-class-name'] = 'Colibri\\Data\\Storages\\Models\\DataRow';
            $args['properties-list'] = implode("\n", $properties);
            $args['uses'] = implode("\n", array_unique($uses));

            $templateContent = File::Read(__DIR__.'/model-templates/row-template.template');
            foreach($args as $key => $value) {
                $templateContent = str_replace('[['.$key.']]', $value, $templateContent);
            }

            File::Create($rootPath.$fileName.'.php', true, '777');
            File::Write($rootPath.$fileName.'.php', $templateContent);

        }
        else {
            
            $rowModelContent = File::Read($rootPath.$fileName.'.php');
            $rowModelContent = \preg_replace_callback('/\s\* region Properties\:(.*)\s\* endregion Properties;/s', function($match) use ($properties) {
                return ' * region Properties:'."\n".implode("\n", $properties)."\n".' * endregion Properties;';
            }, $rowModelContent);
            File::Write($rootPath.$fileName.'.php', $rowModelContent);
        }

    }

    static function GenerateModelTemplates(Storage $storage) {
        
        $rootPath = App::$appRoot;
        $module = isset($storage->settings['module']) ? $storage->settings['module'] : null;
        if($module) {
            $module = StringHelper::ToLower($module);
            if(!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration '.$module);
            }
            $rootPath = App::$moduleManager->$module->modulePath;
        }

        $view = $storage->view;
        if(!$view) {
            return;
        }
        
        $templateProcessorClass = isset($view['class']) ? $view['class'] : '';
        $templates = isset($view['templates']) ? $view['templates'] : '';

        if(!$templateProcessorClass) {
            return;
        }

        $parts = explode('\\', $templateProcessorClass);
        $templateClassName = array_pop($parts);

        $args = [
            'template-class' => $templateProcessorClass,
            'template-key' => '',
            'storage-name' => $storage->name,
            'template-class-name' => $templateClassName,
            'default-content' => '',
        ];

        $templateContent = File::Read(__DIR__.'/model-templates/template-template.template');
        foreach($templates as $key => $template) {
            if(!File::Exists($rootPath.$template.'.php')) {
                $tc = $templateContent;

                $args['template-key'] = $key;

                foreach($args as $key => $value) {
                    $tc = str_replace('[['.$key.']]', $value, $tc);
                }

                File::Write($rootPath.$template.'.php', $tc, true, '777');
            }

        }

    }

}