<?php

namespace Colibri\Tests\Xml;

use Colibri\Xml\XmlNode;
use Colibri\Xml\XmlNamedNodeList;
use Colibri\Xml\XmlNamedNodeListIterator;
use PHPUnit\Framework\TestCase;

class XmlNamedNodeListIteratorTest extends TestCase
{
    public function testIteration()
    {
        $xmlString = '<root><element name="test1">value1</element><element name="test2">value2</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $xmlNamedNodeList = $xmlNode->getElementsByName('element');
        $iterator = new XmlNamedNodeListIterator($xmlNamedNodeList);

        $values = [];
        foreach ($iterator as $node) {
            $values[] = $node->value;
        }

        $this->assertEquals(['value1', 'value2'], $values);
    }
}
