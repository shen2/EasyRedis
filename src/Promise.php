<?php
namespace EasyRedis;

class Promise{
	protected $callbacks = [];

	public function __construct(){
		
	}

	public function then(callable $onFulfilled){
		$this->callbacks[] = $callback;
		return $this;
	}

	public function done(callable $onFulfilled){
		$this->callbacks[] = $callback;
		return null;
	}

	public function fulfill($message){
		foreach ($this->callbacks as $callback){
			$callback($message);
		}
	}
}
