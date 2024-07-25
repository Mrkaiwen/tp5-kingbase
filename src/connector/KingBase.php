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

namespace Wwws3\Kingbase\connector;

use PDO;
use think\db\Connection;

/**
 * KingBase数据库驱动
 */
class KingBase extends Connection
{

    /**
     * 解析pdo连接的dsn信息
     * @access protected
     * @param  array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        if (!empty($config['socket'])) {
            $dsn = 'kdb:unix_socket=' . $config['socket'];
        } elseif (!empty($config['hostport'])) {
            $dsn = 'kdb:host=' . $config['hostname'] . ';port=' . $config['hostport'];
        } else {
            $dsn = 'kdb:host=' . $config['hostname'];
        }
        $dsn .= ';dbname=' . $config['database'];

        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @param  string $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        $tabs = explode('.', $tableName);
        $tableName = end($tabs);

        $sql = "SELECT column_name as Field, data_type as Type, character_maximum_length, is_nullable as Null
            FROM information_schema.columns
            WHERE table_name = '{$tableName}'
            ORDER BY ordinal_position";

        $pdo    = $this->query($sql, [], false, true);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);

        $info   = [];

        if ($result) {
            foreach ($result as $key => $val) {
                $val                 = array_change_key_case($val);
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => 'NO' == $val['null'],
//                    'default' => $val['default'],
//                    'primary' => strtolower($val['key']) == 'pri',
//                    'autoinc' => strtolower($val['extra']) == 'auto_increment',
                ];
            }
        }

        return $this->fieldCase($info);
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param  string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $sql    = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $pdo    = $this->query($sql, [], false, true);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];

        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    protected function supportSavepoint(): bool
    {
        return true;
    }

    /**
     * 启动XA事务
     * @access public
     * @param  string $xid XA事务id
     * @return void
     */
    public function startTransXa($xid)
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }

        $this->linkID->exec("XA START '$xid'");
    }

    /**
     * 预编译XA事务
     * @access public
     * @param  string $xid XA事务id
     * @return void
     */
    public function prepareXa($xid)
    {
        $this->initConnect(true);
        $this->linkID->exec("XA END '$xid'");
        $this->linkID->exec("XA PREPARE '$xid'");
    }

    /**
     * 提交XA事务
     * @access public
     * @param  string $xid XA事务id
     * @return void
     */
    public function commitXa($xid)
    {
        $this->initConnect(true);
        $this->linkID->exec("XA COMMIT '$xid'");
    }

    /**
     * 回滚XA事务
     * @access public
     * @param  string $xid XA事务id
     * @return void
     */
    public function rollbackXa($xid)
    {
        $this->initConnect(true);
        $this->linkID->exec("XA ROLLBACK '$xid'");
    }

    /**
     * SQL性能分析
     * @access protected
     * @param  string $sql
     * @return array
     */
    protected function getExplain($sql)
    {
        return [];
    }
}
