<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 9)
    $number = $_REQUEST["number"];
else
    $number = 9;
$ip = '';
if(!empty($_SERVER["HTTP_CLIENT_IP"])){
  $ip = $_SERVER["HTTP_CLIENT_IP"];
}elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
  $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
}elseif(!empty($_SERVER["REMOTE_ADDR"])){
  $ip = $_SERVER["REMOTE_ADDR"];
} 
$queryParamater = "";

//Check the black list option is open or not
$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $queryParamater .=  $ip ? " AND A.black = 0" : " AND B.black = 0";
}

if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
    $pageNum = intval($_REQUEST["page"]);
    $pageParamater = " LIMIT ".($pageNum * $number).",".$number;
} else {
    $pageNum = 0;
    $pageParamater = " LIMIT 0,".$number;
}

if ($ip){
	$queryString =
	"SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude, A.match, B.name AS effect, C.name AS version 
	FROM media A 
	LEFT JOIN media_effect B ON A.effect = B.id 
	LEFT JOIN media_version C ON A.version=C.id 
	LEFT JOIN publicsong_list D ON D.mid=A.mid
	LEFT JOIN publicsong_ip E ON E.type=D.type
	WHERE A.enabled = 1 AND E.ip=%s";
	$sql = "SELECT count(*) as count FROM media A 
	LEFT JOIN publicsong_list B ON A.mid=B.mid
	LEFT JOIN publicsong_ip C ON C.type=B.type
	WHERE A.enabled=1 AND C.ip=%s ";
	$countResult = $mdb->queryFirstRow($sql, $ip);
	$results = $mdb->query($queryString.$queryParamater.$pageParamater,$ip);
}

if(!$ip || !$results){
	$queryString =
	"SELECT B.mid, B.serial_id, B.name, B.singer, B.path, B.original_track, B.sound_track, B.start_volume_1, B.start_volume_2, B.lyric, B.prelude, B.match, C.name AS effect, D.name AS version 
	FROM media_list A 
	LEFT JOIN media B ON A.mid = B.mid 
	LEFT JOIN media_effect C ON B.effect = C.id 
	LEFT JOIN media_version D ON B.version=D.id 
	WHERE A.type='ad'";
	$countResult = $mdb->queryFirstRow("SELECT COUNT(*) as count FROM media_list LEFT JOIN media ON media_list.mid = media.mid WHERE media_list.type = 'ad'".$queryParamater);
	$results = $mdb->query($queryString.$queryParamater.$pageParamater);
}

$totalNumber = 0;
$countValue = $countResult['count'];
if ($countValue == 0)
    $pageCount = 0;
else {
    $pageCount = (int)($countValue / $number);
    if ($countValue % $number !=0){
        $pageCount = $pageCount + 1;
    }
    $totalNumber = intval($countValue);
}


if (isset($_REQUEST["page"]) && intval($_REQUEST["page"]) > $pageCount){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "请求页数超过总页数"
    ));
    exit;
}

$songs = formatSongsResult($results);

$finalResult = array(
    "songs" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>