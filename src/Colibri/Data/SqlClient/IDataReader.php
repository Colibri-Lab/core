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
 * Интерфейс для всех классов DataReader в точке доступа
 */
interface IDataReader
{
    /**
     * Возвращает список полей в запросе
     *
     * @return string[]
     */
    public function Fields(): array;

    /**
     * Считывает следующую строку и возвращет в виде обьекта
     *
     * @return object
     */
    public function Read(): ?object;

    /**
     * Закрывает ресурс запроса
     *
     * @return void
     */
    public function Close(): void;

    /**
     * Возвращает количество строк
     *
     * @return int
     */
    public function Count(): int;
}
