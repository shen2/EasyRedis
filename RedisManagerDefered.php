<?php
class RedisManagerDefered extends RedisManager{
	
	public static function __callStatic($name, $arguments){
		self::_beginTransaction();
	
		self::$_queue[] = array(
			'callback'	=> is_callable(end($arguments)) ? array_pop($arguments) : null,
			'params'	=> $arguments,
		);
		self::$_profiler->log($name, $arguments);
		return call_user_func_array(array(self::$_redis, $name), $arguments);
	}
	
	/**
	 * 以下是常用函数，静态化常用函数能够显著减少函数调用次数，提升性能
	 */
	public static function hGetAll($key, $callback = null){
		self::_beginTransaction();
		
		self::$_queue[] = array(
			'callback'	=> $callback,
			'params'	=> array($key),
		);
		self::$_redis->hGetAll($key);
		self::$_profiler->log('hGetAll', array($key));
	}
	
	public static function hMset($key, $data, $callback = null){
		self::_beginTransaction();
		
		self::$_queue[] = array(
			'callback'	=> $callback,
			'params'	=> array($key, $data),
		);
		self::$_redis->hMset($key, $data);
		self::$_profiler->log('hMset', array($key, $data));
	}
	
	public static function delete($key, $callback = null){
		self::_beginTransaction();
	
		self::$_queue[] = array(
			'callback'	=> $callback,
			'params'	=> array($key),
		);
		self::$_redis->delete($key);
		self::$_profiler->log('delete', array($key));
	}
}
