<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace Wwws3\Kingbase\builder;

use PDO;
use think\db\Builder;
use think\db\Connection;
use think\db\exception\DbException as Exception;
use think\db\Expression;
use think\db\Query;
use think\db\Raw;

/**
 * KingBase数据库驱动
 */
class KingBase extends Builder
{

    // 查询表达式映射
    protected $exp = ['EQ' => '=', 'NEQ' => '<>', 'GT' => '>', 'EGT' => '>=', 'LT' => '<', 'ELT' => '<=', 'NOTLIKE' => 'NOT LIKE', 'NOTIN' => 'NOT IN', 'NOTBETWEEN' => 'NOT BETWEEN', 'NOTEXISTS' => 'NOT EXISTS', 'NOTNULL' => 'NOT NULL', 'NOTBETWEEN TIME' => 'NOT BETWEEN TIME'];

    // 查询表达式解析
    protected $parser = [
        'parseCompare'     => ['=', '<>', '>', '>=', '<', '<='],
        'parseLike'        => ['LIKE', 'NOT LIKE'],
        'parseBetween'     => ['NOT BETWEEN', 'BETWEEN'],
        'parseIn'          => ['NOT IN', 'IN'],
        'parseExp'         => ['EXP'],
        'parseNull'        => ['NOT NULL', 'NULL'],
        'parseBetweenTime' => ['BETWEEN TIME', 'NOT BETWEEN TIME'],
        'parseTime'        => ['< TIME', '> TIME', '<= TIME', '>= TIME'],
        'parseExists'      => ['NOT EXISTS', 'EXISTS'],
        'parseColumn'      => ['COLUMN'],
    ];

    // SQL表达式
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    protected $insertSql = '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';

    protected $insertAllSql = '%INSERT% INTO %TABLE% (%FIELD%) %DATA% %COMMENT%';

    protected $updateSql = 'UPDATE %TABLE% SET %SET%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    protected $deleteSql = 'DELETE FROM %TABLE%%USING%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';


    /**
     * 字段名分析
     * @access public
     * @param  Query  $query    查询对象
     * @param  mixed  $key      字段名
     * @param  bool   $strict   严格检测
     * @return string
     */
    public function parseKey(Query $query, $key, $strict = false)
    {
        return $key instanceof Expression ? $key->getValue() : $key;
    }


    /**
     * table分析
     * @access protected
     * @param  Query     $query         查询对象
     * @param  mixed     $tables        表名
     * @return string
     */
    protected function parseTable(Query $query, $tables)
    {
        $item    = [];
        $options = $query->getOptions();

        foreach ((array) $tables as $key => $table) {
            if (!is_numeric($key)) {
                $key    = $this->connection->parseSqlTable($key);
                $item[] = $this->parseKey($query, $key) . ' ' . $this->parseKey($query, $table);
            } else {
                $table = $this->connection->parseSqlTable($table);

                if (isset($options['alias'][$table])) {
                    $item[] = $this->parseKey($query, $table) . ' ' . $this->parseKey($query, $options['alias'][$table]);
                } else {
                    $prefix = config('database.database').'.';
                    if(strpos($table,$prefix) === false){
                        $table = $prefix.$options['table'];
                    }
                    $item[] = $this->parseKey($query, $table);
                }
            }
        }

        return implode(',', $item);
    }


    /**
     * 大小比较查询
     * @access protected
     * @param  Query     $query        查询对象
     * @param  string    $key
     * @param  string    $exp
     * @param  mixed     $value
     * @param  string    $field
     * @param  integer   $bindType
     * @return string
     */
    protected function parseCompare(Query $query, $key, $exp, $value, $field, $bindType)
    {
        if (is_array($value)) {
            throw new Exception('where express error:' . $exp . var_export($value, true));
        }

        // 比较运算
        if ($value instanceof \Closure) {
            $value = $this->parseClosure($query, $value);
        }

        if ('=' == $exp && is_null($value)) {
            return $key . ' IS NULL';
        }

        return $key . ' ' . $exp . ' ' . $value;
    }

    /**
     * 时间范围查询
     * @access protected
     * @param  Query     $query        查询对象
     * @param  string    $key
     * @param  string    $exp
     * @param  mixed     $value
     * @param  string    $field
     * @param  integer   $bindType
     * @return string
     */
    protected function parseBetweenTime(Query $query, $key, $exp, $value, $field, $bindType)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return $key . ' ' . substr($exp, 0, -4)
            . $this->parseDateTime($query, $value[0], $field, $bindType)
            . ' AND '
            . $this->parseDateTime($query, $value[1], $field, $bindType);

    }

    /**
     * IN查询
     * @access protected
     * @param  Query     $query        查询对象
     * @param  string    $key
     * @param  string    $exp
     * @param  mixed     $value
     * @param  string    $field
     * @param  integer   $bindType
     * @return string
     */
    protected function parseIn(Query $query, $key, $exp, $value, $field, $bindType)
    {
        // IN 查询
        if ($value instanceof \Closure) {
            $value = $this->parseClosure($query, $value, false);
        } elseif ($value instanceof Expression) {
            $value = $value->getValue();
        } else {
            $value = array_unique(is_array($value) ? $value : explode(',', $value));
            $array = [];

            foreach ($value as $k => $v) {
                $name    = $query->bind($v, $bindType);
                $array[] = ':' . $name;
            }

            if (count($array) == 1) {
                return $key . ('IN' == $exp ? ' = ' : ' <> ') . $array[0];
            } else {
                $zone  = implode(',', $array);
                $value = empty($zone) ? "0" : $zone;
            }
        }
        return $key . ' ' . $exp . ' (' . $value . ')';
    }


    /**
     * 生成查询SQL
     * @access public
     * @param  Query  $query  查询对象
     * @return string
     */
    public function select(Query $query)
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parseDistinct($query, $options['distinct']),
                $this->parseField($query, $options['field']),
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseGroup($query, $options['group']),
                $this->parseHaving($query, $options['having']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $options['limit']),
                $this->parseUnion($query, $options['union']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
                $this->parseForce($query, $options['force']),
            ],
            $this->selectSql);
    }

    /**
     * 生成Insert SQL
     * @access public
     * @param  Query     $query   查询对象
     * @param  bool      $replace 是否replace
     * @return string
     */
    public function insert(Query $query, $replace = false)
    {
        $options = $query->getOptions();

        // 分析并处理数据
        $data = $this->parseData($query, $options['data']);
        if (empty($data)) {
            return '';
        }

        $fields = array_keys($data);
        $values = array_values($data);

        return str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($query, $options['table']),
                '`'.implode('` ,`', $fields).'`',
                implode(' , ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertSql);
    }

    /**
     * 生成insertall SQL
     * @access public
     * @param  Query     $query   查询对象
     * @param  array     $dataSet 数据集
     * @param  bool      $replace 是否replace
     * @return string
     */
    public function insertAll(Query $query, $dataSet, $replace = false)
    {
        $options = $query->getOptions();

        // 获取合法的字段
        if ('*' == $options['field']) {
            $allowFields = $this->connection->getTableFields($options['table']);
        } else {
            $allowFields = $options['field'];
        }

        // 获取绑定信息
        $bind = $this->connection->getFieldsBind($options['table']);

        foreach ($dataSet as $data) {
            $data = $this->parseData($query, $data, $allowFields, $bind);

            $values[] = 'SELECT ' . implode(',', array_values($data));

            if (!isset($insertFields)) {
                $insertFields = array_keys($data);
            }
        }

        $fields = [];

        foreach ($insertFields as $field) {
            $fields[] = $this->parseKey($query, $field);
        }

        return str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($query, $options['table']),
                implode(' , ', $fields),
                implode(' UNION ALL ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertAllSql);
    }

    /**
     * 生成slect insert SQL
     * @access public
     * @param  Query     $query  查询对象
     * @param  array     $fields 数据
     * @param  string    $table  数据表
     * @return string
     */
    public function selectInsert(Query $query, $fields, $table)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        foreach ($fields as &$field) {
            $field = $this->parseKey($query, $field, true);
        }

        return 'INSERT INTO ' . $this->parseTable($query, $table) . ' (' . implode(',', $fields) . ') ' . $this->select($query);
    }

    /**
     * 生成update SQL
     * @access public
     * @param  Query     $query  查询对象
     * @return string
     */
    public function update(Query $query)
    {
        $options = $query->getOptions();

        $data = $this->parseData($query, $options['data']);

        if (empty($data)) {
            return '';
        }

        foreach ($data as $key => $val) {
            $set[] = $key . ' = ' . $val;
        }

        return str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                implode(' , ', $set),
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $options['limit']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
            ],
            $this->updateSql);
    }

    /**
     * 生成delete SQL
     * @access public
     * @param  Query  $query  查询对象
     * @return string
     */
    public function delete(Query $query)
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                !empty($options['using']) ? ' USING ' . $this->parseTable($query, $options['using']) . ' ' : '',
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $options['limit']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
            ],
            $this->deleteSql);
    }
}
