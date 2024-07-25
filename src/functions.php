<?php

namespace Wwws3;


if (!function_exists('kb_func_version')){
    /**
     * @desc 自动加载检测
     * @author liuzw
     * @return string
     */
    function kb_func_version()
    {
        return '1.0.0';
    }
}

if(!function_exists('kdb_content')){
    function kdb_content($host = 'localhost',$db = '',$port = '54321',$user = 'SYSTEM',$passwd = '123456')
    {
        $dsn = "kdb:host={$host};dbname={$db};port={$port}";
        $password = $passwd;
        $dbh = null;
        try {
            $dbh = new \PDO($dsn,$user, $password, array(\PDO::ATTR_ERRMODE =>
                \PDO::ERRMODE_EXCEPTION));
        } catch (Exception $e) {
            echo 'Connection Failed: ' . $e->getMessage() ."\n";
        }
        return $dbh;
    }
}

if(!function_exists('kdb_copy_from_array')){
    //将数据从 PHP 数组复制到表中。
    function kdb_copy_from_array($tableName,$rows)
    {
        \PDO::kdbCopyFromArray($tableName,$rows);
    }
}


if(!function_exists('kdb_copy_from_file')){
    //将文件中的数据复制到表中。
    function kdb_copy_from_file($tableName,$filename)
    {
        \PDO::kdbCopyFromFile($tableName,$filename);
    }
}

if(!function_exists('kdb_copy_to_array')){
    //将数据库表中的数据复制到 PHP 数组中。
    function kdb_copy_to_array($tableName,$delimiter)
    {
        \PDO::kdbCopyToArray($tableName,$delimiter);
    }
}


if(!function_exists('kdb_copy_to_file')){
    //将表中的数据复制到文件中
    function kdb_copy_to_file($tableName,$filename)
    {
        \PDO::kdbCopyToFile($tableName,$filename);
    }
}

if(!function_exists('kdb_lob_create')){
    //创建一个新的大对象。
    function kdb_lob_create()
    {
        return \PDO::kdbLOBCreate();
    }
}
