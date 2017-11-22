<?php
/**
 * @Copyright (C),
 * @Author poembro
 * @Date: 2017-11-08 12:37:46
 * @Description  index 入口文件初始化类
 */
define("DS", DIRECTORY_SEPARATOR);
define("FRAMEWORK_PATH", dirname(dirname(dirname(__DIR__))) . DS .'Library' . DS . 'Nig' . DS);
define("APPLICATION_PATH", dirname(dirname(__DIR__)) . DS .'App' . DS);
 
ini_set('display_errors',1);            
error_reporting(E_ALL);

if (PHP_SAPI === 'cli')
{
	$_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
}

include FRAMEWORK_PATH .'Nig.php'; 
$nig = \Nig\Nig::getInstance(APPLICATION_PATH . 'Config/Main.php');

$nig->useNode('/nig/app/public/index.php', function($req, $res) {
	$m = new \App\Model\User\Main();
	$m->setOne('hello', 123);
    return $m->getOne('hello');
});
 
//挂载对应控制器下的方法
$nig->autoNode($_SERVER['REQUEST_URI']);

echo $nig->run($_SERVER['REQUEST_URI']);
echo PHP_EOL;
//cli方式执行 [root@www Public]# php index.php /api/auth/test



