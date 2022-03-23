<?php

/**
 * Интерфейсы для драйверов к базе данных
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */

namespace Colibri\Data\SqlClient;

/**
 * Интерфейс который должны реализовать все классы создателей запросов в точках доступа
 */
interface IQueryBuilder
{
    /**
     * Создать запрос ввода данных
     *
     * @param string $table
     * @param object $data
     * @param string $returning
     * @return string
     */
    public function CreateInsert($table, $data, $returning = '');

    /**
     * Создать запрос ввода данных или обновления в случае дублирования данных в индексных полях
     *
     * @param string $table
     * @param object $data
     * @param array $exceptFields
     * @param string $returning
     * @return string
     */
    public function CreateInsertOrUpdate($table, $data, $exceptFields = array(), $returning = '');

    /**
     * Создать запрос ввода данных пачкой
     *
     * @param string $table
     * @param array $data
     * @return string
     */
    public function CreateBatchInsert($table, $data);

    /**
     * Создать запрос на обновление данных
     *
     * @param string $table
     * @param string $condition
     * @param object $data
     * @return string
     */
    public function CreateUpdate($table, $condition, $data);

    /**
     * Создать запрос на удаление данных
     *
     * @param string $table
     * @param string $condition
     * @return string
     */
    public function CreateDelete($table, $condition);

    /**
     * Создать запрос на получение списка таблиц
     *
     * @return string
     */
    public function CreateShowTables();

    /**
     * Создать запрос на получение списка полей в таблице
     *
     * @param string $table
     * @return string
     */
    public function CreateShowField($table);
}
