<?php

/**
 * FtpFileSystem
 *
 * Represents a class for finding files and directories via FTP.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\FtpFileSystem
 */
namespace Colibri\IO\FtpFileSystem;

use Colibri\Collections\ArrayList;
use Colibri\Common\VariableHelper;
use Throwable;

/**
 * Class for finding files and directories.
 */
class Finder
{
    /**
     * The FTP connection information.
     *
     * @var object
     */
    private object $_connectionInfo;

    /**
     * The FTP connection.
     *
     * @var mixed
     */
    private mixed $_connection;

    /**
     * Constructor.
     *
     * Initializes a new instance of the Finder class.
     *
     * @param object $connectionInfo The FTP connection information.
     */
    public function __construct(object $connectionInfo)
    {
        $this->_connectionInfo = $connectionInfo;
        $this->_connect();
    }

    /**
     * Destructor.
     *
     * Closes the FTP connection when the object is destroyed.
     */
    public function __destruct()
    {
        if($this->_connection) {
            ftp_close($this->_connection);
        }
    }

    /**
     * Establishes the FTP connection.
     *
     * @throws Exception if connection fails.
     */
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

    /**
     * Reconnects to the FTP server.
     *
     * @return mixed The FTP connection.
     */
    public function Reconnect(): mixed
    {
        $this->_connect();
        return $this->_connection;
    }

    /**
     * Lists files and directories via FTP.
     *
     * @param string $path The path to list.
     * @param bool $recursive Whether to list recursively.
     * @return array An array containing FTP file information.
     */
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
     * Lists files in the specified path.
     *
     * @param string $path The path to search.
     * @param string $match Regular expression to match file names.
     * @param string $sortField Field for sorting.
     * @param int $sortType Sorting type.
     * @return ArrayList An ArrayList containing found files.
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
