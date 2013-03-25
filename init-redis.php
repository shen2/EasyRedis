<?php 
$config = array(
	'host'		=> 'localhost',
	'port'		=> 6379,
	'persistent'=> false,
	'database'	=> 0,
);
RedisManager::setConfig($config);

require 'RedisProfiler.php';
RedisManager::setProfiler(new RedisProfiler());
