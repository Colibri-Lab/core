<?php

namespace Colibri\Utils\Menu;

class Item implements \JsonSerializable
{

    private ?object $_data = null;

    public ?Item $parent = null;

    public function __construct(string $name, string $description, string $className = '', bool $isImportant = false, string $execute = '')
    {
        $this->parent = null;
        $this->_data = (object)[
            'name' => $name,
            'description' => $description,
            'class' => $className,
            'important' => $isImportant,
            'execute' => $execute,
            'index' => '/' . $name . '/',
            'enabled' => true,
            'children' => []
        ];

    }

    public function __get(string $name): mixed
    {
        if (isset($this->_data->$name)) {
            return $this->_data->$name;
        }
        return null;
    }

    static function Create(string $name, string $description, string $className = '', bool $isImportant = false, string $execute = ''): self
    {
        return new self($name, $description, $className, $isImportant, $execute);
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

    public function Add(Item $item): Item
    {
        $item->parent = $this;
        if (!($found = $this->_find($item->name))) {
            $this->_data->children[] = $item;
        }
        else {
            $found->Merge($item->children);
        }

        return $this;
    }

    public function jsonSerialize(): object|array
    {
        $this->_data->index = $this->Route();
        return $this->_data;
    }

}