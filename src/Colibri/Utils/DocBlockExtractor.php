<?php

namespace Colibri\Utils;

use Colibri\App;
use Colibri\Common\Encoding;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\IO\FileSystem\File;

/**
 * Extracts a namespace -> class -> {information, properties, methods}
 * structure purely from docblocks. Assumes exactly one class per file.
 *
 * Classification of each docblock is based on its tags (and, where the
 * tags don't carry a name, a minimal peek at the very next line of code):
 *  - JS getter/setter code right after the docblock  -> property (accessor)
 *  - @magic tag                                       -> magic method
 *  - @var tag                                          -> property (field)
 *  - @param / @return tag                              -> method
 *  - none of the above                                 -> the class docblock
 *    (exactly one such docblock is expected per file)
 * @class
 */
class DocBlockExtractor
{
    /**
     * @private
     * @var string $_path File path or raw source code
     */
    private string $_path = '';

    /**
     * @public
     * @param string $content File path or raw source code
     * @return array The namespace/class/properties/methods structure, or
     *                ['error' => string, ...] if the file doesn't contain
     *                exactly one class docblock
     */
    public function extractFromFile(string $content): ?array
    {
        if (File::Exists($content)) {
            $this->_path = $content;
            $content = File::Read($content);
        }

        return $this->extractFromCode($content, $this->_path);
    }

    /**
     * @public
     * @param string $code PHP or JS source code
     * @return array
     */
    public function extractFromCode(string $code, string $path): ?array
    {
        $this->_path = $path;
        $useMap = $this->buildUseMap($code);
        $fileNamespace = $this->detectNamespace($code);

        $blocks = $this->scanDocBlocks($code);

        $classCandidates = [];
        $propertyBlocks = [];
        $methodBlocks = [];
        $constsBlocks = [];
        $enumCandidates = [];
        $interfaceCandidates = [];
        $traitCandidates = [];
        $iconsCandidates = [];
        $constructor = null;
        $destructor = null;
        $isnamespace = false;

        foreach ($blocks as $block) {
            $parsed = $this->parseDocBlock($block['raw']);
            $tagNames = array_map(fn ($t) => strtolower($t['tag']), $parsed['tags']);
            if(\in_array('ignore', $tagNames, true)) {
                return null;
            }
            
            $tags = VariableHelper::ConvertToAssociative(array_map(fn($t) => ['tag' => $t['tag'], 'raw' => trim(str_replace(':', '', $t['raw']), " \t\n\r\0\x0B ")], $parsed['tags']), 'tag', 'raw');

            if(\in_array('namespace', $tagNames, true)) {
                return null;
            }
            if(\in_array('icons', $tagNames, true)) {
                // icon list
                return null;
            }

            $accessor = $this->peekJsAccessor($block['after']);

            if ($accessor !== null) {
                $propertyBlocks[] = [
                    'parsed'   => $parsed,
                    'name'     => $accessor['name'],
                    'accessor' => $accessor['kind'],
                    'line'     => $block['line'],
                    'tags'     => $tags
                ];
                continue;
            }

            if (in_array('magic', $tagNames, true)) {
                $name = $this->peekFunctionName($block['after']);
                $methodBlocks[] = [
                    'parsed' => $parsed,
                    'name'   => $name,
                    'magic'  => true,
                    'line'   => $block['line'],
                    'tags'     => $tags
                ];
                continue;
            }

            if (in_array('constructor', $tagNames, true)) {
                $name = $this->peekFunctionName($block['after']);
                $constructor = [
                    'parsed' => $parsed,
                    'name'   => $name,
                    'magic'  => true,
                    'constructor'  => true,
                    'line'   => $block['line'],
                    'tags'     => $tags
                ];
                continue;
            }

            if (in_array('destructor', $tagNames, true)) {
                $name = $this->peekFunctionName($block['after']);
                $destructor = [
                    'parsed' => $parsed,
                    'name'   => $name,
                    'magic'  => true,
                    'destructor'  => true,
                    'line'   => $block['line'],
                    'tags'     => $tags
                ];
                continue;
            }

            if (in_array('var', $tagNames, true) || in_array('type', $tagNames, true)) {
                $name = $this->peekFieldName($block['after']);
                $propertyBlocks[] = [
                    'parsed'   => $parsed,
                    'name'     => $name,
                    'accessor' => null,
                    'line'     => $block['line'],
                    'tags'     => $tags
                ];
                continue;
            }

            if (in_array('const', $tagNames, true)) {
                $name = $this->peekFieldName($block['after']);
                $constsBlocks[] = [
                    'parsed'   => $parsed,
                    'name'     => $name,
                    'accessor' => null,
                    'line'     => $block['line'],
                    'tags'     => $tags
                ];
                continue;
            }

            if (in_array('param', $tagNames, true) || in_array('return', $tagNames, true) || in_array('returns', $tagNames, true)) {
                $name = $this->peekFunctionName($block['after']);
                $methodBlocks[] = [
                    'parsed' => $parsed,
                    'name'   => $name,
                    'magic'  => false,
                    'line'   => $block['line'],
                    'tags'     => $tags
                ];
                continue;
            }

            if (in_array('class', $tagNames, true)) {
                $classCandidates[] = [
                    'parsed' => $parsed,
                    'line'   => $block['line'],
                    'after'  => $block['after'],
                    'tags'     => $tags
                ];
            }

            if (in_array('enum', $tagNames, true)) {
                $enumCandidates[] = [
                    'parsed' => $parsed,
                    'line'   => $block['line'],
                    'after'  => $block['after'],
                    'tags'     => $tags
                ];
            }

            if (in_array('trait', $tagNames, true)) {
                $traitCandidates[] = [
                    'parsed' => $parsed,
                    'line'   => $block['line'],
                    'after'  => $block['after'],
                    'tags'     => $tags
                ];
            }

            if (in_array('interface', $tagNames, true)) {
                $interfaceCandidates[] = [
                    'parsed' => $parsed,
                    'line'   => $block['line'],
                    'after'  => $block['after'],
                    'tags'     => $tags
                ];
            }
        }

        if(empty($classCandidates) && empty($enumCandidates) && empty($interfaceCandidates) && empty($traitCandidates)) {
            // this may be a file with only functions, but no class/enum/interface/trait
            
            $prototyped = [];
            $global = [];
            foreach($blocks as $block) {
                $parsed = $this->parseDocBlock($block['raw']);
                $tagNames = array_map(fn ($t) => strtolower($t['tag']), $parsed['tags']);
                if(\in_array('ignore', $tagNames, true)) {
                    return null;
                }
                
                $functionName = $this->peekFunctionName($block['after']);
                if(\in_array('global', $tagNames, true)) {
                    $global[$functionName] = $this->buildMethodInfo($parsed, false, false, false);
                    $global[$functionName]['path'] = '/'.str_replace(App::$appRoot, '', $this->_path);
                    $global[$functionName]['type'] = 'method';
                }
                if(\in_array('prototypeof', $tagNames, true)) {
                    $method = $this->buildMethodInfo($parsed, false, false, false);
                    $method['path'] = '/'.str_replace(App::$appRoot, '', $this->_path);
                    $method['type'] = 'method';
                    $prototyped[$method['prototypeof']][$functionName] = $method;
                }

            }
            
            if(empty($global) && empty($prototyped)) {
                return null; 
                // [
                //     'error' => 'Не найден докблок класса/enum/interface/trait (тег @class/@enum/@interface/@trait).',
                // ];
            }

            return [
                'global' => $global,
                'prototyped' => $prototyped,
            ];

        }


        $classBlock = $classCandidates[0] ?? $enumCandidates[0] ?? $interfaceCandidates[0] ?? $traitCandidates[0];
        $tags = $classBlock['tags'] ?? [];
        $className = $this->peekClassName($classBlock['after']);

        if ($className === null) {
            return [
                'error' => sprintf(
                    'Не удалось определить имя класса рядом с докблоком класса (строка %d).',
                    $classBlock['line']
                ),
            ];
        }

        [$information, $virtualProperties, $virtualMethods] = $this->buildClassInformation(
            $classBlock['parsed'],
            $classBlock['after'],
            $useMap
        );

        $namespace = $information['memberof'] ?? $fileNamespace ?? '';
        $className = trim(str_replace($namespace, '', $className), '.\\');

        $properties = $virtualProperties;
        foreach ($this->buildProperties($propertyBlocks) as $name => $info) {
            if (isset($properties[$name])) {
                $properties[$name] = array_merge($properties[$name], $info);
            } else {
                $properties[$name] = $info;
            }
        }

        $consts = [];
        foreach ($this->buildConsts($constsBlocks) as $name => $info) {
            if (isset($consts[$name])) {
                $consts[$name] = array_merge($consts[$name], $info);
            } else {
                $consts[$name] = $info;
            }
        }

        $methods = $virtualMethods;
        foreach ($methodBlocks as $mb) {
            if ($mb['name'] === null) {
                continue;
            }
            $methods[$mb['name']] = $this->buildMethodInfo($mb['parsed'], $mb['magic']);
            $methods[$mb['name']]['tags'] = $mb['tags'];
        }

        if($constructor) {
            $constructor = $this->buildMethodInfo($constructor['parsed'], false, true, false);
            $constructor['tags'] = $constructor['tags'] ?? [];
        }
        if($destructor) {
            $destructor = $this->buildMethodInfo($destructor['parsed'], false, false, true);
            $destructor['tags'] = $destructor['tags'] ?? [];
        }

        $namespace = StringHelper::Explode($namespace, ['\\', '.']);
        $run = [];
        $cmd = '$return';
        $return = [];
        foreach ($namespace as $ns) {
            $run[] = $cmd . '[\''.$ns.'\'] = [];';
            $cmd = $cmd . '[\''.$ns.'\']';
        }
        foreach($run as $c) {
            eval($c);
        }


        $cmd = $cmd . '[\''.$className.'\'] = [
            \'type\'        => \''.(match(true) {
                !empty($enumCandidates) => 'enum',
                !empty($interfaceCandidates) => 'interface',
                !empty($traitCandidates) => 'trait',
                default => 'class'
            }).'\',
            \'information\' => $information,
            \'constructor\' => $constructor,
            \'destructor\'  => $destructor,
            \'properties\'  => $properties,
            \'methods\'     => $methods,
            \'consts\'      => $consts,
            \'tags\'        => $tags,
        ];'; 
        eval($cmd);


        return $return;
    }
    /**
     * Finds every /** ... *\/ block in the code, along with its line number
     * and a small window of the code that follows it (used only to recover
     * a name/signature that the docblock tags themselves don't carry).
     *
     * @private
     * @param string $code
     * @return array<int, array{raw: string, line: int, after: string}>
     */
    private function scanDocBlocks(string $code): array
    {
        $blocks = [];

        if (!preg_match_all('/^[ \t]*\/\*\*.*?\*\//sm', $code, $matches, PREG_OFFSET_CAPTURE)) {
            return $blocks;
        }

        foreach ($matches[0] as [$raw, $offset]) {
            $line = substr_count($code, "\n", 0, $offset) + 1;
            $afterStart = $offset + strlen($raw);
            $blocks[] = [
                'raw'   => $raw,
                'line'  => $line,
                'after' => substr($code, $afterStart, 500),
            ];
        }

        return $blocks;
    }

    /**
     * Splits a docblock into a summary/description (all non-@ lines,
     * joined into one string) and a list of {tag, raw} pairs, where "raw"
     * is the full remainder of the tag (continuation lines included).
     *
     * @private
     * @param string $docComment
     * @return array{description: string, tags: array<int, array{tag: string, raw: string}>}
     */
    private function parseDocBlock(string $docComment): array
    {
        $lines = preg_split('/\R/u', $docComment);
        $cleanLines = [];

        foreach ($lines as $line) {
            $line = preg_replace('/^\s*\/?\*+\/?\s?/u', '', $line);
            $cleanLines[] = rtrim($line);
        }

        $descriptionParts = [];
        $tags = [];

        foreach ($cleanLines as $line) {
            if (preg_match('/^@([\w-]+)\s*(.*)$/u', $line, $m)) {
                $tags[] = ['tag' => $m[1], 'raw' => trim($m[2])];
                continue;
            }

            if ($line === '') {
                continue;
            }

            if (strstr($line, 'region') !== false || strstr($line, 'endregion') !== false) {
                continue;
            }

            if (empty($tags)) {
                $descriptionParts[] = $line;
            } else {
                // Продолжение многострочного тега (например, содержимое @example ```...```)
                $lastIndex = count($tags) - 1;
                $tags[$lastIndex]['raw'] = trim($tags[$lastIndex]['raw'] . "\n" . $line);
            }
        }

        return [
            'description' => trim(implode(' ', $descriptionParts)),
            'tags'        => $tags,
        ];
    }

    /**
     * @private
     * @param array $parsed Result of parseDocBlock() for the class docblock
     * @param string $afterCode Code right after the class docblock (fallback for name/extends/implements)
     * @param array<string, string> $useMap
     * @return array{0: array, 1: array, 2: array} [information, virtualProperties, virtualMethods]
     */
    private function buildClassInformation(array $parsed, string $afterCode, array $useMap): array
    {
        $information = [
            'path'        => '/'.str_replace(App::$appRoot, '', $this->_path),
            'description' => $parsed['description'],
            'extends'     => null,
            'implements'  => [],
            'abstract'    => false,
            'deprecated'  => false,
            'final'       => false,
            'memberof'    => null,
            'example'     => null,
            'used'        => [],
        ];

        $virtualProperties = [];
        $virtualMethods = [];

        foreach ($parsed['tags'] as $tag) {
            $name = strtolower($tag['tag']);
            $raw = $tag['raw'];

            switch ($name) {
                case 'extends':
                    if ($raw !== '') {
                        $information['extends'] = $this->resolveClassName($raw, $useMap);
                    }
                    break;

                case 'implements':
                    foreach (preg_split('/\s*,\s*/', $raw) as $part) {
                        if ($part !== '') {
                            $information['implements'][] = $this->resolveClassName($part, $useMap);
                        }
                    }
                    break;
                
                case 'used':
                    foreach (preg_split('/\s*,\s*/', $raw) as $part) {
                        if ($part !== '') {
                            $information['used'][] = $this->resolveClassName($part, $useMap);
                        }
                    }
                    break;

                case 'abstract':
                    $information['abstract'] = true;
                    break;
                
                case 'deprecated':
                    $information['deprecated'] = true;
                    break;

                case 'final':
                    $information['final'] = true;
                    break;

                case 'memberof':
                    if ($raw !== '') {
                        $information['memberof'] = $raw;
                    }
                    break;

                case 'example':
                    $information['example'] = $this->stripCodeFence($raw);
                    break;

                case 'property':
                case 'property-read':
                case 'property-write':
                    [$type, $propName, $desc] = $this->parseTypeNameDesc($raw);
                    if ($propName !== null) {
                        $default = null;
                        $access = $name === 'property-read' ? 'read'
                            : ($name === 'property-write' ? 'write' : 'read-write');
                        if(strstr($propName, '=') !== false) {
                            [$propName, $default] = explode('=', $propName, 2);
                            $propName = trim($propName);
                            $default = trim($default);
                        }
                        $virtualProperties[$propName] = [
                            'type'        => $type,
                            'description' => $desc,
                            'access'      => $access,
                            'default'     => $default,
                            'virtual'     => true,
                        ];
                    }
                    break;

                case 'method':
                    $m = $this->parseMethodTag($raw, $useMap);
                    if ($m !== null) {
                        $virtualMethods[$m['name']] = [
                            'static'      => $m['static'],
                            'returns'     => ['type' => $m['returnType'], 'description' => null],
                            'params'      => $m['params'],
                            'description' => $m['description'],
                            'virtual'     => true,
                        ];
                    }
                    break;
            }
        }

        // Если extends/implements/abstract/final не были явно продублированы
        // тегами — подстрахуемся тем, что реально написано в объявлении класса
        if (preg_match(
            '/^(?<mods>(?:abstract\s+|final\s+)*)class\s+[A-Za-z_$][\w$]*'
            . '(?:\s+extends\s+([\w$\\\\]+))?'
            . '(?:\s+implements\s+([\w$\\\\,\s]+?))?'
            . '\s*[{\r\n]/',
            ltrim($afterCode),
            $cm
        )) {
            if ($information['extends'] === null && !empty($cm[2])) {
                $information['extends'] = $this->resolveClassName(trim($cm[2]), $useMap);
            }
            if (empty($information['implements']) && !empty($cm[3])) {
                foreach (preg_split('/\s*,\s*/', trim($cm[3])) as $part) {
                    $information['implements'][] = $this->resolveClassName($part, $useMap);
                }
            }
            if (!$information['abstract'] && stripos($cm['mods'], 'abstract') !== false) {
                $information['abstract'] = true;
            }
            if (!$information['final'] && stripos($cm['mods'], 'final') !== false) {
                $information['final'] = true;
            }
        }

        return [$information, $virtualProperties, $virtualMethods];
    }

    /**
     * @private
     * @param array $propertyBlocks
     * @return array<string, array>
     */
    private function buildProperties(array $propertyBlocks): array
    {
        $properties = [];

        foreach ($propertyBlocks as $pb) {
            if ($pb['name'] === null) {
                continue;
            }

            $info = $this->buildPropertyInfo($pb['parsed']);

            if ($pb['accessor'] !== null) {
                $info['access'] = $pb['accessor'] === 'get' ? 'read' : 'write';
            } else {
                $info['access'] = 'read-write';
            }

            $name = $pb['name'];

            if (isset($properties[$name])) {
                $properties[$name] = $this->mergeAccessorProperties($properties[$name], $info);
            } else {
                $properties[$name] = $info;
            }

            $properties[$name]['tags'] = $pb['tags'];
        }

        return $properties;
    }

    /**
     * @private
     * @param array $constsBlocks
     * @return array<string, array>
     */
    private function buildConsts(array $constsBlocks): array
    {
        $consts = [];

        foreach ($constsBlocks as $pb) {

            $info = $this->buildPropertyInfo($pb['parsed']);

            if ($pb['accessor'] !== null) {
                $info['access'] = $pb['accessor'] === 'get' ? 'read' : 'write';
            } else {
                $info['access'] = 'read-write';
            }

            $name = $pb['name'];

            if (isset($consts[$name])) {
                $consts[$name] = $this->mergeAccessorProperties($consts[$name], $info);
            } else {
                $consts[$name] = $info;
            }

            $consts[$name]['tags'] = $pb['tags'];
            $consts[$name]['path'] = '/'.str_replace(App::$appRoot, '', $this->_path);
        }

        return $consts;
    }

    /**
     * @private
     * @param array $parsed Result of parseDocBlock() for a property docblock
     * @return array
     */
    private function buildPropertyInfo(array $parsed): array
    {
        $info = [
            'visibility'  => null,
            'static'      => false,
            'deprecated'  => false,
            'final'       => false,
            'type'        => null,
            'description' => $parsed['description'],
            'path'        => '/'.str_replace(App::$appRoot, '', $this->_path)
        ];

        foreach ($parsed['tags'] as $tag) {
            $name = strtolower($tag['tag']);

            switch ($name) {
                case 'public':
                case 'private':
                case 'protected':
                    $info['visibility'] = $name;
                    break;

                case 'static':
                    $info['static'] = true;
                    break;

                case 'deprecated':
                    $info['deprecated'] = true;
                    break;

                case 'final':
                    $info['final'] = true;
                    break;

                case 'const':
                case 'var':
                case 'type':
                    [$type, $desc] = $this->parseTypeDesc($tag['raw']);
                    $info['type'] = $type;
                    if ($desc !== '') {
                        $info['description'] = trim($info['description'] . ' ' . $desc);
                    }
                    break;

            }
        }

        return $info;
    }

    /**
     * Merges a getter's and a setter's property info (matched by name) into
     * one entry: readonly if only "get" was seen, writeonly if only "set",
     * read-write if both showed up.
     *
     * @private
     * @param array $existing
     * @param array $incoming
     * @return array
     */
    private function mergeAccessorProperties(array $existing, array $incoming): array
    {
        $accessSet = array_unique(array_filter([$existing['access'] ?? null, $incoming['access'] ?? null]));

        if (count($accessSet) > 1 || in_array('read-write', $accessSet, true)) {
            $access = 'read-write';
        } else {
            $access = $accessSet[array_key_first($accessSet)] ?? 'read-write';
        }

        return [
            'visibility'  => $existing['visibility'] ?? $incoming['visibility'],
            'static'      => $existing['static'] || $incoming['static'],
            'final'       => $existing['final'] || $incoming['final'],
            'type'        => $existing['type'] ?? $incoming['type'],
            'description' => trim(($existing['description'] ?? '') . ' ' . ($incoming['description'] ?? '')),
            'access'      => $access,
        ];
    }

    /**
     * @private
     * @param array $parsed Result of parseDocBlock() for a method docblock
     * @param bool $magic
     * @return array
     */
    private function buildMethodInfo(array $parsed, bool $magic = false, bool $constructor = false, bool $destructor = false): array
    {
        $info = [
            'visibility'  => null,
            'async'       => false,
            'static'      => false,
            'abstract'    => false,
            'final'       => false,
            'magic'       => $magic,
            'constructor' => $constructor,
            'destructor'  => $destructor,
            'params'      => [],
            'returns'     => null,
            'deprecated'  => false,
            'throws'      => [],
            'description' => $parsed['description'],
            'example'     => null,
            'path'        => '/'.str_replace(App::$appRoot, '', $this->_path)
        ];

        foreach ($parsed['tags'] as $tag) {
            $name = strtolower($tag['tag']);
            $raw = $tag['raw'];

            switch ($name) {
                case 'global':
                case 'public':
                case 'private':
                case 'protected':
                    $info['visibility'] = $name;
                    break;

                case 'static':
                    $info['static'] = true;
                    break;
                case 'async':
                    $info['async'] = true;
                    break;
                
                case 'deprecated':
                    $info['deprecated'] = true;
                    break;

                case 'abstract':
                    $info['abstract'] = true;
                    break;

                case 'final':
                    $info['final'] = true;
                    break;

                case 'magic':
                    $info['magic'] = true;
                    break;

                case 'prototypeof':
                    $info['prototypeof'] = $raw;
                    break;

                case 'throws':
                    if ($raw !== '') {
                        $parts = explode(' ', $raw);
                        $info['throws'][$parts[0]] = trim(implode(' ', \array_slice($parts, 1)));
                    }
                    break;
                case 'param':
                    $default = null;
                    [$type, $paramName, $desc] = $this->parseTypeNameDesc($raw);
                    $paramName = trim($paramName, '[]'); 
                    if(strstr($paramName, '=') !== false) {
                        [$paramName, $default] = explode('=', $paramName, 2);
                        $paramName = trim($paramName);
                        $default = trim($default);
                    }
                    $info['params'][] = [
                        'type'        => str_replace(['{', '}'], '', $type),
                        'name'        => $paramName,
                        'description' => $desc,
                        'default'     => $default,
                        'nullable'    => $this->isNullableType($type),
                    ];
                    break;

                case 'return':
                case 'returns':
                    [$type, $desc] = $this->parseTypeDesc($raw);
                    $info['returns'] = ['type' => $type, 'description' => $desc];
                    break;

                case 'example':
                    $info['example'] = $this->stripCodeFence($raw);
                    break;
            }
        }

        return $info;
    }

    /**
     * Parses "<type> <description>" (used by @return, @var).
     *
     * @private
     * @param string $raw
     * @return array{0: ?string, 1: string} [type, description]
     */
    private function parseTypeDesc(string $raw): array
    {
        if ($raw === '') {
            return [null, ''];
        }

        if (preg_match('/^(\S+)\s*(.*)$/s', $raw, $m)) {
            return [trim($m[1], '{}[] '), trim($m[2], '{}[]- ')];
        }

        return [null, $raw];
    }

    /**
     * Parses "<type> $name <description>" or "<type> name <description>"
     * (used by @param, @property, @property-read, @property-write).
     * The name is returned WITHOUT a leading "$".
     *
     * @private
     * @param string $raw
     * @return array{0: ?string, 1: ?string, 2: string} [type, name, description]
     */
    private function parseTypeNameDesc(string $raw): array
    {
        if ($raw === '') {
            return [null, null, ''];
        }

        // Предпочитаем явный $var — так однозначно понятно, где имя, а где тип
        if (preg_match('/^(.*?)\$(\w+)\s*(.*)$/s', $raw, $m)) {
            $type = trim($m[1]) ?: null;
            return [$type, $m[2], trim($m[3])];
        }

        // Без $ — считаем, что это "тип имя описание" через пробел (JS-стиль)
        if (preg_match('/^(\S+)\s+(\S+)\s*(.*)$/s', $raw, $m)) {
            return [$m[1], $m[2], trim($m[3])];
        }

        // Только одно слово — трактуем как имя без типа
        return [null, trim($raw) ?: null, ''];
    }

    /**
     * Parses an @method tag: "[static] <returntype> <name>(<args>) <description>".
     *
     * @private
     * @param string $raw
     * @param array<string, string> $useMap
     * @return array{name: string, static: bool, returnType: ?string, params: array, description: string}|null
     */
    private function parseMethodTag(string $raw, array $useMap): ?array
    {
        if (!preg_match(
            '/^(?:(static)\s+)?(\S+)\s+([A-Za-z_]\w*)\s*\(([^)]*)\)\s*(.*)$/s',
            $raw,
            $m
        )) {
            return null;
        }

        $params = [];
        $argsRaw = trim($m[4]);

        if ($argsRaw !== '') {
            foreach (preg_split('/\s*,\s*/', $argsRaw) as $arg) {
                if ($arg === '') {
                    continue;
                }

                if (preg_match('/^(.*?)\$(\w+)\s*(?:=\s*(.+))?$/', $arg, $am)) {
                    $type = trim($am[1]) ?: null;
                    $default = isset($am[3]) ? trim($am[3]) : null;
                    $params[] = [
                        'type'        => $type,
                        'name'        => $am[2],
                        'default'     => $default,
                        'nullable'    => $this->isNullableType($type) || $default === 'null',
                        'description' => null,
                    ];
                } else {
                    $params[] = [
                        'type'        => null,
                        'name'        => trim($arg),
                        'default'     => null,
                        'nullable'    => false,
                        'description' => null,
                    ];
                }
            }
        }

        return [
            'name'       => $m[3],
            'static'     => $m[1] !== '',
            'returnType' => $m[2],
            'params'     => $params,
            'description' => trim($m[5]),
        ];
    }

    /**
     * @private
     * @param ?string $type
     * @return bool
     */
    private function isNullableType(?string $type): bool
    {
        if ($type === null) {
            return false;
        }

        return str_starts_with($type, '?') || stripos($type, 'null|') === 0 || stripos($type, '|null') !== false;
    }

    /**
     * Strips a leading/trailing markdown code fence (``` ... ```), if present,
     * from an @example tag's raw content.
     *
     * @param string $raw
     * @return string
     */
    private function stripCodeFence(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```[\w]*\s*/', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);
        return trim($raw);
    }

    /**
     * Detects a JS getter/setter right after a docblock: "get name() {" / "set name(v) {".
     *
     * @private
     * @param string $afterCode
     * @return array{kind: string, name: string}|null
     */
    private function peekJsAccessor(string $afterCode): ?array
    {
        $afterCode = ltrim($afterCode);

        if (preg_match(
            '/^(?:(?:public|private|protected|static|async)\s+)*(get|set)\s+#?([A-Za-z_$][\w$]*)\s*\(/',
            $afterCode,
            $m
        )) {
            return ['kind' => $m[1], 'name' => $m[2]];
        }

        return null;
    }

    /**
     * Recovers a function/method name (PHP "function foo(" or JS "foo(" / "async foo(").
     *
     * @private
     * @param string $afterCode
     * @return string|null
     */
    private function peekFunctionName(string $afterCode): ?string
    {
        $afterCode = ltrim($afterCode);
        $afterCode = preg_replace('/^(\s*\/\/[^\n]*\n)+/', '', $afterCode);
        $afterCode = ltrim($afterCode);

        // PHP
        if (preg_match(
            '/^(?:(?:public|protected|private|static|abstract|final)\s+)*function\s+&?([A-Za-z_$][\w$]*)\s*\(/',
            $afterCode,
            $m
        )) {
            return $m[1];
        }

        // JS метод класса
        if (preg_match(
            '/^(?:(?:public|private|protected|static|async)\s+)*#?([A-Za-z_$][\w$]*)\s*\([^;{]*\)\s*\{/',
            $afterCode,
            $m
        )) {
            return $m[1];
        }

        // JS global method
        if (preg_match(
            '/\.prototype\.(\w+)\s*=\s*(?:async\s+)?function/',
            $afterCode,
            $m
        )) {
            return $m[1];
        }

        if (preg_match(
            '/^const\s+(\w+)\s*=\s*\(.*\)\s*=>/',
            $afterCode,
            $m
        )) {
            return $m[1];
        }

        if (preg_match(
            '/^const\s+(\w+)\s*=\s*function\s*\(/',
            $afterCode,
            $m
        )) {
            return $m[1];
        }

        if (preg_match(
            '/\.(\w+)\s*=\s*function/',
            $afterCode,
            $m
        )) {
            return $m[1];
        }

        return null;
    }

    /**
     * Recovers a property/field name (PHP "$name" or JS "name = ..."/"name;").
     *
     * @private
     * @param string $afterCode
     * @return string|null
     */
    private function peekFieldName(string $afterCode): ?string
    {
        $afterCode = ltrim($afterCode);
        $afterCode = preg_replace('/^(\s*\/\/[^\n]*\n)+/', '', $afterCode);
        $afterCode = ltrim($afterCode);

        // PHP: модификаторы + (?тип)? $name или Name (константа)
        if (preg_match(
            '/^(?:(?:public|protected|private|static|readonly)\s+)*\??[\w\\\\|]*\s*\$?([A-Za-z_]\w*)/',
            $afterCode,
            $m
        )) {
            return $m[1];
        }

        // JS: модификаторы + name = ... ; / name;
        if (preg_match(
            '/^(?:(?:public|private|protected|static|readonly)\s+)*#?([A-Za-z_$][\w$]*)\s*(?:=[^=>]|;)/',
            $afterCode,
            $m
        )) {
            return $m[1];
        }

        return null;
    }

    /**
     * Recovers class name/extends/implements/abstract/final from the raw
     * "class Foo extends Bar implements Baz {" declaration.
     *
     * @private
     * @param string $afterCode
     * @return string|null Class name only (extends/implements/modifiers are
     *                      handled separately in buildClassInformation())
     */
    private function peekClassName(string $afterCode): ?string
    {
        $afterCode = ltrim($afterCode);
        $afterCode = preg_replace('/^(\s*\/\/[^\n]*\n)+/', '', $afterCode);
        $afterCode = ltrim($afterCode);

        // это если пхп
        if (preg_match('/^(?:abstract\s+|final\s+)*class\s+([A-Za-z_$][\w$]*)/', $afterCode, $m)) {
            return $m[1];
        }

        // это если пхп
        if (preg_match('/^(?:abstract\s+|final\s+)*enum\s+([A-Za-z_$][\w$]*)/', $afterCode, $m)) {
            return $m[1];
        }

        // это если пхп
        if (preg_match('/^(?:abstract\s+|final\s+)*interface\s+([A-Za-z_$][\w$]*)/', $afterCode, $m)) {
            return $m[1];
        }

        // это если пхп
        if (preg_match('/^(?:abstract\s+|final\s+)*trait\s+([A-Za-z_$][\w$]*)/', $afterCode, $m)) {
            return $m[1];
        }

        //если js
        // Colibri.UI.Component = class extends Colibri.Events.Dispatcher {
        if(preg_match('/^([A-Za-z_$][\w$]*(?:\.[A-Za-z_$][\w$]*)*)\s*=\s*class\b/', $afterCode, $m)) {
            return $m[1];
        }

        return null;
    }


    /**
     * @private
     * @param string $code
     * @return string|null e.g. "App\Models", or null if no "namespace ...;" found
     */
    private function detectNamespace(string $code): ?string
    {
        if (preg_match('/^\s*namespace\s+([\w\\\\]+)\s*;/m', $code, $m)) {
            return trim($m[1], '\\');
        }

        return null;
    }

    /**
     * @private
     * @param string $code
     * @return array<string, string> short name => fully-qualified name
     */
    private function buildUseMap(string $code): array
    {
        $map = [];

        if (preg_match_all('/^\s*use\s+([\w\\\\]+)\\\\\{([^}]+)\}\s*;/m', $code, $groups, PREG_SET_ORDER)) {
            foreach ($groups as $g) {
                $prefix = trim($g[1], '\\');
                foreach (preg_split('/\s*,\s*/', trim($g[2])) as $item) {
                    if ($item === '') {
                        continue;
                    }
                    if (preg_match('/^([\w\\\\]+)(?:\s+as\s+(\w+))?$/', $item, $im)) {
                        $fqn = $prefix . '\\' . trim($im[1], '\\');
                        $short = $im[2] ?? substr(strrchr('\\' . $fqn, '\\'), 1);
                        $map[$short] = $fqn;
                    }
                }
            }
        }

        if (preg_match_all(
            '/^\s*use\s+(?!function\s+|const\s+)([\w\\\\]+)(?:\s+as\s+(\w+))?\s*;/m',
            $code,
            $uses,
            PREG_SET_ORDER
        )) {
            foreach ($uses as $u) {
                $fqn = trim($u[1], '\\');
                $short = $u[2] ?? substr(strrchr('\\' . $fqn, '\\'), 1);
                $map[$short] = $fqn;
            }
        }

        return $map;
    }

    /**
     * @private
     * @param string $name
     * @param array<string, string> $useMap
     * @return string
     */
    private function resolveClassName(string $name, array $useMap): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }

        if (!preg_match('/^([\w\\\\]+)(.*)$/', ltrim($name, '\\'), $m)) {
            return $name;
        }

        $classPart = $m[1];
        $suffix = $m[2];

        if (strpos($classPart, '\\') !== false) {
            return $classPart . $suffix;
        }

        if (isset($useMap[$classPart])) {
            return $useMap[$classPart] . $suffix;
        }

        return $classPart . $suffix;
    }
}
