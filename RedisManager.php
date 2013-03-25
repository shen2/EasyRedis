<?php
/*
interface DeferedObject{
	public static function createDefered($key);
	public static function fillData($data);
}
*/
class RedisManager {
	
	protected static $_profiler = null;
	
	/**
	 * @var Redis
	 */
	protected static $_redis = null;
	
	/**
	 * @var array
	 */
	protected static $_config;
	
	/**
	 * 
	 * @var boolean
	 */
	protected static $_transactionalMode = false;
	
	/**
	 * @var array
	 */
	protected static $_queue = array();
	
	/**
	 * 
	 * @var mixed
	 */
	protected static $_lastResult;
	
	/**
	 * 
	 * @param array $config
	 */
	public static function setConfig($config){
		self::$_config = $config;
	}
	
	/**
	 * 
	 * @param RedisProfiler $profiler
	 */
	public static function setProfiler($profiler){
		self::$_profiler = $profiler;
	}
	
	public static function getProfiler(){
		return self::$_profiler;
	}
	
	protected static function _connect(){
		self::$_redis = new Redis();
		if (self::$_config['persistent'])
			self::$_redis->pconnect(self::$_config['host'], self::$_config['port']);
		else
			self::$_redis->connect(self::$_config['host'], self::$_config['port']);
		
		self::$_redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
		
		self::$_redis->select(self::$_config['database']);
	}
	
	protected static function _beginTransaction(){
		if (self::$_redis === null)
			self::_connect();
		
		if (!self::$_transactionalMode){
			self::$_redis->multi(Redis::PIPELINE);
			self::$_transactionalMode = true;
		}
	}
	
	public static function close(){
		if (self::$_transactionalMode)
			self::flush();
	}
	
	public static function onGet($result){
		self::$_lastResult = $result;
	}
	
	public static function __callStatic($name, $arguments){
		if (self::$_redis === null)
			self::_connect();
		
		self::$_profiler->log($name, $arguments);
		
		if (self::$_transactionalMode){
			self::$_queue[] = array(
				'callback'	=>	array(get_called_class(), 'onGet'),
				'params'	=>	$arguments,
			);
			call_user_func_array(array(self::$_redis, $name), $arguments);
			self::flush();
			return self::$_lastResult;
		}
		else
			return call_user_func_array(array(self::$_redis, $name), $arguments);
	}
	
	public static function flush(){
		if (self::$_redis === null)
			self::_connect();
		
		$results = self::$_redis->exec();
		
		self::$_transactionalMode = false;
		
		foreach (self::$_queue as $index => $command)
			if (isset($command['callback'])){
				$params = $command['params'];
				array_unshift($params, $results[$index]);
				call_user_func_array($command['callback'], $params);
			}
		self::$_queue = array();
	}
}

require_once 'init-redis.php';
