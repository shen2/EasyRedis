<?php
namespace EasyRedis;

class Profiler{

    protected $_queries = array();

    protected $_commands = array();

    protected $_commandsTime = array();

    protected $_totalCommands = 0;

    protected $_startedMicrotime = 0;

    public function log($name, $arguments = array()){
        $this->_commands[] = $name . ' ' . json_encode($arguments);
    }

    public function start(){
        $this->_startedMicrotime = microtime(true);
    }

    public function execute(){
        $this->_commandsTime[count($this->_queries)] = microtime(true) - $this->_startedMicrotime;
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

    public function getCommandsTime(){
        return $this->_commandsTime;
    }

    public function __destruct(){
        /*
        if (count($this->_queries) > 500){
                // do something.
        }*/
    }
}
