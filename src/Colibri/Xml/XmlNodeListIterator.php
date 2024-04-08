<?php

/**
 * Xml
 *
 * This class represents a query executor for XML documents.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml
 *
 */
namespace Colibri\Xml;

use Colibri\Collections\ArrayListIterator;

/**
 * XmlNodeListIterator
 *
 * This class represents an iterator for XmlNodeList.
 *
 * @method XmlNode current() Returns the current node.
 * @method XmlNode next() Moves to the next node.
 */
class XmlNodeListIterator extends ArrayListIterator
{
}