<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */
namespace Colibri\Data\Storages\Fields;

use Colibri\Collections\ArrayList;

/**
 * Класс поле список файлов
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class FileListField extends ArrayList
{

    /**
     * Конструктор
     * @param string $data данные из поля
     * @return void
     */
    public function __construct($data)
    {
        parent::__construct([]);
        $data = str_replace("\n", "", str_replace("\r", "", $data));
        if (!empty($data)) {
            $sources = explode(';', $data);
            foreach ($sources as $file) {
                $this->Add(new FileField($file));
            }
        }
    }

    /**
     * Возвращает строку для записи в поле
     * @param string $splitter разделитель
     * @return string собранная строка из путей файлов
     */
    public function ToString(string $splitter = ';'): string
    {
        $sources = [];
        foreach ($this as $file) {
            $sources[] = $file->ToString();
        }
        return implode(';', $sources);
    }

    /**
     * Return string value of this object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->ToString();
    }


}