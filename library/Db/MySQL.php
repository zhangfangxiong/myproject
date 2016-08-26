<?php

/**
 * 数据操作类
 *
 * @author Jack Xie <xiejinci@gmail.com>
 * @copyright 2006-2010 1lele.com
 * $Id: Db.php 8 2010-03-18 08:34:29Z xiejc $
 * @history
 */
class Db_MySQL
{

    private $sDbName;

    /**
     * 事务计数
     * 
     * @var int
     */
    private $iTransaction = 0;

    /**
     * 存放当前连接
     * 
     * @var object
     */
    private $oDbh = null;

    private $bUseCommit = false;

    /**
     * 执行sql数组
     */
    private $aSQL = array();
    
    private $iPingTime = 0;
    
    /**
     * 构造函数
     * 
     * @param array $sDbName            
     * @param array $is_static            
     * @param bool $bIsPersistent            
     * @return void
     */
    public function __construct ($sDbName)
    {
        $this->iPingTime = time();
        $this->sDbName = $sDbName;
        $this->connect();
    }

    public function connect ()
    {
        $this->oDbh = Util_Common::getPdoDb($this->sDbName);
    }

    /**
     * 释放数据库连接
     * 
     * @return void
     */
    public function close ()
    {
    }

    /**
     * 查询操作的底层接口
     * 
     * @param string $sql
     *            要执行查询的SQL语句
     * @return Object
     */
    public function execute ($sql)
    {
        if (time() - $this->iPingTime > 300) {
            $this->close();
            $this->connect();
            $this->iPingTime = time();
        }
        
        $res = $this->oDbh->query($sql);
        if ($res === false) {
            throw new Exception($sql);
            // echo $sql;exit;
        }
        return $res;
    }

    /**
     * 自动执行操作(针对Insert/Update操作)
     * 
     * @param string $sql            
     * @return int 影响的行数
     */
    public function query ($sql)
    {
        $res = $this->execute($sql);
        return $res->rowCount();
    }

    /**
     * 取得所有数据
     * 
     * @param string $sql
     *            SQL语句
     * @param string $field
     *            以字段做为数组的key
     * @return array
     */
    public function getAll ($sql, $field = null)
    {
        $res = $this->execute($sql);
        if (! $res) {
            return array();
        }
        
        $rows = $res->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return array();
        }
        
        if (null != $field) {
            $list = $rows;
            $rows = array();
            foreach ($list as $row) {
                $rows[$row[$field]] = $row;
            }
        }
        
        return $rows;
    }

    /**
     * 以get_row方式取得所有数据
     * 
     * @param string $sql
     *            SQL语句
     * @param int $index
     *            以字段做为数组的key
     * @return array
     */
    public function getAllByRow ($sql, $index = -1)
    {
        $res = $this->execute($sql);
        if (! $res) {
            return array();
        }
        
        $rows = $res->fetchAll(PDO::FETCH_NUM);
        if (empty($rows)) {
            return array();
        }
        
        if (-1 != $index) {
            $list = $rows;
            $rows = array();
            foreach ($list as $row) {
                $rows[$row[$index]] = $row;
            }
        }
        
        return $rows;
    }

    /**
     * 取得指定条数的数据
     * 
     * @param string $sql
     *            SQL语句
     * @param int $offset
     *            LIMIT的第一个参数
     * @param int $limit
     *            LIMIT的第二个参数
     * @param string $field
     *            以字段做为数组的key
     * @return array
     */
    public function getLimit ($sql, $offset, $limit, $field = null)
    {
        $limit = intval($limit);
        if ($limit <= 0) {
            return array();
        }
        $offset = intval($offset);
        if ($offset < 0) {
            return array();
        }
        $sql = $sql . ' LIMIT ' . $limit;
        if ($offset > 0) {
            $sql .= ' OFFSET ' . $offset;
        }
        return $this->getAll($sql, $field);
    }

    /**
     * 返回所有记录中以第一个字段为值的数组
     * 
     * @param string $sql
     *            SQL语句
     * @param bool $isMaster
     *            主从
     * @return array
     */
    public function getCol ($sql)
    {
        $list = $this->getAllByRow($sql);
        $rows = array();
        foreach ($list as $row) {
           $rows[] = $row[0]; 
        }
        
        return $rows;
    }

    /**
     * 返回所有记录中以第一个字段为key,第二个字段为值的数组
     * 
     * @param string $sql
     *            SQL语句
     * @return array
     */
    public function getPair ($sql)
    {
        $list = $this->getAllByRow($sql);
        $rows = array();
        foreach ($list as $row) {
           $rows[$row[0]] = $row[1]; 
        }
        
        return $rows;
    }

    /**
     * 取得第一条记录
     * 
     * @param string $sql
     *            SQL语句
     * @return array
     */
    public function getRow ($sql)
    {
        $res = $this->execute($sql);
        if (! $res) {
            return array();
        }
        
        $row = $res->fetch(PDO::FETCH_ASSOC);
        if (empty($row)) {
            return array();
        }
        
        return $row;
    }

    /**
     * 取得第一条记录的第一个字段值
     * 
     * @param string $sql
     *            SQL语句
     * @return int string
     */
    public function getOne ($sql)
    {
        $res = $this->execute($sql);
        if (! $res) {
            return null;
        }
        
        $one = $res->fetchColumn(0);
        if (empty($one)) {
            return null;
        }
        
        return $one;
    }

    /**
     * 替换操作
     * @param unknown $table
     * @param unknown $row
     * @param string $quote
     * @return number
     */
    public function replace ($table, $row, $quote = false)
    {
        return $this->insert($table, $row, $quote, 'REPLACE');
    }

    /**
     * 插入一条记录
     * 
     * @param string $table 表数
     * @param array $row 数据
     * @param bool $quote 是否进行数据过滤
     * @return int 影响的条数
     */
    public function insert ($table, $row, $quote = false, $type = 'INSERT')
    {
        $cols = array();
        $vals = array();
        if (false == $quote) {
            foreach ($row as $col => $val) {
                $cols[] = $col;
                $vals[] = $val;
            }
        } else {
            foreach ($row as $col => $val) {
                $cols[] = $col;
                $vals[] = $this->quote($val);
            }
        }
        $sql = $type . ' INTO `' . $table . '`' . '(`' . join('`, `', $cols) . '`) ' . 'VALUES (\'' . join('\',\'', $vals) . '\')';
        return $this->query($sql);
    }

    /**
     * 插入一批数据库
     * 
     * @param string $table
     *            表名
     * @param array $rows
     *            数据列表 array( array( 'field1'=>$val1, 'field2'=>$val2, ... ), array( 'field1'=>$val1, 'field2'=>$val2, ...), ... )
     * @param string $type
     *            插入类型(INSERT|REPLACE)
     * @param bool $quote
     *            是否进行数据过滤
     * @param bool $return_sql
     *            如果启用，则无数据库操作，仅返回SQL字符串。
     * @return int 影响的条数
     */
    public function insertRows ($table, $rows, $type = 'INSERT', $quote = false)
    {
        if (empty($rows)) {
            return true;
        }
        $cols = array();
        $vals = array();
        foreach ($rows as $n => $row) {
            $arr = array();
            if (false == $quote) {
                foreach ($row as $col => $val) {
                    if (0 == $n) {
                        $cols[] = $col;
                    }
                    $arr[] = $val;
                }
            } else {
                foreach ($row as $col => $val) {
                    if (0 == $n) {
                        $cols[] = $col;
                    }
                    $arr[] = $this->quote($val);
                }
            }
            $vals[] = '(\'' . join('\', \'', $arr) . '\')';
        }
        $sql = $type . ' INTO `' . $table . '`(`' . join('`, `', $cols) . '`) VALUES' . join(', ', $vals);
        return $this->query($sql);
    }

    /**
     * 数据更新
     * 
     * @param string $table
     *            表名
     * @param array $data
     *            记录
     * @param string $where
     *            更新条件
     * @param bool $quote
     *            是否进行过滤
     * @return int 影响的条数
     */
    public function update ($table, $data, $where = '', $quote = false)
    {
        $sets = array();
        if (false == $quote) {
            foreach ($data as $col => $val) {
                $sets[] = '`' . $col . '` = \'' . $val . '\'';
            }
        } else {
            foreach ($data as $col => $val) {
                $sets[] = '`' . $col . '` = \'' . $this->quote($val) . '\'';
            }
        }
        
        $sql = 'UPDATE `' . $table . '`' . ' SET ' . implode(', ', $sets) . (($where) ? ' WHERE ' . $where : '');
        return $this->query($sql);
    }

    /**
     * 删除数据
     * 
     * @param string $table
     *            表名
     * @param string $where
     *            条件
     * @return int
     */
    public function delete ($table, $where)
    {
        return $this->query('DELETE FROM ' . $table . ' WHERE ' . $where);
    }

    /**
     * 取得最后的lastInsertId
     * 
     * @return int
     */
    public function lastInsertId ()
    {
        return $this->oDbh->lastInsertId();
    }

    /**
     * 数据过滤
     * 
     * @param mixed $value
     *            要过滤的值
     * @return string
     */
    public function quote ($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return addcslashes($value, "\000\n\r\\'\"\032");
    }

    /**
     * 事务开始
     * 
     * @param bool $no_check            
     * @return bool
     */
    public function begin ()
    {
        if ($this->iTransaction == 0) {
            if ($this->bUseCommit) {
                throw new Exception('本次操作里已经使用了一次事务。', 3);
            }
            $this->execute('BEGIN');
            $this->bUseCommit = true;
        }
        $this->iTransaction ++;
        return true;
    }

    /**
     * 事务提交
     */
    public function commit ()
    {
        if ($this->iTransaction < 1) {
            throw new Exception('出错啦！事务不配对！', 3);
        }
        $this->iTransaction --;
        if (0 == $this->iTransaction) {
            $this->execute('COMMIT');
        }
        return true;
    }

    /**
     * 事务回滚
     */
    public function rollBack ()
    {
        $this->execute('ROLLBACK');
        $this->iTransaction = 0;
        $this->bUseCommit = false;
        return true;
    }

    public function __destruct ()
    {
        $this->close();
    }
}