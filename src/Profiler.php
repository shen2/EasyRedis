<?php
namespace EasyRedis;

class Profiler{
	
	protected $_queries = array();
	
	public function log($name, $arguments = array()){
		$this->_queries[] = $name . ' ' . json_encode($arguments);
	}
	
	public function getTotalNumQueries(){
		return count($this->_queries);
	}
	
	public function getQueryProfiles(){
		return $this->_queries;
	}
	
	public function __destruct(){
		/*
		if (count($this->_queries) > 500){
			// do something.
		}*/
	}
}
