<?php

namespace Colibri\Data\Storages\Models;

use Colibri\App;
use Colibri\AppException;
use Colibri\Common\StringHelper;
use Colibri\Data\Storages\Storage;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use Colibri\Data\Storages\Fields\Field;

class Generator
{

    static $types = [
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

    private static function _convertNames(string $rootNamespace, string $table, string $row): array
    {

        $parts = explode('\\', $table);

        $tableClassName = array_pop($parts);
        $namespaceName = implode('\\', $parts);

        $row = str_replace($namespaceName . '\\', '', $row);

        return [$rootNamespace . $namespaceName, $tableClassName, $row];
    }

    public static function GetSchemaObject($fields, string $rowClass, string $classPrefix): array
    {
        $jsonTypeMap = [
            'bool' => ['boolean', 'number'],
            'int' => ['integer'],
            'float' => ['number'],
            'double' => ['number'],
            'string' => ['string'],
            'object' => ['object'],
            'array' => ['array'],
            'date' => ['string'],
            'datetime' => ['string'],
            'callable' => ['null'],
            'iterable' => ['null'],
            'resource' => ['null']
        ];

        $schemaRequired = [];
        $schemaProperties = [];

        if (empty((array) $fields)) {
            return [[], ["\t\t\t" . '\'patternProperties\' => [\'.*\' => [\'type\' => [\'number\',\'string\',\'boolean\',\'object\',\'array\',\'null\']]]']];
        }

        foreach ($fields as $field) {

            $schemaType = '';
            $schemaItems = '';
            $schemaEnum = [];

            $class = $field->{'class'};
            if (!in_array($field->{'class'}, array_keys($jsonTypeMap))) {
                $class = explode('\\', $class);
                $class = end($class);
                $schemaType = $class . '::JsonSchema';
            } else {
                $schemaType = $jsonTypeMap[$field->{'class'}];
            }

            if ($field->multiple) {
                $schemaItems = $schemaType;
                $schemaType = '\'array\'';
            }

            if ($field->values) {
                foreach ($field->values as $value => $title) {
                    $schemaEnum[] = is_string($value) ? '\'' . $value . '\'' : $value;
                }
            }

            if (is_array($schemaType) && in_array('boolean', $schemaType) && empty($schemaEnum)) {
                // надо запихать значения
                $schemaEnum = ['true', 'false', '0', '1'];
            }

            if ($schemaType === $rowClass . '::JsonSchema') {
                if ($field->params['required'] ?? false) {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => ObjectField::JsonSchema, '; // [ \'$ref\' => \'#\' ]
                } else {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [ \'oneOf\' => [ [ \'type\' => \'null\' ], ObjectField::JsonSchema ] ], '; // [\'$ref\' => \'#\']
                }
            } elseif ($schemaType === 'ObjectField::JsonSchema') {
                if(!empty((array)$field->fields)) {
                    $schemaType = StringHelper::ToCamelCaseVar(($classPrefix ? $classPrefix . '_' : '') . $field->name . '_object_field', true) . '::JsonSchema';
                }
                if ($field->params['required'] ?? false) {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => '.$schemaType.',';
                } else {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [  \'oneOf\' => [ '.$schemaType.', [ \'type\' => \'null\'] ] ],';
                }
            } elseif ($schemaType === 'ArrayField::JsonSchema') {
                if(!empty((array)$field->fields)) {
                    $schemaType = StringHelper::ToCamelCaseVar(($classPrefix ? $classPrefix . '_' : '') . $field->name . '_array_field', true) . '::JsonSchema';
                    if ($field->params['required'] ?? false) {
                        $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => '.$schemaType.',';
                    } else {
                        $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [  \'oneOf\' => [ '.$schemaType.', [ \'type\' => \'null\'] ] ],';
                    }
                } else {
                    [$sr, $sb] = self::GetSchemaObject($field->fields, $rowClass, $classPrefix . '_' . $field->name);
                    if ($field->params['required'] ?? false) {
                        $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [\'type\' => \'array\', \'items\' => [\'type\' => \'object\', \'required\' => [' . implode('', str_replace("\t\t\t", "", $sr)) . '], \'properties\' => [' . implode('', str_replace("\t\t\t", "", $sb)) . ']]],';
                    } else {
                        $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [  \'oneOf\' => [ [ \'type\' => \'null\' ], [\'type\' => \'array\', \'items\' => [\'type\' => \'object\', \'required\' => [' . implode('', str_replace("\t\t\t", "", $sr)) . '], \'properties\' => [' . implode('', str_replace("\t\t\t", "", $sb)) . ']]]]],';
                    }
                }
            } elseif ($schemaType === 'DateField::JsonSchema' || $schemaType === 'DateTimeField::JsonSchema') {
                if ($field->params['required'] ?? false) {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [\'type\' => \'string\', \'format\' => \'' . ($schemaType === 'DateTimeField::JsonSchema' ? 'db-date-time' : 'date') . '\'],';
                } else {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [ \'anyOf\' => [ [\'type\' => [\'string\', \'null\'], \'format\' => \'' . ($schemaType === 'DateTimeField::JsonSchema' ? 'db-date-time' : 'date') . '\'], [\'type\' => [\'string\', \'null\'], \'maxLength\' => 0] ] ],';
                }
            } elseif ($schemaType === 'ValueField::JsonSchema') {
                $schemaType = in_array($field->type, ['int', 'float', 'double']) ? 'number' : 'string';
                if ($field->params['required'] ?? false) {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [\'type\' => ' . '\'' . $schemaType . '\', \'enum\' => [' . implode(', ', $schemaEnum) . ']],';
                } else {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => [  \'oneOf\' => [ [ \'type\' => \'null\' ], [\'type\' => \'' . $schemaType . '\', \'enum\' => [' . implode(', ', $schemaEnum) . ']] ] ],';
                }
            } else {
                if ($field->params['required'] ?? false) {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => ' . (!isset($jsonTypeMap[$field->{'class'}]) ? $schemaType . ',' :
                        '[\'type\' => ' .
                        (count($schemaType) === 1 ? '\'' . $schemaType[0] . '\'' : '[\'' . implode('\',\'', $schemaType) . '\']') . ', ' .
                        (!empty($schemaEnum) ? '\'enum\' => [' . implode(', ', $schemaEnum) . '],' : '') .
                        ($field->{'class'} === 'string' && (bool) $field->length ? '\'maxLength\' => ' . $field->length . ', ' : '') .
                        ($field->{'class'} === 'string' ? ((bool) ($field->params['canbeempty'] ?? true) ? '' : '\'minLength\' => 1, ') : '') .
                        ($schemaItems ? '\'items\' => ' . $schemaItems : '') .
                        '],'
                    );
                } else {
                    $schemaProperties[] = "\t\t\t" . '\'' . $field->{'name'} . '\' => ' . (!isset($jsonTypeMap[$field->{'class'}]) ? '[ \'oneOf\' => [ [ \'type\' => \'null\'], ' . $schemaType . ' ] ],' :
                        '[ \'oneOf\' => [ [ \'type\' => \'null\'], [\'type\' => ' .
                        (count($schemaType) === 1 ? '\'' . $schemaType[0] . '\'' : '[\'' . implode('\',\'', $schemaType) . '\']') . ', ' .
                        (!empty($schemaEnum) ? '\'enum\' => [' . implode(', ', $schemaEnum) . '],' : '') .
                        ($field->{'class'} === 'string' && (bool) $field->length ? '\'maxLength\' => ' . $field->length . ', ' : '') .
                        ($field->{'class'} === 'string' ? ((bool) ($field->params['canbeempty'] ?? true) ? '' : '\'minLength\' => 1, ') : '') .
                        ($schemaItems ? '\'items\' => ' . $schemaItems : '') .
                        '] ] ],'
                    );

                }
            }

            if ($field->params['required'] ?? false) {
                $schemaRequired[] = "\t\t\t" . '\'' . $field->{'name'} . '\',';
            }
        }

        return [$schemaRequired, $schemaProperties];

    }

    private static function GenerateField(Storage $storage, Field $field, string $rootNamespace, string $row, array &$uses, array &$properties, array &$consts, string $classPrefix)
    {
        $langModule = App::$moduleManager->Get('lang');

        $class = $field->{'class'};
        if (!in_array($field->{'class'}, self::$types)) {
            $fullClassName = $storage->GetFieldClass($field);
            if ($fullClassName !== $rootNamespace . '\\' . $row) {
                $uses[] = 'use ' . $fullClassName . ';';
            }
            $class = explode('\\', $class);
            $class = end($class);

            if($class === 'ObjectField' && !empty((array)$field->fields)) {
                [$class, $fullSubClassName] = self::GenerateObjectFieldClass($storage, $field, $classPrefix);
                $uses[] = 'use ' . $fullSubClassName . ';';
            } else if($class === 'ArrayField' && !empty((array)$field->fields)) {
                [$class, $fullSubClassName] = self::GenerateArrayFieldClass($storage, $field, $classPrefix);
                $uses[] = 'use ' . $fullSubClassName . ';';
            }

        }

        if($class === 'ValueField') {
            $adClasses = [];
            $values = $field->rawvalues;
            foreach($values as $v) {
                if($v['type'] === 'text') {
                    $adClasses[] = 'string';
                } else {
                    $adClasses[] = 'int';
                    $adClasses[] = 'float';
                }
            }
            $adClasses = array_unique($adClasses);
            if(!empty($adClasses)) {
                $class = $class . '|' . implode('|', $adClasses);
            }
        }

        if($field->{'type'} === 'enum') {
            $class = $class . '|ValueField';
            $uses[] = 'use Colibri\Data\Storages\Fields\ValueField;';
        }
        
        $desc = $field->{'desc'};
        if ($langModule) {
            $desc = $desc[$langModule->Default()] ?? $desc;
        }

        $properties[] = ' * @property' . ($field->readonly ? '-read' : '') . ' ' . $class . (!$field->required ? '|null' : '') . ' $' . $field->{'name'} . ' ' . $desc;
        if ($field->values) {
            foreach ($field->values as $value => $title) {
                if ($langModule) {
                    $title = $title[$langModule->Default()] ?? $title;
                }
                $name = StringHelper::CreateHID($field->{'name'} . '-' . str_replace('_', '-', $value), true);
                $name = StringHelper::ToCamelCaseAttr($name, true);
                if (in_array($field->{'type'}, ['int', 'float', 'double', 'bool'])) {
                    $consts[] = "\t" . '/** ' . $title . ' */' . "\n\t" . 'public const ' . $name . ' = ' . $value . ';';
                } else {
                    $consts[] = "\t" . '/** ' . $title . ' */' . "\n\t" . 'public const ' . $name . ' = \'' . $value . '\';';
                }
            }
        }
    }

    public static function GenerateArrayFieldClass(Storage $storage, Field $field, string $classPrefix): array
    {

        $langModule = App::$moduleManager->Get('lang');
        
        $rootPath = App::$appRoot;
        $rootNamespace = '';
        $module = isset($storage->settings['module']) ? $storage->settings['module'] : null;
        if ($module) {
            $module = StringHelper::ToLower($module);
            if (!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration ' . $module);
            }
            $rootPath = App::$moduleManager->$module->modulePath;
            $rootNamespace = App::$moduleManager->$module->moduleNamespace;
        }

        $models = $storage->{'models'};
        $row = $models['row'];
        $fieldName = $field->{'name'};
        $className = StringHelper::ToCamelCaseVar(($classPrefix ? $classPrefix . '_' : '') . $fieldName . '_array_field', true);
        $row = 'Models\\Fields\\' . StringHelper::ToCamelCaseVar($storage->name, true) . '\\' . $className;

        $fieldDesc = $field->{'desc'};
        if ($langModule) {
            $fieldDesc = $fieldDesc[$langModule->Default()] ?? $fieldDesc;
        }

        $args = [
            'property-desc' => $fieldDesc,
            'namespace-path' => $rootNamespace . 'Models\\Fields\\' . StringHelper::ToCamelCaseVar($storage->name, true),
            'child-class-name' => '',
            'uses' => '',
            'schema-required' => '',
            'schema-properties' => '',
        ];

        $properties = $uses = $consts = [];

        [$childClass, $fullSubClassName] = self::GenerateObjectFieldClass($storage, $field, $classPrefix);
        $uses[] = 'use ' . $fullSubClassName . ';';

        $fileName = str_replace('\\', '/', $row);
        if (!File::Exists($rootPath . $fileName . '.php')) {
            $templateContent = File::Read(__DIR__ . '/model-templates/arrayfield-template.template');

            $args['child-class-name'] = $childClass;
            $args['class-name'] = $className;
            $args['row-class-name'] = $row;
            $args['properties-list'] = implode("\n", $properties);
            $args['uses'] = implode("\n", $uses);

            foreach ($args as $key => $value) {
                $templateContent = str_replace('[[' . $key . ']]', $value, $templateContent);
            }

            File::Create($rootPath . $fileName . '.php', true, '777');
            File::Write($rootPath . $fileName . '.php', $templateContent);
        } else {
            // do nothing
        }

        return [$className, $rootNamespace . $row];


    }

    public static function GenerateObjectFieldClass(Storage $storage, Field $field, string $classPrefix): array
    {
        $langModule = App::$moduleManager->Get('lang');
        
        $rootPath = App::$appRoot;
        $rootNamespace = '';
        $module = isset($storage->settings['module']) ? $storage->settings['module'] : null;
        if ($module) {
            $module = StringHelper::ToLower($module);
            if (!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration ' . $module);
            }
            $rootPath = App::$moduleManager->$module->modulePath;
            $rootNamespace = App::$moduleManager->$module->moduleNamespace;
        }

        $models = $storage->{'models'};
        $row = $models['row'];
        $fieldName = $field->{'name'};
        $className = StringHelper::ToCamelCaseVar(($classPrefix ? $classPrefix . '_' : '') . $fieldName . '_object_field', true);
        $row = 'Models\\Fields\\' . StringHelper::ToCamelCaseVar($storage->name, true) . '\\' . $className;

        $fieldDesc = $field->{'desc'};
        if ($langModule) {
            $fieldDesc = $fieldDesc[$langModule->Default()] ?? $fieldDesc;
        }

        $args = [
            'property-desc' => $fieldDesc,
            'namespace-path' => $rootNamespace . 'Models\\Fields\\' . StringHelper::ToCamelCaseVar($storage->name, true),
            'row-class-name' => '',
            'properties-list' => '',
            'uses' => '',
            'consts' => '',
            'schema-required' => '',
            'schema-properties' => '',
        ];

        $properties = $uses = $consts = [];

        [$schemaRequired, $schemaProperties] = self::GetSchemaObject($field->fields, $className, ($classPrefix ? $classPrefix . '_' : '') . $fieldName);

        foreach($field->fields as $f) {
            self::GenerateField($storage, $f, $rootNamespace, $row, $uses, $properties, $consts, ($classPrefix ? $classPrefix . '_' : '') . $fieldName);
        }

        $uses = array_unique($uses);
        sort($uses);

        $uses = array_diff($uses, ['use Colibri\Data\Storages\Fields\ObjectField;']);

        $fileName = str_replace('\\', '/', $row);
        if (!File::Exists($rootPath . $fileName . '.php')) {
            $templateContent = File::Read(__DIR__ . '/model-templates/objectfield-template.template');

            $args['class-name'] = $className;
            $args['row-class-name'] = $row;
            $args['properties-list'] = implode("\n", $properties);
            $args['uses'] = implode("\n", $uses);
            $args['consts'] = implode("\n", $consts);
            $args['schema-required'] = implode("\n", $schemaRequired);
            $args['schema-properties'] = implode("\n", $schemaProperties);

            foreach ($args as $key => $value) {
                $templateContent = str_replace('[[' . $key . ']]', $value, $templateContent);
            }

            File::Create($rootPath . $fileName . '.php', true, '777');
            File::Write($rootPath . $fileName . '.php', $templateContent);
        } else {

            $rowModelContent = File::Read($rootPath . $fileName . '.php');
            $rowModelContent = \preg_replace_callback('/\s\* region Properties\:(.*)\s\* endregion Properties;/s', function ($match) use ($properties) {
                return ' * region Properties:' . "\n" . implode("\n", $properties) . "\n" . ' * endregion Properties;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region Uses\:(.*)# endregion Uses;/s', function ($match) use ($uses) {
                return '# region Uses:' . "\n" . implode("\n", $uses) . "\n" . '# endregion Uses;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region Consts\:(.*)# endregion Consts;/s', function ($match) use ($consts) {
                return '# region Consts:' . "\n" . implode("\n", $consts) . "\n\t" . '# endregion Consts;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region SchemaRequired\:(.*)# endregion SchemaRequired;/s', function ($match) use ($schemaRequired) {
                return '# region SchemaRequired:' . "\n" . implode("\n", $schemaRequired) . "\n\t\t\t" . '# endregion SchemaRequired;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region SchemaProperties\:(.*)# endregion SchemaProperties;/s', function ($match) use ($schemaProperties) {
                return '# region SchemaProperties:' . "\n" . implode("\n", $schemaProperties) . "\n\t\t\t" . '# endregion SchemaProperties;';
            }, $rowModelContent);
            File::Write($rootPath . $fileName . '.php', $rowModelContent);

        }

        return [$className, $rootNamespace . $row];
    }

    public static function GenerateModelClasses(Storage $storage): void
    {

        $langModule = App::$moduleManager->Get('lang');

        $rootPath = App::$appRoot;
        $rootNamespace = '';
        $module = isset($storage->settings['module']) ? $storage->settings['module'] : null;
        if ($module) {
            $module = StringHelper::ToLower($module);
            if (!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration ' . $module);
            }
            $rootPath = App::$moduleManager->$module->modulePath;
            $rootNamespace = App::$moduleManager->$module->moduleNamespace;
        }

        $models = $storage->{'models'};
        $table = $models['table'];
        $row = $models['row'];

        [$rootNamespace, $table, $row] = self::_convertNames($rootNamespace, $table, $row);

        $storageDesc = $storage->{'desc'};
        if ($langModule) {
            $storageDesc = $storageDesc[$langModule->Default()] ?? $storageDesc;
        }

        $args = [
            'module-name' => $module,
            'storage-name' => $storage->name,
            'storage-desc' => $storageDesc,
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
            ' * @property int $id ID строки',
            ' * @property DateTimeField $datecreated Дата создания строки',
            ' * @property DateTimeField $datemodified Дата последнего обновления строки',
        ];

        [$schemaRequired, $schemaProperties] = self::GetSchemaObject($storage->fields, $row, '');

        $uses = ['use Colibri\Data\Storages\Fields\DateTimeField;'];
        $consts = [];
        foreach ($storage->fields as $field) {
            /** @var Field $field */
            self::GenerateField($storage, $field, $rootNamespace, $row, $uses, $properties, $consts, '');
        }

        $uses = array_unique($uses);
        sort($uses);

        $fileName = str_replace('\\', '/', $models['table']);
        if (!File::Exists($rootPath . $fileName . '.php')) {

            $args['namespace-path'] = $rootNamespace;
            $args['table-class-name'] = $table;
            $args['parent-table-class-name'] = 'Colibri\\Data\\Storages\\Models\\DataTable';
            $args['row-class-name'] = $row;

            $templateContent = File::Read(__DIR__ . '/model-templates/table-template.template');
            foreach ($args as $key => $value) {
                $templateContent = str_replace('[[' . $key . ']]', $value, $templateContent);
            }

            File::Create($rootPath . $fileName . '.php', true, '777');
            File::Write($rootPath . $fileName . '.php', $templateContent);

        }

        $fileName = str_replace('\\', '/', $models['row']);
        if (!File::Exists($rootPath . $fileName . '.php')) {

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

            $templateContent = File::Read(__DIR__ . '/model-templates/row-template.template');
            foreach ($args as $key => $value) {
                $templateContent = str_replace('[[' . $key . ']]', $value, $templateContent);
            }

            File::Create($rootPath . $fileName . '.php', true, '777');
            File::Write($rootPath . $fileName . '.php', $templateContent);

        } else {

            $rowModelContent = File::Read($rootPath . $fileName . '.php');
            $rowModelContent = \preg_replace_callback('/\s\* region Properties\:(.*)\s\* endregion Properties;/s', function ($match) use ($properties) {
                return ' * region Properties:' . "\n" . implode("\n", $properties) . "\n" . ' * endregion Properties;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region Uses\:(.*)# endregion Uses;/s', function ($match) use ($uses) {
                return '# region Uses:' . "\n" . implode("\n", $uses) . "\n" . '# endregion Uses;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region Consts\:(.*)# endregion Consts;/s', function ($match) use ($consts) {
                return '# region Consts:' . "\n" . implode("\n", $consts) . "\n\t" . '# endregion Consts;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region SchemaRequired\:(.*)# endregion SchemaRequired;/s', function ($match) use ($schemaRequired) {
                return '# region SchemaRequired:' . "\n" . implode("\n", $schemaRequired) . "\n\t\t\t" . '# endregion SchemaRequired;';
            }, $rowModelContent);
            $rowModelContent = \preg_replace_callback('/# region SchemaProperties\:(.*)# endregion SchemaProperties;/s', function ($match) use ($schemaProperties) {
                return '# region SchemaProperties:' . "\n" . implode("\n", $schemaProperties) . "\n\t\t\t" . '# endregion SchemaProperties;';
            }, $rowModelContent);
            File::Write($rootPath . $fileName . '.php', $rowModelContent);

        }

    }

    public static function GenerateModelTemplates(Storage $storage)
    {

        $rootPath = App::$appRoot;
        $module = isset($storage->settings['module']) ? $storage->settings['module'] : null;
        if ($module) {
            $module = StringHelper::ToLower($module);
            if (!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration ' . $module);
            }
            $rootPath = App::$moduleManager->$module->modulePath;
        }

        $view = $storage->{'view'};
        if (!$view) {
            return;
        }

        $templateProcessorClass = isset($view['class']) ? $view['class'] : '';
        $templates = isset($view['templates']) ? $view['templates'] : '';

        if (!$templateProcessorClass) {
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

        $templateContent = File::Read(__DIR__ . '/model-templates/template-template.template');
        foreach ($templates as $key => $template) {
            if (!File::Exists($rootPath . $template . '.php')) {
                $tc = $templateContent;

                $args['template-key'] = $key;

                foreach ($args as $key => $value) {
                    $tc = str_replace('[[' . $key . ']]', $value, $tc);
                }

                File::Write($rootPath . $template . '.php', $tc, true, '777');
            }

        }

    }

}