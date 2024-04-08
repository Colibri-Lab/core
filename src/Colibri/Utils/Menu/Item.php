<?php

/**
 * Menu
 *
 * @package Colibri\Utils\Menu
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 * 
 */
namespace Colibri\Utils\Menu;

/**
 * Represents an item in a menu.
 */
class Item implements \JsonSerializable
{

    private ?object $_data = null;

    public ? Item $parent = null;

    /**
     * Constructor for creating a new menu item.
     *
     * @param string $name The name of the item.
     * @param string $title The title of the item.
     * @param string $description The description of the item.
     * @param string $icon The icon associated with the item.
     * @param string $execute The action to execute when the item is clicked.
     */
    public function __construct(string $name, string $title, string $description, string $icon = '', string $execute = '')
    {
        $this->parent = null;
        $this->_data = (object) [
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

    /**
     * Creates a new menu item.
     *
     * @param string $name The name of the item.
     * @param string $title The title of the item.
     * @param string $description The description of the item.
     * @param string $icon The icon associated with the item.
     * @param string $execute The action to execute when the item is clicked.
     * @return self The newly created menu item.
     */
    static function Create(string $name, string $title, string $description, string $icon = '', string $execute = ''): self
    {
        return new self($name, $title, $description, $icon, $execute);
    }

    /**
     * Creates a menu item from an array.
     *
     * @param array $array The array containing item data.
     * @return self The newly created menu item.
     */
    static function FromArray(array $array): self
    {
        $item = new self($array['name'] ?? '', $array['title'] ?? '', $array['description'] ?? '', $array['icon'] ?? '', $array['execute'] ?? '');
        if (!empty($array['children'] ?? []) && is_array($array['children'])) {
            foreach ($array['children'] as $itemArray) {
                $item->Add(Item::FromArray($itemArray));
            }
        }
        return $item;
    }

    /**
     * Magic method to get properties dynamically.
     *
     * @param string $name The name of the property.
     * @return mixed|null The value of the property or null if not found.
     */
    public function __get(string $name): mixed
    {
        if (isset($this->_data->$name)) {
            return $this->_data->$name;
        }
        return null;
    }

    /**
     * Generates the route for the item.
     *
     * @return string The route of the item.
     */
    public function Route(): string
    {
        $route = '/';
        if ($this->parent) {
            $route = $this->parent->Route();
        }
        return $route . $this->name . '/';
    }

    /**
     * Merges the given items with this item.
     *
     * @param array $items The items to merge.
     * @return void
     */
    public function Merge(array $items): void
    {
        foreach ($items as $item) {
            $this->Add($item);
        }
    }

    /**
     * Internal method to find an item by name.
     *
     * @param string $name The name of the item to find.
     * @return self|null The found item or null if not found.
     */
    private function _find(string $name): self|null
    {
        foreach ($this->children as $item) {
            if ($item->name == $name) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Adds an item or items to this item.
     *
     * @param Item|array $item The item or items to add.
     * @return Item This item.
     */
    public function Add(Item|array $item): Item
    {

        if (is_array($item)) {
            foreach ($item as $i) {
                $this->Add($i);
            }
        } else {
            $item->parent = $this;
            if (!($found = $this->_find($item->name))) {
                $this->_data->children[] = $item;
            } else {
                $found->Merge($item->children);
            }
        }
        return $this;
    }

    /**
     * Serializes the item to JSON.
     *
     * @return object|array The serialized item.
     */
    public function jsonSerialize(): object|array
    {
        $this->_data->index = $this->Route();
        return $this->_data;
    }

    /**
     * Converts the item to an array.
     *
     * @return array|object The converted item.
     */
    public function ToArray(): array |object
    {
        return json_decode(json_encode($this), true);
    }



}