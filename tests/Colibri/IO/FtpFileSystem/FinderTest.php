<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FtpFileSystem\Finder;
use Colibri\IO\FtpFileSystem\Exception;

class FinderTest extends TestCase
{
    private $connectionInfo;

    protected function setUp(): void
    {
        $this->connectionInfo = (object)[
            'host' => 'ftp.example.com',
            'port' => 21,
            'timeout' => 90,
            'user' => 'username',
            'password' => 'password',
            'passive' => true
        ];
    }

    public function testConstruct()
    {
        $finder = new Finder($this->connectionInfo);
        $this->assertInstanceOf(Finder::class, $finder);
    }

    public function testReconnect()
    {
        $finder = new Finder($this->connectionInfo);
        $connection = $finder->Reconnect();
        $this->assertNotNull($connection);
    }

    public function testFiles()
    {
        $finder = new Finder($this->connectionInfo);
        $files = $finder->Files('/');
        $this->assertInstanceOf(\Colibri\Collections\ArrayList::class, $files);
    }

    public function testInvalidConnection()
    {
        $this->connectionInfo->host = 'invalid.host';
        $this->expectException(Exception::class);
        new Finder($this->connectionInfo);
    }
}
