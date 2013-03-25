<?php
class RedisProfiler{
	
	protected $_queries = array();
	
	protected $_count = 0;
	
	public function log($name, $arguments){
		$this->_queries[] = $name . json_encode($arguments);
		$this->_count ++;
		
		if ($this->_count >= 300 && $this->_count % 100 === 0)
			Duoshuo::log($this->_count . ' quries: ' . $_SERVER['REQUEST_URI'] . "\n" . implode(',', $this->_queries) . "\n", 'redis-slow');
	}
	
	public function getTotalNumQueries(){
		return count($this->_queries);
	}
	
	public function getQueryProfiles(){
		return $this->_queries;
	}
}
