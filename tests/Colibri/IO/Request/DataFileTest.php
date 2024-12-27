<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\DataFile;

class DataFileTest extends TestCase
{
    public function testConstructor()
    {
        $dataFile = new DataFile('name', 'fileData', 'filename', 'mime/type');
        $this->assertEquals('name', $dataFile->name);
        $this->assertEquals('fileData', $dataFile->value);
        $this->assertEquals('filename', $dataFile->file);
        $this->assertEquals('mime/type', $dataFile->mime);
    }
}
