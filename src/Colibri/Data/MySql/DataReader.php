<?php

/**
 * Драйвер для MySql
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */

namespace Colibri\Data\MySql;

use Colibri\Data\SqlClient\DataField;
use Colibri\Data\SqlClient\IDataReader;
use Throwable;
use resource;

/**
 * Класс обеспечивающий работу с результатами запросов
 * 
 * @property-read bool $hasRows
 * @property int $affected
 * @property-read int $count
 * 
 * @testFunction testDataReader
 */
final class DataReader implements IDataReader
{
    /**
     * Ресурс запроса
     *
     * @var mixed
     */
    private mixed $_results = null;

    /**
     * Количество результатов в текущей стрнице запроса
     *
     * @var int
     */
    private ?int $_count = null;

    /**
     * Общее количество результатов
     * Заполнено только тогда когда запрос выполнен с параметром info=true в ExecuteReader
     *
     * @var int
     */
    private ?int $_affected = null;

    /**
     * Строка зарпоса после обработки
     * @var string|null
     */
    private ?string $_preparedQuery = null;

    /**
     * Создание обьекта
     *
     * @param mixed $results
     * @param int $affected
     */
    public function __construct(mixed $results, ?int $affected = null, ?string $preparedQuery = null)
    {
        $this->_results = $results;
        $this->_affected = $affected;
        $this->_preparedQuery = $preparedQuery;
    }

    /**
     * Закрытие ресурса обязательно
     */
    public function __destruct()
    {
        $this->Close();
    }

    /**
     * Закрывает ресурс
     *
     * @return void
     * @testFunction testDataReaderClose
     */
    public function Close(): void
    {
        if ($this->_results && isset($this->_results->current_field)) {
            mysqli_free_result($this->_results);
            $this->_results = null;
        }
    }

    /**
     * Считать следующую строку в запросе
     *
     * @return object|null
     * @testFunction testDataReaderRead
     */
    public function Read(): ?object
    {
        $result = mysqli_fetch_object($this->_results);
        if (!$result) {
            return null;
        }

        return $result;
    }

    /**
     * Список полей в запросе
     *
     * @return array
     * @testFunction testDataReaderFields
     */
    public function Fields(): array
    {
        $fields = array();
        $num = mysqli_num_fields($this->_results);
        for ($i = 0; $i < $num; $i++) {
            $f = mysqli_fetch_field_direct($this->_results, $i);
            $field = new DataField();
            $field->db = $f->db;
            $field->name = $f->name;
            $field->originalName = $f->orgname;
            $field->table = $f->table;
            $field->originalTable = $f->orgtable;
            $field->def = $f->def;
            $field->maxLength = $f->max_length;
            $field->length = $f->length;
            $field->decimals = $f->decimals;
            $field->type = $this->_type2txt($f->type);
            $field->flags = $this->_flags2txt($f->flags);
            $field->escaped = '`' . $field->originalTable . '`.`' . $field->originalName . '`';
            $fields[$f->name] = $field;
        }
        return $fields;
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     * @testFunction testDataReader__get
     */
    public function __get(string $property): mixed
    {
        $return = null;
        $property = strtolower($property);
        switch ($property) {
            case 'hasrows': {
                    $return = $this->_results && mysqli_num_rows($this->_results) > 0;
                    break;
                }
            case 'affected': {
                    $return = $this->_affected;
                    break;
                }
            case 'count': {
                    if (is_null($this->_count)) {
                        $this->_count = mysqli_num_rows($this->_results);
                    }
                    $return = $this->_count;
                    break;
                }
            default:
                $return = null;
        }
        return $return;
    }

    public function __set(string $property, mixed $value): void
    {
        if (strtolower($property) == 'affected') {
            $this->_affected = $value;
        }
    }
    /**
     * Возвращает количество
     * @return int 
     */
    public function Count(): int
    {
        return $this->count;
    }

    /**
     * Конвертация типов
     *
     * @param string $type_id
     * @return string
     * @testFunction testDataReader_type2txt
     */
    private function _type2txt(string $type_id): string
    {
        static $types;

        if (!isset($types)) {
            $types = array();
            $constants = get_defined_constants(true);
            foreach ($constants['mysqli'] as $c => $n) {
                if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m)) {
                    $types[$n] = $m[1];
                }
            }
        }

        return array_key_exists($type_id, $types) ? $types[$type_id] : null;
    }

    /**
     * Конвертация флафов
     *
     * @param int $flags_num
     * @return array
     * @testFunction testDataReader_flags2txt
     */
    private function _flags2txt(int $flags_num): array
    {
        static $flags;

        if (!isset($flags)) {
            $flags = array();
            $constants = get_defined_constants(true);
            foreach ($constants['mysqli'] as $c => $n) {
                if (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m) && !array_key_exists($n, $flags)) {
                    $flags[$n] = $m[1];
                }
            }
        }

        $result = array();
        foreach ($flags as $n => $t) {
            if ($flags_num& $n) {
                $result[] = $t;
            }
        }
        return $result;
    }
}