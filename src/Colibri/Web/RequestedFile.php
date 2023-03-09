<?php

/**
 * Класс описывающий файл отправленный в запрос
 * Только для чтения
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Web
 * @version 1.0.0
 * 
 */

namespace Colibri\Web;
use Symfony\Component\String\Exception\InvalidArgumentException;

/**
 * Файл из списка файлов запроса
 * 
 * @property boolean $isValid
 * @property string $binary
 * 
 * @testFunction testRequestedFile
 */
class RequestedFile
{

    /**
     * Название файла
     *
     * @var string
     */
    public string $name;
    /**
     * Расширение файла
     *
     * @var string
     */
    public string $ext;
    /**
     * Тип файла
     *
     * @var string
     */
    public string $mimetype;
    /**
     * Ошибка
     *
     * @var string
     */
    public string $error;
    /**
     * Размер файла в байтах
     *
     * @var int
     */
    public int $size;
    /**
     * Пусть к временному файлу
     *
     * @var string
     */
    public string $temporary;

    /**
     * Конструктор
     *
     * @param array|object $arrFILE
     */
    function __construct(array |object $arrFILE)
    {

        if (!$arrFILE) {
            return;
        }

        $arrFILE = (array) $arrFILE;

        $this->name = $arrFILE["name"];
        $ret = preg_split("/\./i", $this->name);
        if (count($ret) > 1) {
            $this->ext = $ret[count($ret) - 1];
        }
        $this->mimetype = $arrFILE["type"];
        $this->temporary = $arrFILE["tmp_name"];
        $this->error = $arrFILE["error"];
        $this->size = $arrFILE["size"];
    }

    /**
     * Магический метод
     *
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop): mixed
    {
        $prop = strtolower($prop);
        if ($prop == 'isvalid') {
            return !empty($this->name);
        } elseif ($prop == 'binary') {
            if(!$this->temporary) {
                throw new InvalidArgumentException('File path can not be empty');
            }
            return file_get_contents($this->temporary);
        }
        return null;
    }

    /**
     * Удаление класса
     */
    function __destruct()
    {
        // if (file_exists($this->temporary)) {
        //     unlink($this->temporary);
        // }
    }

    /**
     * Сохраняет временый файл в указанную директорую
     *
     * @param string $path
     * @return void
     * @testFunction testRequestedFileMoveTo
     */
    function MoveTo(string $path, int $mode = 0777): void
    {
        rename($this->temporary, $path);
        chmod($path, $mode);
    }
}