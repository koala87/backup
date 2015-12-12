<?php

// $config = include_once dirname(__FILE__).'/../config.php';

// $redis = new Redis();
// $redis->connect(REDIS_HOST, REDIS_PORT);

//使用有序集合存储
class redisCache{
	private $redis=NULL;
	public function __construct($host = '127.0.0.1',$port = '6379'){
		$this->redis = new Redis();
		$this->redis->connect($host, $port);
	}
	
	public function is_exist($key){
		if($this->redis->exists($key))
			return true;
		return false;
	}
	
	public function set_delete($key){
		return $this->redis->delete($key);
	}
	
	public function set($key,$value=array(),$timeout=0){
		if(!$key)
			return false;
		if($this->is_exist($key))
			$this->redis->delete($key);
		if($value){
			foreach($value as $k=>$val){
				$this->redis->zadd($key, $k, is_array($val) ? serialize($val) : $val);
			}
		}
		if($timeout > 0){
			$this->redis->expire($key, $timeout);
		}
		return true;
	}
	
	public function get($key, $start=0, $stop=-1){
		if(!$key)
			return array();
		$ret_arr = array();
		// echo $start.'---'.$stop.'---'.PHP_EOL;
		$ret = $this->redis->zrange($key, $start, $stop);
		if($ret){
			foreach($ret as $key=>$value){
				$ret_arr[] = unserialize($value);
			}
		}
		return $ret_arr;
	}
}