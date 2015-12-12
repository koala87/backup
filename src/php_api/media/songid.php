<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

if (!isset($_REQUEST["serialid"]) && !isset($_REQUEST["mid"])){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "请提交正确的参数"
    ));
    exit;
}

$queryString = 
"SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude, A.match, B.name AS effect, C.name AS version 
FROM media A 
LEFT JOIN media_effect B ON A.effect = B.id 
LEFT JOIN media_version C ON A.version=C.id";

$queryParamater = " WHERE A.enabled=1";

$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $queryParamater = $queryParamater." AND A.black=0";
}

if (isset($_REQUEST["serialid"])){
    $queryParamater = $queryParamater." AND serial_id=%d";
    $handler = $_REQUEST["serialid"];
}
else if (isset($_REQUEST["mid"])){
    $queryParamater = $queryParamater." AND mid=%d";
    $handler = $_REQUEST["mid"];
}

$queryString = $queryString.$queryParamater;

//echo $queryString."<br />";

$result = $mdb->queryFirstRow($queryString, $handler);

if (!$result){
	//未获取mv 则查找MP3 
	if(isset($_REQUEST["serialid"])){
		$op_arr = array('serialid' => intval($_REQUEST["serialid"]));
	}else if (isset($_REQUEST["mid"])){
		$op_arr = array('mmid' => intval($_REQUEST["mid"]));
	}
	$options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => http_build_query($op_arr),
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents('http://'.PHP_HOST.'/music/songid.php', false, $context);
    $dic = (array)json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result),true);
    $output = (array)$dic['result'];
	//print_r($dic);exit;
	if($output && $output['song']){
		$result = array();
		$result['mid'] = $output['song']['mmid'];
		$result['serial_id'] = $output['song']['serialid'];
		$result['name'] = $output['song']['name'];
		$result['singer'] = $output['song']['singer'];
		$result['path'] = $output['song']['path'];
		$result['original_track'] = null;
		$result['sound_track'] = null;
		$result['start_volume_1'] = null;
		$result['start_volume_2'] = null;
		$result['match'] = 0;
		$result['lyric'] = $output['song']['lyric'];
		$result['prelude'] = $output['song']['prelude'];
		$result['effect'] = null;
		$result['version'] = null;
		$result['singer_id'] = null;
    }else{
		echo json_encode(array(
			"result" => null,
			"status" => false,
			"error" => "查询内容不存在"
		));
		exit;
	}
}

$finalResult = array(
    "song" => formatSongResult($result)
);

echo json_encode(array(
    "result" => $finalResult,
    "status" => true,
    "error" => ""
));

saveLog($GLOBALS['timeStart'], $_SERVER['REQUEST_URI']);
unset($GLOBALS['timeStart']);
?>