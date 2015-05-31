<?php
namespace EasyRedis;
/*
interface DeferedObject{
	public function createDefered($key);
	public function fillData($data);
}
*/
class Manager {
	/**
	 * 
	 * @var Profiler
	 */
	protected $_profiler = null;
	
	/**
	 * @var \Redis
	 */
	protected $_redis = null;
	
	/**
	 * @var array
	 */
	protected $_config;
	
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
			$this->_profiler = new Profiler();
	}
	
	public function __destruct(){
		$this->flush();
	}
	
	/**
	 * 
	 * @param Profiler $profiler
	 */
	public function setProfiler($profiler){
		$this->_profiler = $profiler;
	}
	
	/**
	 * 
	 * @return Profiler
	 */
	public function getProfiler(){
		return $this->_profiler;
	}
	
	protected function _connect(){
		$this->_redis = new \Redis();
		if ($this->_config['persistent'])
			$this->_redis->pconnect($this->_config['host'], isset($this->_config['port']) ? $this->_config['port'] : null, isset($this->_config['timeout']) ? $this->_config['timeout'] : null);
		else
			$this->_redis->connect($this->_config['host'], isset($this->_config['port']) ? $this->_config['port'] : null, isset($this->_config['timeout']) ? $this->_config['timeout'] : null);
		
		$this->_redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
		
		if ($this->_config['database'] > 0)
			$this->_redis->select($this->_config['database']);
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
		
		if (!empty($this->_queue)){
			$this->_queue[] = array(
				'callback'	=>	array($this, 'onGet'),
				'params'	=>	$arguments,
			);
			call_user_func_array(array($this->_redis, $name), $arguments);
			$this->flush();
			return $this->_lastResult;
		}
		else{
			$this->_profiler->execute();
			return call_user_func_array(array($this->_redis, $name), $arguments);
		}
	}
	
	public function defer($name, $params = array(), $callback = null){
		if ($this->_redis === null)
			$this->_connect();
		
		if (empty($this->_queue))
			$this->_redis->multi(\Redis::PIPELINE);
		
		$this->_queue[] = array(
			'callback'	=> $callback,
			'params'	=> $params,
		);
		
		if ($this->_profiler)
			$this->_profiler->log($name, $params);
		
		return call_user_func_array(array($this->_redis, $name), $params);
	}
	
	public function flush(){
		if (empty($this->_queue))
			return;
		
		if ($this->_redis === null)
			$this->_connect();
		
		$results = $this->_redis->exec();
		
		if ($this->_profiler)
			$this->_profiler->execute();
		
		foreach ($this->_queue as $index => $command)
			if (isset($command['callback'])){
				$params = $command['params'];
				array_unshift($params, $results[$index]);
				call_user_func_array($command['callback'], $params);
			}
		$this->_queue = array();
	}
}
