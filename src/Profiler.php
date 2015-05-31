<?php
namespace EasyRedis;

class Profiler{
	
	protected $_queries = array();
	
	protected $_commands = array();
	
	protected $_totalCommands = 0;

	public function log($name, $arguments = array()){
		$this->_commands[] = $name . ' ' . json_encode($arguments);
	}
	
	public function execute(){
		$this->_queries[] = $this->_commands;
		$this->_totalCommands += count($this->_commands);
		$this->_commands = array();
	}
	
	public function getTotalNumQueries(){
		return count($this->_queries);
	}
	
	public function getTotalCommands(){
		return $this->_totalCommands;
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
