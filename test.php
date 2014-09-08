<?php 
require 'src/Manager.php';
require 'src/Profiler.php';

$config = array(
	'host'		=> 'localhost',
	'port'		=> 6379,
	'persistent'=> false,
	'database'	=> 0,
	'profiler'	=> true,
);

// Create a connection.
$redisManager = new EasyRedis\Manager($config);

// send request synchronously.
$redisManager->call('set', 'a', 'abc');
echo $redisManager->call('get', 'a') . "\n";

// send request asynchronously
$redisManager->defer('set', ['a', 'abc']);

$redisManager->defer('set', ['b', '123'], function(){
    echo "successed.\n";
});
