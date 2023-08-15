<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FtpFileSystem;

use Colibri\Collections\ArrayList;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Debug;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use DirectoryIterator;
use Throwable;

/**
 * Класс помогающий искать файлы и директории
 * @testFunction testFinder
 */
class Finder
{
    private object $_connectionInfo;

    private mixed $_connection;

    /**
     * Конструктор
     */
    public function __construct(object $connectionInfo)
    {
        $this->_connectionInfo = $connectionInfo;
        $this->_connect();
    }

    public function __destruct()
    {
        if($this->_connection) {
            ftp_close($this->_connection);
        }
    }

    private function _connect()
    {

        $this->_connection = ftp_connect(
            $this->_connectionInfo->host,
            $this->_connectionInfo->port,
            $this->_connectionInfo->timeout
        );
        if(!$this->_connection) {
            throw new Exception('Can not connect to host');
        }

        if(!ftp_login($this->_connection, $this->_connectionInfo->user, $this->_connectionInfo->password)) {
            throw new Exception('Can not login');
        }

        if(!ftp_pasv($this->_connection, $this->_connectionInfo->passive)) {
            throw new Exception('Can not set pasv mode');
        }

    }

    public function Reconnect(): mixed
    {
        $this->_connect();
        return $this->_connection;
    }

    private function _ftpList(string $path, bool $recursive = false): array
    {
        $list = ftp_rawlist($this->_connection, $path, false);
        if(!$list) {
            return [];
        }

        $ret = [];
        foreach ($list as $child) {
            $chunks = preg_split("/\s+/", $child);

            $item = [];
            [
                $item['perm'],
                $item['number'],
                $item['user'],
                $item['group'],
                $item['size'],
                $item['month'],
                $item['day'],
                $item['time']
            ] = $chunks;
            $item['type'] = substr($chunks[0], 0, 1) === 'd' ? 'directory' : 'file';

            array_splice($chunks, 0, 8);
            $item['name'] = $path . '/' . implode(" ", $chunks);

            $ret[] = (object)$item;
        }

        return $ret;

    }

    /**
     * Найти файлы
     *
     * @param string $path путь к папке
     * @param string $match регулярное выражение
     * @param string $sortField поле для сориторовки
     * @param int $sortType тип сортировки
     * @return ArrayList
     * @testFunction testFinderFiles
     */
    public function Files(string $path, string $match = '/.*/', string $sortField = '', int $sortType = SORT_ASC)
    {

        $ret = new ArrayList();

        try {

            $list = $this->_ftpList($path, false);
            foreach($list as $item) {

                if(in_array($item->type, ['directory'])) {
                    continue;
                }

                if (!VariableHelper::IsEmpty($match) && preg_match($match, $item->name) == 0) {
                    continue;
                }

                $ret->Add(new File($item, $this->_connection, $this));
            }

        } catch (Throwable $e) {
            $ret->Clear();
        }

        if ($sortField) {
            $ret->Sort($sortField, $sortType);
        }

        return $ret;
    }

}
