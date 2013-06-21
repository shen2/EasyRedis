<?php 
$config = array(
	'host'		=> 'localhost',
	'port'		=> 6379,
	'persistent'=> false,
	'database'	=> 0,
	'profiler'	=> true,
);
$redisManager = new RedisManager($config);
