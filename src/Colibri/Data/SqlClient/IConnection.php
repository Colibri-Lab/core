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
    public function Open();

    /**
     * Переоткрывает закрытое соеднинение
     *
     * @return bool
     */
    public function Reopen();

    /**
     * Закрывает соединение
     *
     * @return bool
     */
    public function Close();
}
