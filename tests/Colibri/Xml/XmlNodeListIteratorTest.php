<?php

namespace Colibri\Tests\Xml;

use Colibri\Xml\XmlNode;
use Colibri\Xml\XmlNodeList;
use Colibri\Xml\XmlNodeListIterator;
use PHPUnit\Framework\TestCase;

class XmlNodeListIteratorTest extends TestCase
{
    public function testIteration()
    {
        $xmlString = '<root><element>value1</element><element>value2</element></root>';
        $xmlNode = XmlNode::LoadNode($xmlString, false);
        $xmlNodeList = $xmlNode->children;
        $iterator = new XmlNodeListIterator($xmlNodeList);

        $values = [];
        foreach ($iterator as $node) {
            $values[] = $node->value;
        }

        $this->assertEquals(['value1', 'value2'], $values);
    }
}
