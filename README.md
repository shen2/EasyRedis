EasyRedis
=========

an async redis wrapper for phpredis

## Features
* Using phpredis extension (nicolasff/phpredis)
* Support asynchronous and synchronous request
* Support for logged, unlogged and counter batches

## Installation

PHP 5.4+ is required.  phpredis extension is required.

Append dependency into composer.json

```
	...
	"require": {
		...
		"shen2/easy-redis": "dev-master"
	}
	...
```

## Basic Using

```php
<?php

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
```
