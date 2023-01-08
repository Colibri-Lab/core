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
 * Интерфейс который должны обеспечить все классы подключения точек доступа
 */
interface IConnection
{
    /**
     * Открытие подключения
     *
     * @return bool
     */
    public function Open(): bool;

    /**
     * Переоткрывает закрытое соеднинение
     *
     * @return bool
     */
    public function Reopen(): bool;

    /**
     * Закрывает соединение
     *
     * @return void
     */
    public function Close(): void;

}