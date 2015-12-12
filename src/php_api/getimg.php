<?php
/**
 * 将图片缓存到redis,有效期一个月
 * @author dzhua
 * @date 2015-10-23
 */
 
 //开启缓存：true, 关闭缓存：false
$use_cache = true;

$path = trim($_GET['path']);
if($use_cache) {
	$path = dirname(__FILE__)  . trim($_GET['path']);
	
	if(file_exists($path)) {
		$redis = new Redis();
		$redis->connect('127.0.0.1', 6379);
		$redis->select(10);

		//断点续传206
		http_response_code(206);
		header('Content-type: image/jpeg');
		if($redis->exists($path)) {
			echo $redis->get($path);
		} else {
			$img = file_get_contents($path);
			echo $img;
			
			$redis->set($path, $img);
			$redis->expire($path, 86400*30);
		}
	} else {
		/*
		$fileName = date('Y-m-d').'img.log';
		$text = $path.PHP_EOF;
		if($fp = fopen($fileName, 'a')) {
			if(@fwrite($fp, $text)) {
				@fclose($fp);
			} else {
				@fclose($fp);
			}
		}
		*/
	}
} else {
	header('Location: '.$path);
}
