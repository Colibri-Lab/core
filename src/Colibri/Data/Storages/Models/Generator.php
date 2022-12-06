<?php

namespace Colibri\Data\Storages\Models;

use Colibri\App;
use Colibri\AppException;
use Colibri\Common\StringHelper;
use Colibri\Data\Storages\Storage;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use Colibri\Data\Storages\Fields\Field;

class Generator {

    private static function _convertNames(string $rootNamespace, string $table, string $row): array {
        
        $parts = explode('\\', $table);
        
        $tableClassName = array_pop($parts);
        $namespaceName = implode('\\', $parts);

        $row = str_replace($namespaceName.'\\', '', $row);

        return [$rootNamespace.$namespaceName, $tableClassName, $row];
    }

    public static function GetSchemaObject($fields): array
    {
        $jsonTypeMap = [
            'bool' => 'boolean',
            'int' => 'integer',
            'float' => 'number',
            'double' => 'number',
            'string' => 'string',
            'object' => 'object',
            'array' => 'array',
            'date' => 'string',
            'datetime' => 'string',
            'callable' => 'null',
            'iterable' => 'null',
            'resource' => 'null'
        ];

        $schemaRequired = [];
        $schemaProperties = [];

        if(empty((array)$fields)) {
            return [[], ['\'patternProperties\' => [\'.*\' => [\'type\' => \'string\']]']];
        }

        foreach($fields as $field) {

            $schemaType = '';
            $schemaItems = '';
            $schemaEnum = [];

            $class = $field->class;
            if (!in_array($field->class, array_keys($jsonTypeMap))) {
                $class = explode('\\', $class);
                $class = end($class);
                $schemaType = $class . '::JsonSchema';
            } else {
                $schemaType = $jsonTypeMap[$field->class];
            }

            if($field->multiple) {
                $schemaItems = $schemaType;
                $schemaType = '\'array\'';
            }

            if($field->values) {
                foreach($field->values as $value => $title) {
                    $schemaEnum[] = '\''.$value.'\'';
                }
            }

            if($schemaType === 'ObjectField::JsonSchema') {
                [$sr, $sb] = self::GetSchemaObject($field->fields);
                $schemaProperties[] = "\t\t\t".'\''.$field->name.'\' => [\'type\' => \'object\', \'required\' => ['.implode('', str_replace("\t\t\t", "", $sr)).'], \'properties\' => ['.implode('', str_replace("\t\t\t", "", $sb)).']],';
            } elseif ($schemaType === 'ArrayField::JsonSchema') {
                [$sr, $sb] = self::GetSchemaObject($field->fields);
                $schemaProperties[] = "\t\t\t" . '\''.$field->name.'\' => [\'type\' => \'array\', \'items\' => [\'type\' => \'object\', \'required\' => ['.implode('', str_replace("\t\t\t", "", $sr)).'], \'properties\' => ['.implode('', str_replace("\t\t\t", "", $sb)).']]],';
            } elseif ($schemaType === 'DateField::JsonSchema' || $schemaType === 'DateTimeField::JsonSchema') {
                $schemaProperties[] = "\t\t\t" . '\''.$field->name.'\' => [\'type\' => \'string\', \'format\' => \'date'.($schemaType === 'DateTimeField::JsonSchema' ? '-time' : '').'\'],';
            } elseif ($schemaType === 'ValueField::JsonSchema') {
                $schemaProperties[] = "\t\t\t" . '\''.$field->name.'\' => [\'type\' => \'string\', \'enum\' => ['.implode(', ', $schemaEnum).']],';
            } else {
                $schemaProperties[] = "\t\t\t".'\''.$field->name.'\' => '.(!isset($jsonTypeMap[$field->class]) ? $schemaType.',' : 
                    '[\'type\' => '.
                        ($field->params['required'] ? '\''.$schemaType.'\'' : '[\''.$schemaType.'\', \'null\']').', '.
                        (!empty($schemaEnum) ? '\'enum\' => ['.implode(', ', $schemaEnum).'],' : '').
                        ($field->class === 'string' && (bool)$field->length ? '\'maxLength\' => '.$field->length.'' : '').
                        ($schemaItems ? '\'items\' => '.$schemaItems : '').
                    '],'
                );
            }

            if($field->params['required'] ?? false) {
                $schemaRequired[] = "\t\t\t".'\''.$field->name.'\',';
            }
        }

        return [$schemaRequired, $schemaProperties];

    }


    public static function GenerateModelClasses(Storage $storage): void {

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
            'schema-required' => '',
            'schema-properties' => '',
        ];

        $properties = [
            ' * @property-read int $id ID строки',
            ' * @property-read DateTimeField $datecreated Дата создания строки',
            ' * @property-read DateTimeField $datemodified Дата последнего обновления строки',
        ];

        [$schemaRequired, $schemaProperties] = self::GetSchemaObject($storage->fields);

        $uses = ['use Colibri\Data\Storages\Fields\DateTimeField;'];
        $consts = [];
        foreach($storage->fields as $field) {
            /** @var Field $field */

            $class = $field->class;
            if(!in_array($field->class, $types)) {
                $uses[] = 'use '.$storage->GetFieldClass($field).';';
                $class = explode('\\', $class);
                $class = end($class);
            }

            $properties[] = ' * @property'.($field->readonly ? '-read' : '').' '.$class.(!$field->required ? '|null' : '').' $'.$field->name.' '.$field->desc;
            if($field->values) {
                foreach($field->values as $value => $title) {
                    $name = StringHelper::CreateHID($field->name.'-'.str_replace('_', '-', $value), true);
                    $name = StringHelper::ToCamelCaseAttr($name, true);
                    $consts[] = "\t".'/** '.$title.' */'."\n\t".'public const '.$name.' = \''.$value.'\';';
                }
            }

        }

        $uses = array_unique($uses);

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

            File::Create($rootPath.$fileName.'.php', true, '777');
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
            $args['uses'] = implode("\n", $uses);
            $args['consts'] = implode("\n", $consts);
            $args['schema-required'] = implode("\n", $schemaRequired);
            $args['schema-properties'] = implode("\n", $schemaProperties);

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
            $rowModelContent = \preg_replace_callback('/# region Uses\:(.*)# endregion Uses;/s', function($match) use ($uses) {
                return '# region Uses:'."\n".implode("\n", $uses)."\n".'# endregion Uses;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region Consts\:(.*)# endregion Consts;/s', function($match) use ($consts) {
                return '# region Consts:'."\n".implode("\n", $consts)."\n\t".'# endregion Consts;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region SchemaRequired\:(.*)# endregion SchemaRequired;/s', function($match) use ($schemaRequired) {
                return '# region SchemaRequired:'."\n".implode("\n", $schemaRequired)."\n\t\t\t".'# endregion SchemaRequired;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region SchemaProperties\:(.*)# endregion SchemaProperties;/s', function($match) use ($schemaProperties) {
                return '# region SchemaProperties:'."\n".implode("\n", $schemaProperties)."\n\t\t\t".'# endregion SchemaProperties;';
            }, $rowModelContent);
            File::Write($rootPath.$fileName.'.php', $rowModelContent);
        }

    }

    public static function GenerateModelTemplates(Storage $storage) {
        
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
