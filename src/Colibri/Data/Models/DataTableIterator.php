<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Models
 */

namespace Colibri\Data\Models;

use Colibri\Collections\ArrayListIterator;

/**
 * Итератор для DataTable
 * @method DataRow current()
 * @method DataRow next()
 */
class DataTableIterator extends ArrayListIterator
{
}
