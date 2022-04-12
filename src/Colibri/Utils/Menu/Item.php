<?php

namespace Colibri\Utils\Menu;
use ScssPhp\ScssPhp\Ast\Sass\Expression\VariableExpression;
use Colibri\Common\VariableHelper;

class Item implements \JsonSerializable
{

    private ?object $_data = null;

    public ?Item $parent = null;

    public function __construct(string $name, string $title, string $description, string $icon = '', string $execute = '')
    {
        $this->parent = null;
        $this->_data = (object)[
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'execute' => $execute,
            'icon' => $icon,
            'index' => '/' . $name . '/',
            'enabled' => true,
            'children' => []
        ];

    }

    static function Create(string $name, string $title, string $description, string $icon = '', string $execute = ''): self
    {
        return new self($name, $title, $description, $icon, $execute);
    }

    static function FromArray(array $array): self 
    {
        $item = new self($array['name'] ?? '', $array['title'] ?? '', $array['description'] ?? '', $array['icon'] ?? '', $array['execute'] ?? '');
        if(!empty($array['children'] ?? []) && is_array($array['children'])) {
            foreach($array['children'] as $itemArray) {
                $item->Add(Item::FromArray($itemArray));
            }
        }
        return $item;
    }

    public function __get(string $name): mixed
    {
        if (isset($this->_data->$name)) {
            return $this->_data->$name;
        }
        return null;
    }

    public function Route(): string
    {
        $route = '/';
        if ($this->parent) {
            $route = $this->parent->Route();
        }
        return $route . $this->name . '/';
    }

    public function Merge(array $items): void
    {
        foreach ($items as $item) {
            $this->Add($item);
        }
    }

    private function _find(string $name): self|null
    {
        foreach ($this->children as $item) {
            if ($item->name == $name) {
                return $item;
            }
        }
        return null;
    }

    public function Add(Item|array $item): Item
    {

        if(is_array($item)) {
            foreach($item as $i) {
                $this->Add($i);
            }
        }
        else {
            $item->parent = $this;
            if (!($found = $this->_find($item->name))) {
                $this->_data->children[] = $item;
            }
            else {
                $found->Merge($item->children);
            }
        }
        return $this;
    }

    public function jsonSerialize(): object|array
    {
        $this->_data->index = $this->Route();
        return $this->_data;
    }

    public function ToArray(): array|object
    {
        return json_decode(json_encode($this), true);
    }


}