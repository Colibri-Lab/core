<?php

namespace Colibri\Data\Storages\Fields;

use Colibri\Utils\Debug;
use DateTime;
use JsonSerializable;

/**
 * Класс для работы с полями типа datatime
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class DateField extends DateTimeField {

    /**
     * Return Date in ISO8601 format
     *
     * @return String
     */
    public function __toString() {
        return $this->format('yyyy-MM-dd');
    }
    
}

