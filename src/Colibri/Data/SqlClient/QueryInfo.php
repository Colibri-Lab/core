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

class QueryInfo
{
    
    public string $type;

    public int $insertid;

    public int $affected;

    public string $error;

    public string $query;
    
    public function __construct(string $type, int $insertid, int $affected, string $error, string $query)
    {
        $this->type = $type;
        $this->insertid = $insertid;
        $this->affected = $affected;
        $this->error = $error;
        $this->query = $query;
    }
}
