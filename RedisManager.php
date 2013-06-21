<?php
/*
interface DeferedObject{
	public function createDefered($key);
	public function fillData($data);
}
*/
class RedisManager {
	/**
	 * 
	 * @var RedisProfiler
	 */
	protected $_profiler = null;
	
	/**
	 * @var Redis
	 */
	protected $_redis = null;
	
	/**
	 * @var array
	 */
	protected $_config;
	
	/**
	 * 
	 * @var boolean
	 */
	protected $_transactionalMode = false;
	
	/**
	 * @var array
	 */
	protected $_queue = array();
	
	/**
	 * 
	 * @var mixed
	 */
	protected $_lastResult;
	
	/**
	 * 
	 * @param array $config
	 */
	public function __construct($config){
		$this->_config = $config;
		
		if ($config['profiler'])
			$this->_profiler = new RedisProfiler();
	}
	
	public function __destruct(){
		if ($this->_transactionalMode)
			$this->flush();
	}
	
	/**
	 * 
	 * @param RedisProfiler $profiler
	 */
	public function setProfiler($profiler){
		$this->_profiler = $profiler;
	}
	
	/**
	 * 
	 * @return RedisProfiler
	 */
	public function getProfiler(){
		return $this->_profiler;
	}
	
	protected function _connect(){
		$this->_redis = new Redis();
		if ($this->_config['persistent'])
			$this->_redis->pconnect($this->_config['host'], $this->_config['port']);
		else
			$this->_redis->connect($this->_config['host'], $this->_config['port']);
		
		$this->_redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
		
		if ($this->_config['database'] > 0)
			$this->_redis->select($this->_config['database']);
	}
	
	protected function _beginTransaction(){
		if ($this->_redis === null)
			$this->_connect();
		
		if (!$this->_transactionalMode){
			$this->_redis->multi(Redis::PIPELINE);
			$this->_transactionalMode = true;
		}
	}
	
	public function onGet($result){
		$this->_lastResult = $result;
	}
	
	public function call(){
		$arguments = func_get_args();
		$name = array_shift($arguments);
		
		if ($this->_redis === null)
			$this->_connect();
		
		if ($this->_profiler)
			$this->_profiler->log($name, $arguments);
		
		if ($this->_transactionalMode){
			$this->_queue[] = array(
				'callback'	=>	array($this, 'onGet'),
				'params'	=>	$arguments,
			);
			call_user_func_array(array($this->_redis, $name), $arguments);
			$this->flush();
			return $this->_lastResult;
		}
		else
			return call_user_func_array(array($this->_redis, $name), $arguments);
	}
	
	public function defer(){
		$arguments = func_get_args();
		$name = array_shift($arguments);
		
		$this->_beginTransaction();

		$this->_queue[] = array(
			'callback'	=> is_callable(end($arguments)) ? array_pop($arguments) : null,
			'params'	=> $arguments,
		);
		
		if ($this->_profiler)
			$this->_profiler->log($name, $arguments);
		
		return call_user_func_array(array($this->_redis, $name), $arguments);
	}
	
	public function flush(){
		if ($this->_redis === null)
			$this->_connect();
		
		$results = $this->_redis->exec();
		
		$this->_transactionalMode = false;
		
		foreach ($this->_queue as $index => $command)
			if (isset($command['callback'])){
				$params = $command['params'];
				array_unshift($params, $results[$index]);
				call_user_func_array($command['callback'], $params);
			}
		$this->_queue = array();
	}
	
	/**
	 * 以下是常用函数，静态化常用函数能够显著减少函数调用次数，提升性能
	public function hGetAll($key, $callback = null){
		$this->_beginTransaction();

		$this->_queue[] = array(
			'callback'	=> $callback,
			'params'	=> array($key),
		);
		$this->_redis->hGetAll($key);
		
		if ($this->_profiler)
			$this->_profiler->log('hGetAll', array($key));
	}
	 */
}
