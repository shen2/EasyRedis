<?php 
require 'src/Manager.php';
require 'src/Promise.php';
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
$redisManager->send('set', ['a', 'abc']);
echo $redisManager->send('get', ['a']) . "\n";

// send request asynchronously
$redisManager->sendAsync('set', ['a', 'abc']);

$redisManager->sendAsync('set', ['b', '123'])->then(function(){
    echo "successed.\n";
});
