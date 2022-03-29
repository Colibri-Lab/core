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


use Colibri\Data\SqlClient\NonQueryInfo as SqlNonQueryInfo;

/**
 * Класс для хранения результатов запроса, если не требуется получение табличных данных
 * 
 * @property string $type
 * @property int $insertid
 * @property int $affected
 * @property string $error
 * @property string $query
 * 
 */
final class NonQueryInfo extends SqlNonQueryInfo
{

    public function __construct(string $type, int $insertid, int $affected, string $error, string $query)
    {
        $this->type = $type;
        $this->insertid = $insertid;
        $this->affected = $affected;
        $this->error = $error;
        $this->query = $query;
    }
}
