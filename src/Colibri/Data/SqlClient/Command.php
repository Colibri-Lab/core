<?php

/**
 * Интерфейсы для драйверов к базе данных
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */

namespace Colibri\Data\SqlClient;

/**
 * Абстрактный класс для выполнения команд в точку доступа
 * 
 * @property string $query
 * @property string $commandtext
 * @property string $text
 * @property IConnection $connection
 * @property-read string $type
 * @property int $page
 * @property int $pagesize
 * 
 * @testFunction testCommand
 */
abstract class Command
{

    /**
     * Коннект к базе данных
     *
     * @var IConnection
     */
    protected ?IConnection $_connection = null;

    /**
     * Командная строка
     *
     * @var string
     */
    protected string $_commandtext = '';

    /**
     * Размер страницы
     *
     * @var integer
     */
    protected int $_pagesize = 10;

    /**
     * Текущая строка
     *
     * @var integer
     */
    protected int $_page = -1;

    /**
     * Параметры запроса
     *
     * @var array
     */
    protected ?array $_params = null;

    /**
     * Конструктор
     *
     * @param string $commandtext
     * @param IConnection $connection
     */
    public function __construct(string $commandtext = '', ?IConnection $connection = null)
    {
        $this->_commandtext = $commandtext;
        $this->_connection = $connection;
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     * @testFunction testCommand__get
     */
    public function __get(string $property): mixed
    {
        $return = null;
        switch (strtolower($property)) {
            case 'query':
            case 'commandtext':
            case 'text': {
                    $return = $this->_commandtext;
                    break;
                }
            case 'connection': {
                    $return = $this->_connection;
                    break;
                }
            case 'type': {
                    $parts = explode(' ', $this->query);
                    $return = strtolower($parts[0]);
                    break;
                }
            case 'page': {
                    $return = $this->_page;
                    break;
                }
            case 'pagesize': {
                    $return = $this->_pagesize;
                    break;
                }
            case 'params': {
                    return $this->_params;
                }
            default: {
                    $return = null;
                }
        }
        return $return;
    }

    /**
     * Сеттер
     *
     * @param string $property
     * @param mixed $value
     * @testFunction testCommand__set
     */
    public function __set(string $property, mixed $value): void
    {
        switch (strtolower($property)) {
            case 'query':
            case 'commandtext':
            case 'text': {
                    $this->_commandtext = $value;
                    break;
                }
            case 'connection': {
                    $this->_connection = $value;
                    break;
                }
            case "page": {
                    $this->_page = $value;
                    break;
                }
            case "pagesize": {
                    $this->_pagesize = $value;
                    break;
                }
            case 'params': {
                    $this->_params = $value;
                    break;
                }
            default:
        }
    }

    /**
     * Выполняет запрос и возвращает IDataReader
     *
     * @return IDataReader
     */
    abstract public function ExecuteReader(bool $info = true): IDataReader;

    /**
     * Выполняет запрос и возвращает QueryInfo
     *
     * @return QueryInfo
     */
    abstract public function ExecuteNonQuery(): QueryInfo;

    /**
     * Подготавливает строку, добавляет постраничку и все, что необходимо для конкретного драйвера
     *
     * @return string
     */
    abstract public function PrepareQueryString(): string;
}
