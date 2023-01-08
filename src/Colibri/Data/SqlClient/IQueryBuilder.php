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
     * @param array|object $data
     * @param string $returning
     * @return string
     */
    public function CreateInsert(string $table, array |object $data, string $returning = ''): string;

    /**
     * Создать запрос ввода/обновления данных
     *
     * @param string $table
     * @param array|object $data
     * @param string $returning
     * @return string
     */
    public function CreateReplace(string $table, array |object $data, string $returning = ''): string;

    /**
     * Создать запрос ввода данных или обновления в случае дублирования данных в индексных полях
     *
     * @param string $table
     * @param array|object $data
     * @param array $exceptFields
     * @param string $returning
     * @return string
     */
    public function CreateInsertOrUpdate(string $table, array |object $data, array $exceptFields = array(), string $returning = '');

    /**
     * Создать запрос ввода данных пачкой
     *
     * @param string $table
     * @param array|object $data
     * @return string
     */
    public function CreateBatchInsert(string $table, array |object $data);

    /**
     * Создать запрос на обновление данных
     *
     * @param string $table
     * @param string $condition
     * @param array|object $data
     * @return string
     */
    public function CreateUpdate(string $table, string $condition, array |object $data): string;

    /**
     * Создать запрос на удаление данных
     *
     * @param string $table
     * @param string $condition
     * @return string
     */
    public function CreateDelete(string $table, string $condition): string;

    /**
     * Создать запрос на получение списка таблиц
     *
     * @return string
     */
    public function CreateShowTables(): string;

    /**
     * Создать запрос на получение списка полей в таблице
     *
     * @param string $table
     * @return string
     */
    public function CreateShowField(string $table): string;
}