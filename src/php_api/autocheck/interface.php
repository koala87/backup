<?php
if(!defined("CHECK_ON"))exit;
require_once '../tools/db.php';
require_once '../tools/main.php';
header("Content-type: text/html; charset=utf-8"); 
$ret_str = "";
set_time_limit(0);
$interface_list = array(
					array("interface"=>"/media/list.php","name"=>"歌曲列表","param"=>array("key"=>"value")),
					array("interface"=>"/media/board.php","name"=>"排行榜（新歌榜）","param"=>array("type"=>"new")),
					array("interface"=>"/media/advertisement.php","name"=>"公播","param"=>array("key"=>"value")),
					array("interface"=>"/singer/list.php","name"=>"歌手榜","param"=>array("key"=>"value")),
					array("interface"=>"/singer/songs.php","name"=>"歌手（刘德华）歌曲榜","param"=>array("sid"=>2484)),
					);

if($interface_list){
	foreach ($interface_list as $key => $value) {
		$options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => http_build_query($value['param']),
            )
        );
        $context = stream_context_create($options);
       	$result = file_get_contents('http://'.PHP_HOST.$value['interface'], false, $context);
        $ret_arr = json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result),true);
        if(!$ret_arr['result'] || (isset($ret_arr['result']['totalNumber']) && $ret_arr['result']['totalNumber'] <= 0) ){
        		$ret_str .= "接口".$value['name']."没有数据！".PHP_EOL;
        } 
	}
}

return $ret_str;