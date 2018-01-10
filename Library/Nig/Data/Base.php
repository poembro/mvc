<?php
/**
 * @Copyright (C),
 * @Author poembro
 * @Date: 2017-11-08 12:37:46
 * @Description Base 数据处理基础类
 */
namespace Nig\Data;

use Nig\Data\Mysql;
use Nig\Data\Rdb;
use Nig\Config;

class Base
{
    /**
     * 对象缓存
     * @var array
     * @access protected
     */
    private static $_conn = []; 
    
    final private function _get($key)
    {
        if (!$key)
        {
            return trigger_error("Data config error !".
                __FILE__ . ':'. __LINE__, E_USER_ERROR);
        }
        return Config::get($key); 
    }
    
    final public function mysql($key = 'mysql') 
    {
        if (isset(self::$_conn[$key]))
        {
            return self::$_conn[$key];
        }
        
        $conf = $this->_get($key);
        self::$_conn[$key] = new Mysql($conf);
        
        return self::$_conn[$key];
    }
    
    final public function redis($key = 'redis')
    {
        if (isset(self::$_conn[$key]))
        {
            return self::$_conn[$key];
        }
        
        $conf = $this->_get($key);
        
        self::$_conn[$key] = Rdb::getInstance($conf);
        return self::$_conn[$key];
    }
}
 