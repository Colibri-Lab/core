<?php

/**
 * SqlClient
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\SqlClient
 * 
 */

namespace Colibri\Data\SqlClient;

/**
 * Описание поля данных в запросе
 * @testFunction testDataField
 */
class DataField
{

    /**
     * Имя базы данных
     *
     * @var string
     */
    public string $db;

    /**
     * Имя столбца
     *
     * @var string
     */
    public string $name;

    /**
     * Исходное имя столбца, если у него есть псевдоним
     * 
     * @var string
     */
    public string $originalName;

    /**
     * Имя таблицы, которой принадлежит столбец (если не вычислено)
     */
    public string $table;

    /**
     * Исходное имя таблицы, если есть псевдоним
     *
     * @var string
     */
    public string $originalTable;

    /**
     * Имя таблицы, имя поля в формате необходимом для работы с базой данных
     * 
     * @var string
     */
    public string $escaped;

    /**
     * Зарезервировано для значения по умолчанию, на данный момент всегда ""
     *
     * @var string
     */
    public string $def;

    /**
     * Максимальная ширина поля результирующего набора.
     *
     * @var int
     */
    public int $maxLength;

    /**
     * Ширина поля, как она задана при определении таблицы.
     *
     * @var int
     */
    public int $length;

    /**
     * Целое число, представляющее битовые флаги для поля.
     *
     * @var array
     */
    public array $flags;

    /**
     * Тип данных поля
     *
     * @var string
     */
    public string $type;

    /**
     * Число знаков после запятой (для числовых полей)
     *
     * @var int
     */
    public int $decimals;
    
}
