<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Models
 */

namespace Colibri\Data\Models;

use Colibri\Collections\ArrayListIterator;

/**
 * DataTable iterator
 * @method DataRow current()
 * @method DataRow next()
 */
class DataTableIterator extends ArrayListIterator
{
}