<?php

/**
 * Trees
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Trees
 */

namespace Colibri\Data\Trees;

use Colibri\Common\VariableHelper;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\DataAccessPointsException;
use Colibri\Data\Models\DataRow;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\SqlClient\QueryInfo;

class NestedSet
{
    /**
     * Ошибки
     *
     * @var array
     */
    public $ERRORS = array();
    /**
     * Сообщения об ошибках
     *
     * @var array
     */
    public $ERRORS_MES = array();

    /**
     * Точка доступа
     *
     * @var DataAccessPoint
     */
    private $dataPoint;

    /**
     * Таблица
     *
     * @var string
     */
    private $_table;
    /**
     * Поле ID
     *
     * @var string
     */
    private $_table_id;
    /**
     * Левый индекс
     *
     * @var string
     */
    private $_table_left;
    /**
     * Правый индекс
     *
     * @var string
     */
    private $_table_right;
    /**
     * ПОле уровень
     *
     * @var string
     */
    private $_table_level;
    /**
     * Поле parent
     *
     * @var string
     */
    private $_table_parent;

    /**
     * Конструктор
     *
     * @param DataAccessPoint $dtp
     * @param string $table
     * @param string $id
     * @param string $left
     * @param string $right
     * @param string $level
     * @param string $parent
     */
    public function __construct($dtp, $table, $id, $left, $right, $level, $parent)
    {
        $this->dataPoint = $dtp;
        $this->_table = $table;
        $this->_table_id = $id;
        $this->_table_left = $left;
        $this->_table_right = $right;
        $this->_table_level = $level;
        $this->_table_parent = $parent;
    }

    /**
     * Ошибка
     *
     * @param string $file
     * @param string $class
     * @param string $function
     * @param int $line
     * @param string $sql
     * @param int $error
     * @return void
     */
    private function _setError($file, $class, $function, $line, $sql, $error)
    {
        $this->ERRORS[] = array(2, 'SQL query error.', $file . '::' . $class . '::' . $function . '::' . $line, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . $error);
        $this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        return $this->{'_table_'.$property};
    }

    /**
     * Очищает данные
     *
     * @param array $data
     * @return bool
     */
    public function Clear($data = array())
    {
        try {
            $return = false;
            $sql = 'TRUNCATE ' . $this->_table;
            $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            if ($res->error) {
                $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            } else {
                $sql = 'DELETE FROM ' . $this->_table;
                $res = $this->dataPoint->Query($sql);
                if ($res->error) {
                    $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
                } else {
                    $data[$this->_table_left] = 1;
                    $data[$this->_table_right] = 2;
                    $data[$this->_table_level] = 0;
                    $data[$this->_table_parent] = 0;

                    $res = $this->dataPoint->Insert($this->_table, $data, $this->_table_id);
                    if ($res->insertid == -1) {
                        $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
                    } else {
                        return $res->insertid;
                    }
                }
            }
            return $return;
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return false;
        }
    }

    /**
     * Обновляет данные
     *
     * @param int $id
     * @param array $data
     * @return QueryInfo|null
     */
    public function Update($id, $data)
    {
        try {
            unset($data['nflag']);
            return $this->dataPoint->Update($this->_table, $data, $this->_table_id.'='.$id);
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), 'update sql', $e->getMessage());
            return null;
        }
    }

    /**
     * Receives left, right and level for unit with number id.
     *
     * @param integer $section_id Unique section id
     * @param integer $cache Recordset is cached for $cache microseconds
     * @return array|null - left, right, level
     */
    private function GetNodeInfo($section_id)
    {
        $sql = 'SELECT * FROM ' . $this->_table . ' WHERE ' . $this->_table_id . ' = ' . (int)$section_id;

        try {
            $res = $this->dataPoint->Query($sql);
            if ($res->Count() == 0) {
                $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, 'no_element_in_tree');
                return null;
            }
            $data = $res->Read();
            return array($data->{$this->_table_left}, $data->{$this->_table_right}, $data->{$this->_table_level}, $data->{$this->_table_parent});
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return null;
        }
    }

    /**
     * Возвращает данные по ноде
     *
     * @param boolean $section_id
     * @param boolean $returnAsReader
     * @param string $criteria
     * @return mixed
     */
    public function GetNode($section_id = false, $returnAsReader = false, $criteria = '')
    {
        if ($section_id !== false) {
            $sql = 'SELECT * FROM '.$this->_table.' WHERE '.$this->_table_id.'=\''.$section_id.'\'';
        } else {
            $sql = 'SELECT * FROM '.$this->_table.' WHERE '.$criteria;
        }

        try {
            $query = $this->dataPoint->Query($sql);
            if ($query->Count() == 1) {
                return $returnAsReader ? $query : $query->Read();
            } else {
                $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, 'no rows found, or results is to many');
            }
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return false;
        }
    }

    /**
     * Возвращает данные корневой ноды
     *
     * @param boolean $returnAsReader
     * @return mixed
     */
    public function GetRootNode($returnAsReader = false)
    {
        try {
            $sql = 'SELECT * FROM '.$this->_table.' WHERE '.$this->_table_level.'=\'0\'';
            $query = $this->dataPoint->Query($sql);
            if ($query->Count() == 0) {
                $this->Clear();
                $query = $this->dataPoint->Query($sql);
            }

            if ($query->Count() == 1) {
                return $returnAsReader ? $query : $query->Read();
            } else {
                $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, 'no rows found, or results is to many');
            }
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return null;
        }
    }

    /**
     * Receives parent left, right and level for unit with number $id.
     *
     * @param integer $section_id
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @return \stdClass|null
     */
    public function GetParentInfo($section_id, $condition = '')
    {
        $node_info = $this->GetNodeInfo($section_id);
        if (!$node_info) {
            return null;
        }

        list($leftId, $rightId, $level) = $node_info;
        $level--;
        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition);
        }

        $sql = 'SELECT * FROM ' . $this->_table
                . ' WHERE ' . $this->_table_left . ' < ' . $leftId
                . ' AND ' . $this->_table_right . ' > ' . $rightId
                . ' AND ' . $this->_table_level . ' = ' . $level
                . $condition
                . ' ORDER BY ' . $this->_table_left;

        try {
            $res = $this->dataPoint->Query($sql);
            return $res->Read();
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return null;
        }
    }

    /**
     * Add a new element in the tree to element with number $section_id.
     *
     * @param integer $section_id Number of a parental element
     * @param array $data Contains parameters for additional fields of a tree (if is): array('filed name' => 'importance', etc)
     * @return integer|null Inserted element id
     */
    public function Insert($section_id, $data = array())
    {
        $node_info = $this->GetNodeInfo($section_id);
        if (!$node_info) {
            return null;
        }

        list(, $rightId, $level) = $node_info;

        $data[$this->_table_left] = $rightId;
        $data[$this->_table_right] = ($rightId + 1);
        $data[$this->_table_level] = ($level + 1);
        $data[$this->_table_parent] = $section_id;

        $this->dataPoint->Query('BEGIN', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);

        $sql = 'UPDATE ' . $this->_table . ' SET '
                . $this->_table_left . '=CASE WHEN ' . $this->_table_left . '>' . $rightId . ' THEN ' . $this->_table_left . '+2 ELSE ' . $this->_table_left . ' END, '
                . $this->_table_right . '=CASE WHEN ' . $this->_table_right . '>=' . $rightId . ' THEN ' . $this->_table_right . '+2 ELSE ' . $this->_table_right . ' END '
                . 'WHERE ' . $this->_table_right . '>=' . $rightId;

        $return = false;

        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        } else {
            $res = $this->dataPoint->Insert($this->_table, $data, $this->_table_id);
            if ($res->insertid == -1) {
                $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, 'insert node sql', $res->error);
                $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            } else {
                $this->dataPoint->Query('COMMIT', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
                $return = $res->insertid;
            }
        }

        return $return;
    }

    /**
     * Add a new element in the tree near element with number id.
     *
     * @param integer $id Number of a parental element
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @param array $data Contains parameters for additional fields of a tree (if is): array('filed name' => 'importance', etc)
     * @return integer|null Inserted element id
     */
    public function InsertNear($id, $condition = '', $data = array())
    {
        $node_info = $this->GetNodeInfo($id);
        if (!$node_info) {
            return null;
        }

        list(, $rightId, $level, $parent) = $node_info;

        $data[$this->_table_left] = ($rightId + 1);
        $data[$this->_table_right] = ($rightId + 2);
        $data[$this->_table_level] = ($level);
        $data[$this->_table_parent] = $parent;

        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition);
        }

        $this->dataPoint->Query('BEGIN', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);

        $sql = 'UPDATE ' . $this->_table . ' SET '
                . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' > ' . $rightId . ' THEN ' . $this->_table_left . ' + 2 ELSE ' . $this->_table_left . ' END, '
                . $this->_table_right . ' = CASE WHEN ' . $this->_table_right . '> ' . $rightId . ' THEN ' . $this->_table_right . ' + 2 ELSE ' . $this->_table_right . ' END, '
                . 'WHERE ' . $this->_table_right . ' > ' . $rightId;
        $sql .= $condition;

        $return = false;
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        } else {
            $res = $this->dataPoint->Insert($this->_table, $data, $this->_table_id);
            if ($res->affected == 0) {
                $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
                $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            } else {
                $this->dataPoint->Query('COMMIT', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
                $return = $res->insertid;
            }
        }
        return $return;
    }

    /**
     * Assigns a node with all its children to another parent.
     *
     * @param integer $id node ID
     * @param integer $newParentId ID of new parent node
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @return bool TRUE if successful, FALSE otherwise.
     */
    public function MoveAll($id, $newParentId)
    {
        $node_info = $this->GetNodeInfo($id);
        if (!$node_info) {
            return false;
        }

        list($leftId, $rightId, $level) = $node_info;
        $node_info = $this->GetNodeInfo($newParentId);
        if (!$node_info) {
            return false;
        }

        list($leftIdP, $rightIdP, $levelP) = $node_info;
        if ($id == $newParentId || $leftId == $leftIdP || ($leftIdP >= $leftId && $leftIdP <= $rightId) || ($level == $levelP+1 && $leftId > $leftIdP && $rightId < $rightIdP)) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, 'moving sql', 'cant_move_tree');
            return false;
        }

        $this->dataPoint->Query('BEGIN', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);

        if ($leftIdP < $leftId && $rightIdP > $rightId && $levelP < $level - 1) {
            $sql = 'UPDATE ' . $this->_table . ' SET '
                    . $this->_table_parent . ' = '.$newParentId.', '
                    . $this->_table_level . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_level.sprintf('%+d', -($level-1)+$levelP) . ' ELSE ' . $this->_table_level . ' END, '
                    . $this->_table_right . ' = CASE WHEN ' . $this->_table_right . ' BETWEEN ' . ($rightId+1) . ' AND ' . ($rightIdP-1) . ' THEN ' . $this->_table_right . '-' . ($rightId-$leftId+1) . ' '
                    . 'WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_right . '+' . ((($rightIdP-$rightId-$level+$levelP)/2)*2+$level-$levelP-1) . ' ELSE ' . $this->_table_right . ' END, '
                    . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . ($rightId+1) . ' AND ' . ($rightIdP-1) . ' THEN ' . $this->_table_left . '-' . ($rightId-$leftId+1) . ' '
                    . 'WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_left . '+' . ((($rightIdP-$rightId-$level+$levelP)/2)*2+$level-$levelP-1) . ' ELSE ' . $this->_table_left . ' END '
                    . 'WHERE ' . $this->_table_left . ' BETWEEN ' . ($leftIdP+1) . ' AND ' . ($rightIdP-1);
        } elseif ($leftIdP < $leftId) {
            $sql = 'UPDATE ' . $this->_table . ' SET '
                    . $this->_table_parent . ' = '.$newParentId.', '
                    . $this->_table_level . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_level.sprintf('%+d', -($level-1)+$levelP) . ' ELSE ' . $this->_table_level . ' END, '
                    . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $rightIdP . ' AND ' . ($leftId-1) . ' THEN ' . $this->_table_left . '+' . ($rightId-$leftId+1) . ' '
                    . 'WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_left . '-' . ($leftId-$rightIdP) . ' ELSE ' . $this->_table_left . ' END, '
                    . $this->_table_right . ' = CASE WHEN ' . $this->_table_right . ' BETWEEN ' . $rightIdP . ' AND ' . $leftId . ' THEN ' . $this->_table_right . '+' . ($rightId-$leftId+1) . ' '
                    . 'WHEN ' . $this->_table_right . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_right . '-' . ($leftId-$rightIdP) . ' ELSE ' . $this->_table_right . ' END '
                    . 'WHERE (' . $this->_table_left . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId. ' '
                    . 'OR ' . $this->_table_right . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ')';
        } else {
            $sql = 'UPDATE ' . $this->_table . ' SET '
                    . $this->_table_level . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_level.sprintf('%+d', -($level-1)+$levelP) . ' ELSE ' . $this->_table_level . ' END, '
                    . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $rightId . ' AND ' . $rightIdP . ' THEN ' . $this->_table_left . '-' . ($rightId-$leftId+1) . ' '
                    . 'WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_left . '+' . ($rightIdP-1-$rightId) . ' ELSE ' . $this->_table_left . ' END, '
                    . $this->_table_right . ' = CASE WHEN ' . $this->_table_right . ' BETWEEN ' . ($rightId+1) . ' AND ' . ($rightIdP-1) . ' THEN ' . $this->_table_right . '-' . ($rightId-$leftId+1) . ' '
                    . 'WHEN ' . $this->_table_right . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_right . '+' . ($rightIdP-1-$rightId) . ' ELSE ' . $this->_table_right . ' END '
                    . 'WHERE (' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ' '
                    . 'OR ' . $this->_table_right . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ')';
        }
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            return false;
        }
        $this->dataPoint->Query('COMMIT', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        return true;
    }

    /**
     * Change items position.
     *
     * @param integer $id1 first item ID
     * @param integer $id2 second item ID
     * @return bool TRUE if successful, FALSE otherwise.
     */
    public function ChangePosition($id1, $id2)
    {
        $node_info = $this->GetNodeInfo($id1);
        if (false === $node_info) {
            return false;
        }
        list($leftId1, $rightId1, $level1, $parent1) = $node_info;
        $node_info = $this->GetNodeInfo($id2);
        if (false === $node_info) {
            return false;
        }
        list($leftId2, $rightId2, $level2, $parent2) = $node_info;

        $this->dataPoint->Query('BEGIN', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);

        $sql = 'UPDATE ' . $this->_table . ' SET '
                . $this->_table_parent . ' = ' . $parent2 .', '
                . $this->_table_left . ' = ' . $leftId2 .', '
                . $this->_table_right . ' = ' . $rightId2 .', '
                . $this->_table_level . ' = ' . $level2 .' '
                . 'WHERE ' . $this->_table_id . ' = ' . (int)$id1;
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            return false;
        }

        $sql = 'UPDATE ' . $this->_table . ' SET '
                . $this->_table_parent . ' = ' . $parent1 .', '
                . $this->_table_left . ' = ' . $leftId1 .', '
                . $this->_table_right . ' = ' . $rightId1 .', '
                . $this->_table_level . ' = ' . $level1 .' '
                . 'WHERE ' . $this->_table_id . ' = ' . (int)$id2;
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            return false;
        }
        $this->dataPoint->Query('COMMIT', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        return true;
    }

    /**
     * Swapping nodes within the same level and limits of one parent with all its children: $id1 placed before or after $id2.
     *
     * @param integer $id1 first item ID
     * @param integer $id2 second item ID
     * @param string $position 'before' or 'after' $id2
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @return bool TRUE if successful, FALSE otherwise.
     */
    public function ChangePositionAll($id1, $id2, $position = 'after', $condition = '')
    {
        $node_info = $this->GetNodeInfo($id1);
        if (!$node_info) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, '', 'cant_change_position');
            return false;
        }
        list($leftId1, $rightId1, $level1) = $node_info;
        $node_info = $this->GetNodeInfo($id2);
        if (!$node_info) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, '', 'cant_change_position');
            return false;
        }
        list($leftId2, $rightId2, $level2) = $node_info;
        if ($level1 <> $level2) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, '', 'cant_change_position');
            return false;
        }
        if ('before' == $position) {
            if ($leftId1 > $leftId2) {
                $sql = 'UPDATE ' . $this->_table . ' SET '
                        . $this->_table_right . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_table_right . ' - ' . ($leftId1 - $leftId2) . ' '
                        . 'WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_table_right . ' +  ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_table_right . ' END, '
                        . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_table_left . ' - ' . ($leftId1 - $leftId2) . ' '
                        . 'WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_table_left . ' + ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_table_left . ' END '
                        . 'WHERE ' . $this->_table_left . ' BETWEEN ' . $leftId2 . ' AND ' . $rightId1;
            } else {
                $sql = 'UPDATE ' . $this->_table . ' SET '
                        . $this->_table_right . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_table_right . ' + ' . (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) . ' '
                        . 'WHEN ' . $this->_table_left . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->_table_right . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_table_right . ' END, '
                        . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_table_left . ' + ' . (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) . ' '
                        . 'WHEN ' . $this->_table_left . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->_table_left . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_table_left . ' END '
                        . 'WHERE ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . ($leftId2 - 1);
            }
        }
        if ('after' == $position) {
            if ($leftId1 > $leftId2) {
                $sql = 'UPDATE ' . $this->_table . ' SET '
                        . $this->_table_right . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_table_right . ' - ' . ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) . ' '
                        . 'WHEN ' . $this->_table_left . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_table_right . ' +  ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_table_right . ' END, '
                        . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_table_left . ' - ' . ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) . ' '
                        . 'WHEN ' . $this->_table_left . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_table_left . ' + ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_table_left . ' END '
                        . 'WHERE ' . $this->_table_left . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . $rightId1;
            } else {
                $sql = 'UPDATE ' . $this->_table . ' SET '
                        . $this->_table_right . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_table_right . ' + ' . ($rightId2 - $rightId1) . ' '
                        . 'WHEN ' . $this->_table_left . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . $rightId2 . ' THEN ' . $this->_table_right . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_table_right . ' END, '
                        . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_table_left . ' + ' . ($rightId2 - $rightId1) . ' '
                        . 'WHEN ' . $this->_table_left . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . $rightId2 . ' THEN ' . $this->_table_left . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_table_left . ' END '
                        . 'WHERE ' . $this->_table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId2;
            }
        }
        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition);
        }
        $sql .= $condition;

        $this->dataPoint->Query('BEGIN', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            return false;
        }
        $this->dataPoint->Query('COMMIT', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        return true;
    }

    /**
     * Delete element with number $id from the tree wihtout deleting it's children.
     *
     * @param integer $id Number of element
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @return bool TRUE if successful, FALSE otherwise.
     */
    public function Delete($id, $condition = '')
    {
        $node_info = $this->GetNodeInfo($id);
        if (!$node_info) {
            return false;
        }

        list($leftId, $rightId) = $node_info;
        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition);
        }

        $this->dataPoint->Query('BEGIN', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);

        $sql = 'DELETE FROM ' . $this->_table . ' WHERE ' . $this->_table_id . ' = ' . (int)$id;
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            return false;
        }

        $sql = 'UPDATE ' . $this->_table . ' SET '
                . $this->_table_level . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_level . ' - 1 ELSE ' . $this->_table_level . ' END, '
                . $this->_table_right . ' = CASE WHEN ' . $this->_table_right . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_right . ' - 1 '
                . 'WHEN ' . $this->_table_right . ' > ' . $rightId . ' THEN ' . $this->_table_right . ' - 2 ELSE ' . $this->_table_right . ' END, '
                . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_table_left . ' - 1 '
                . 'WHEN ' . $this->_table_left . ' > ' . $rightId . ' THEN ' . $this->_table_left . ' - 2 ELSE ' . $this->_table_left . ' END '
                . 'WHERE ' . $this->_table_right . ' > ' . $leftId;
        $sql .= $condition;
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            return false;
        }
        $this->dataPoint->Query('COMMIT', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        return true;
    }

    /**
     * Delete element with number $id from the tree and all it childret.
     *
     * @param integer $id Number of element
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @return bool TRUE if successful, FALSE otherwise.
     */
    public function DeleteAll($id, $condition = '')
    {
        $node_info = $this->GetNodeInfo($id);
        if (!$node_info) {
            return false;
        }

        list($leftId, $rightId) = $node_info;
        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition);
        }

        $this->dataPoint->Query('BEGIN', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);

        $sql = 'DELETE FROM ' . $this->_table . ' WHERE ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId;
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            return false;
        }
        $deltaId = (($rightId - $leftId) + 1);
        $sql = 'UPDATE ' . $this->_table . ' SET '
                . $this->_table_left . ' = CASE WHEN ' . $this->_table_left . ' > ' . $leftId.' THEN ' . $this->_table_left . ' - ' . $deltaId . ' ELSE ' . $this->_table_left . ' END, '
                . $this->_table_right . ' = CASE WHEN ' . $this->_table_right . ' > ' . $leftId . ' THEN ' . $this->_table_right . ' - ' . $deltaId . ' ELSE ' . $this->_table_right . ' END '
                . 'WHERE ' . $this->_table_right . ' > ' . $rightId;
        $sql .= $condition;
        $res = $this->dataPoint->Query($sql, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->affected == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, $res->error);
            $this->dataPoint->Query('ROLLBACK', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
            return false;
        }
        $this->dataPoint->Query('COMMIT', (object)['type' => DataAccessPoint::QueryTypeNonInfo]);
        return true;
    }

    /**
     * Counts element with number $id from the tree and all it childret.
     *
     * @param integer $id Number of element
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @return bool TRUE if successful, FALSE otherwise.
     */
    public function CountAll($id, $condition = '')
    {
        $node_info = $this->GetNodeInfo($id);
        if (!$node_info) {
            return false;
        }

        list($leftId, $rightId) = $node_info;
        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition);
        }

        $sql = 'SELECT count(*) as c FROM ' . $this->_table . ' WHERE ' . $this->_table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId;
        try {
            $res = $this->dataPoint->Query($sql);
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return false;
        }

        $r = $res->Read();
        return $r->c;
    }

    /**
     * Returns all elements of the tree sortet by left.
     *
     * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @param string $joinWith Join string
     * @param int $page Page
     * @param int $pagesize Pagesize
     * @return IDataReader|null needed fields
     */
    public function Full($fields, $condition = '', $joinWith = '', $page = -1, $pagesize = 10)
    {
        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition, true);
        }

        if (!VariableHelper::IsEmpty($joinWith)) {
            $joinWith = $this->_PrepareJoin($joinWith);
        }

        if (VariableHelper::IsArray($fields)) {
            $fields = implode(', ', $fields);
        } else {
            $fields = '*';
        }

        $sql = 'SELECT ' . $fields . ' FROM ' . $this->_table.' '.$joinWith;
        $sql .= $condition;
        $sql .= ' ORDER BY ' . $this->_table_left;

        try {
            $res = $this->dataPoint->Query($sql, (object)['page' => $page, 'pagesize' => $pagesize]);
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return null;
        }

        return $res;
    }

    /**
     * Gets a position number
     *
     * @param int $id
     * @param string $condition
     * @return int
     */
    public function GetPositionNumber($id, $condition = '')
    {
        $node = $this->GetNodeInfo($id);
        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition, false);
        }

        $sql = 'select count(*) as c from '.$this->_table.' where '.$this->_table_left.' < '.$node[0].$condition.' order by '.$this->_table_left;
        $r = $this->dataPoint->Query($sql);
        $rr = $r->Read();
        return $rr->c;
    }

    /**
     * Returns all elements of a branch starting from an element with number $id.
     *
     * @param integer $id Node unique id
     * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @param array $joinWith Array structure: array('outer' => array('table' => 'condition'), 'inner' => array('table', 'condition')), etc where array key - join type (inner, outer, cross), condition - condition string
     * @param int $page Page
     * @param int $pagesize Pagesize
     * @return IDataReader|null
     */
    public function Branch($id, $fields = '', $condition = '', $joinWith = '', $page = -1, $pagesize = 10)
    {
        if (VariableHelper::IsArray($fields)) {
            $fields[] = "*";
            $fields = 'A.' . implode(', A.', $fields);

            $fields = str_replace('A.(', '(', $fields);
            $fields = str_replace('A.exists(', 'exists(', $fields);
            $fields = str_replace('A.count(', 'count(', $fields);
            $fields = str_replace('A.concat(', 'concat(', $fields);
            $fields = str_replace('A. concat(', 'concat(', $fields);
            $fields = str_replace('A. (', '(', $fields);
        } else {
            $fields = 'A.*';
        }


        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition, false, 'A.');
        }


        // removes a 0 leveled row
        $condition .= ' and A.'.$this->_table_level.' > 0';

        if (!VariableHelper::IsEmpty($joinWith)) {
            $joinWith = $this->_PrepareJoin($joinWith, 'A.');
        }


        $sql = 'SELECT ' . $fields . ', CASE WHEN A.' . $this->_table_left . ' + 1 < A.' . $this->_table_right . ' THEN 1 ELSE 0 END AS nflag FROM ' . $this->_table . ' A '.$joinWith.', ' . $this->_table . ' B WHERE B.' . $this->_table_id . ' = ' . (int)$id . ' AND A.' . $this->_table_left . ' >= B.' . $this->_table_left . ' AND A.' . $this->_table_right . ' <= B.' . $this->_table_right;
        $sql .= $condition;
        $sql .= ' ORDER BY A.' . $this->_table_left;

        try {
            $res = $this->dataPoint->Query($sql, (object)['page' => $page, 'pagesize' => $pagesize]);
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return null;
        }

        return $res;
    }

    /**
     * Returns all parents of element with number $id.
     *
     * @param integer $id Node unique id
     * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @param string $joinWith Join string
     * @return IDataReader|null
     */
    public function Parents($id, $fields = '', $condition = '', $joinWith = '')
    {
        if (VariableHelper::IsArray($fields)) {
            $fields = 'A.' . implode(', A.', $fields);

            $fields = str_replace('A.(', '(', $fields);
            $fields = str_replace('A.exists(', 'exists(', $fields);
            $fields = str_replace('A.count(', 'count(', $fields);
            $fields = str_replace('A.concat(', 'concat(', $fields);
            $fields = str_replace('A. concat(', 'concat(', $fields);
            $fields = str_replace('A. (', '(', $fields);
        } else {
            $fields = 'A.*';
        }

        if (!VariableHelper::IsEmpty($condition)) {
            $condition = $this->_PrepareCondition($condition, false, 'A.');
        }
        if (!VariableHelper::IsEmpty($joinWith)) {
            $joinWith = $this->_PrepareJoin($joinWith, 'A.');
        }

        $sql = 'SELECT ' . $fields . ', CASE WHEN A.' . $this->_table_left . ' + 1 < A.' . $this->_table_right . ' THEN 1 ELSE 0 END AS nflag FROM ' . $this->_table . ' A '.$joinWith.', ' . $this->_table . ' B WHERE B.' . $this->_table_id . ' = ' . (int)$id . ' AND B.' . $this->_table_left . ' BETWEEN A.' . $this->_table_left . ' AND A.' . $this->_table_right;
        $sql .= $condition;
        $sql .= ' ORDER BY A.' . $this->_table_left;

        try {
            $res = $this->dataPoint->Query($sql);
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return null;
        }

        return $res;
    }

    /**
     * Returns a slightly opened tree from an element with number $id.
     *
     * @param integer $id Node unique id
     * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
     * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
     * @param int $page Page
     * @param int $pagesize Pagesize
     * @return IDataReader|null
     */
    public function Ajar($id, $fields = '', $condition = '', $page = -1, $pagesize = 10)
    {
        if (VariableHelper::IsArray($fields)) {
            $fields = 'A.' . implode(', A.', $fields);

            $fields = str_replace('A.(', '(', $fields);
            $fields = str_replace('A.exists(', 'exists(', $fields);
            $fields = str_replace('A.count(', 'count(', $fields);
            $fields = str_replace('A.concat(', 'concat(', $fields);
            $fields = str_replace('A. concat(', 'concat(', $fields);
            $fields = str_replace('A. (', '(', $fields);
        } else {
            $fields = 'A.*';
        }

        $condition1 = '';
        if (!VariableHelper::IsEmpty($condition)) {
            $condition1 = $this->_PrepareCondition($condition, false, 'B.');
        }

        $sql = 'SELECT A.' . $this->_table_left . ', A.' . $this->_table_right . ', A.' . $this->_table_level . ' FROM ' . $this->_table . ' A, ' . $this->_table . ' B '
                . 'WHERE B.' . $this->_table_id . ' = ' . (int)$id . ' AND B.' . $this->_table_left . ' BETWEEN A.' . $this->_table_left . ' AND A.' . $this->_table_right;
        $sql .= $condition1;
        $sql .= ' ORDER BY A.' . $this->_table_left;

        try {
            $res = $this->dataPoint->Query($sql);
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return null;
        }

        if ($res->Count() == 0) {
            $this->_setError(__FILE__, __CLASS__, __FUNCTION__, __LINE__, $sql, 'no_element_in_tree');
            return null;
        }

        $alen = $res->Count();
        $i = 0;
        if (VariableHelper::IsArray($fields)) {
            $fields = implode(', ', $fields);
        } else {
            $fields = '*';
        }

        if (!VariableHelper::IsEmpty($condition)) {
            $condition1 = $this->_PrepareCondition($condition, false);
        }

        $sql = 'SELECT ' . $fields . ' FROM ' . $this->_table . ' A WHERE (' . $this->_table_level . ' = 1';
        while ($row = $res->Read()) {
            if ((++$i == $alen) && ($row->{$this->_table_left} + 1) == $row->{$this->_table_right}) {
                break;
            }
            $sql .= ' OR (' . $this->_table_level . ' = ' . ($row->{$this->_table_level} + 1)
                    . ' AND ' . $this->_table_left . ' > ' . $row->{$this->_table_left}
                    . ' AND ' . $this->_table_right . ' < ' . $row->{$this->_table_right} . ')';
        }
        $sql .= ') ' . $condition1;
        $sql .= ' ORDER BY ' . $this->_table_left;


        try {
            $res = $this->dataPoint->Query($sql, (object)['page' => $page, 'pagesize' => $pagesize]);
        } catch (DataAccessPointsException $e) {
            $this->_setError($e->getFile(), __CLASS__, __FUNCTION__, $e->getLine(), $sql, $e->getMessage());
            return null;
        }

        return $res;
    }

    /**
     * Transform array with conditions to SQL query
     * Array structure:
     * array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc
     * where array key - condition (AND, OR, etc), value - condition string.
     *
     * @param array|string $condition
     * @param bool $where - True - yes, flase - not
     * @param string $prefix
     * @return string
     */
    private function _PrepareCondition($condition, $where = false, $prefix = '')
    {
        if (!is_array($condition)) {
            return $condition;
        }
        $sql = ' ';
        if (true === $where) {
            $sql .= 'WHERE ' . $prefix;
        }
        $keys = array_keys($condition);
        for ($i = 0;$i < count($keys);$i++) {
            if (false === $where || (true === $where && $i > 0)) {
                $sql .= ' ' . strtoupper($keys[$i]) . ' ' . $prefix;
            }
            $sql .= implode(' ' . strtoupper($keys[$i]) . ' ' . $prefix, $condition[$keys[$i]]);
        }

        $sql = str_replace($prefix.'(', '(', $sql);
        $sql = str_replace($prefix.'exists(', 'exists(', $sql);
        $sql = str_replace($prefix.'count(', 'count(', $sql);
        $sql = str_replace('A.concat(', 'concat(', $sql);
        $sql = str_replace('A. concat(', 'concat(', $sql);
        return str_replace('A. (', '(', $sql);
    }

    /**
     * Transform array with conditions to SQL query
     * Array structure:
     * array $joinWith Array structure: array('outer' => array('table' => array('fieldfrom', 'fieldto')), 'inner' => array('table' => array('fieldfrom', 'fieldto')), etc where array key - join type (inner, outer, cross), condition - condition string
     * where array key - condition (AND, OR, etc), value - condition string.
     *
     * @param array|string $joinWith
     * @param string $prefix
     * @return string
     */
    private function _PrepareJoin($joinWith, $prefix = '')
    {
        if (!is_array($joinWith)) {
            return $joinWith;
        }
        $sql = ' ';
        foreach ($joinWith as $joinType => $joinConditions) {
            foreach ($joinConditions as $joinTable => $joinFields) {
                $sql .= ' '.strtoupper($joinType).' JOIN '.$joinTable.' ON '.$joinFields[0].' = '.$prefix.$joinFields[1];
            }
        }
        return $sql;
    }
}
