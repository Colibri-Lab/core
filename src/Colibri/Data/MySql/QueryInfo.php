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


use Colibri\Data\SqlClient\QueryInfo as SqlQueryInfo;

/**
 * Класс для хранения результатов запроса, если не требуется получение табличных данных
 */
final class QueryInfo extends SqlQueryInfo
{

}