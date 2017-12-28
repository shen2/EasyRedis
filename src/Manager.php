<?php
namespace EasyRedis;

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
    protected $_promises = [];

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

    public function send($name, $params = []){

        if ($this->_redis === null)
            $this->_connect();

        if ($this->_profiler)
            $this->_profiler->log($name, $params);

        if (!empty($this->_promises)){
            // Send the method call first, enqueue later.
            $result = call_user_func_array(array($this->_redis, $name), $params);
            if ($result === false)
                $this->_throwException($name, $params);
            
            $promise = new Promise();
            $promise->done(function($result) use(&$lastResult) {
                $lastResult = $result;
            });
            $this->_promises[] = $promise;
            
            $this->flush();
            return $lastResult;
        }
        else{
            if($this->_profiler)
                $this->_profiler->start();

            $result = call_user_func_array(array($this->_redis, $name), $params);

            if ($this->_profiler)
                $this->_profiler->execute();

            return $result;
        }
    }

    public function sendAsync($name, $params = []){
        if ($this->_redis === null)
            $this->_connect();

        if (empty($this->_promises))
            $this->_redis->multi(\Redis::PIPELINE);

        if ($this->_profiler)
            $this->_profiler->log($name, $params);

        // Send the method call first, enqueue later.
        $result = call_user_func_array(array($this->_redis, $name), $params);
        if ($result === false)
            $this->_throwException($name, $params);
        
        $this->_promises[] = $promise = new Promise();
        
        return $promise;
    }

    public function flush(){
        if (empty($this->_promises))
            return;

        if ($this->_redis === null)
            $this->_connect();

        if($this->_profiler)
            $this->_profiler->start();

        $results = $this->_redis->exec();

        if ($this->_profiler)
            $this->_profiler->execute();

        foreach ($this->_promises as $index => $promise){
            $promise->fulfill($results[$index]);
        }
        $this->_promises = [];
    }
    
    protected function _throwException($name, $params){
        $args = [];
        foreach($params as $arg){
            $args[] = var_export($arg, true);
        }
        throw new Exception('Redis::' . $name . '(' . implode(', ', $args) . ') returns FALSE');
    }
}
