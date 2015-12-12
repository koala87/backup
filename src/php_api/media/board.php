<?php
// header("content-type:text/html; charset=utf-8");
header("Content-Type: application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';
require_once '../tools/redisCache.php';
//排行榜

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);
$redis = new redisCache(REDIS_HOST,REDIS_PORT);

$use_cache = true;
$redis_key = "songlist#media#board";

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 9)
    $number = $_REQUEST["number"];
else
    $number = 9;

if (isset($_REQUEST["type"])){
    $type = strval($_REQUEST["type"]);
	$redis_key .= "#".$type;
}else {
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "没有上传列表类型"
    ));
    exit;
}

$queryString =
"SELECT B.mid, B.serial_id, B.name, B.singer, B.path, B.original_track, B.sound_track, B.start_volume_1, B.start_volume_2, B.lyric, B.prelude, B.match, C.name AS effect, D.name AS version, B.artist_sid_1 AS singer_id
FROM media_list A 
LEFT JOIN media B ON A.mid = B.mid 
LEFT JOIN media_effect C ON B.effect = C.id 
LEFT JOIN media_version D ON B.version=D.id 
WHERE B.enabled = 1 AND A.type=%s";

$queryParamater = "";

//Check the black list option is open or not
$black = false;
$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $black = true;
    $queryParamater = $queryParamater." AND B.black = 0";
}

$handle = '';
$hot_filter = '';
if (isset($_REQUEST["header"])){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["header"]).'%';
    $queryParamater = $queryParamater." AND B.header LIKE %s";
    $hot_filter = " AND B.header LIKE %s";
} else if (isset($_REQUEST["pinyin"])){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["pinyin"]).'%';
    $queryParamater = $queryParamater." AND B.pinyin LIKE %s";
    $hot_filter = " AND B.pinyin LIKE %s";
} else if (isset($_REQUEST["name"])){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["name"]).'%';
    $queryParamater = $queryParamater." AND B.name LIKE %s";
    $hot_filter = " AND B.name LIKE %s";
}

if (isset($_REQUEST['words'])){
	$use_cache = false;
    $words = intval($_REQUEST['words']);
    if (intval($words) < 9)
        $queryParamater = $queryParamater." AND B.words=".$words;
    else
        $queryParamater = $queryParamater." AND B.words>8";
}

if ($type == "hot") {
	if($black){
		$querystr = $queryParamater.'AND A.black = 0';
	}
    $countResult = $mdb->queryFirstRow("SELECT LEAST(COUNT(*), 200) AS count FROM media B WHERE enabled=1 " . $queryParamater,$handle);
/* Fetch New Song from Table "media_list"
} else if ($type == "new") {
    $countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM media B WHERE enabled=1 " . $queryParamater, $handle);
*/
} else {
    $countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM media_list A LEFT JOIN media B ON A.mid = B.mid WHERE B.enabled = 1 AND A.type = %s".$queryParamater, $type, $handle);
}

$totalNumber = 0;
$countValue = $countResult['count'];
if ($countValue == 0)
    $pageCount = 0;//总页数
else {
    // $pageCount = (int)($countValue / $number);
    // if ($countValue % $number !=0){
    //     $pageCount = $pageCount + 1;
    // }

    $pageCount = ceil($countValue / $number);
    $totalNumber = intval($countValue);
}

if (isset($_REQUEST["page"]) && $_REQUEST['page'] > 1 && intval($_REQUEST["page"]) > $pageCount-1){//页数从0开始
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "请求页数超过总页数"
    ));
    exit;
}


if (isset($_REQUEST['sort']) && $_REQUEST["sort"] == 1){
	$use_cache = false;
    $queryParamater = $queryParamater." ORDER BY B.first";
} else {
	$queryParamater = $queryParamater." ORDER BY A.index";
}

if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
    $pageNum = intval($_REQUEST["page"]);//page 从0开始
    if($type == 'hot'){
        $pagenumber = ($pageNum+1) * $number - 1 > $totalNumber ? $totalNumber - ($pageNum) * $number : $number;
        $pageParamater = " LIMIT ".($pageNum * $number).",".$pagenumber;
    }else{
        $pageParamater = " LIMIT ".($pageNum * $number).",".$number;
    }
} else {
    $pageNum = 0;
    $pageParamater = " LIMIT 0,".$number;
}

if ($type == "hot"){//return hot board(dynamic)
    $hotQueryString =
    "SELECT B.mid, B.serial_id, B.name, B.singer, B.path, B.original_track, B.sound_track, B.start_volume_1, B.start_volume_2, B.lyric, B.prelude,B.match, D.name AS effect, C.name AS version, B.artist_sid_1 AS singer_id 
    FROM media B 
    LEFT JOIN media_effect D ON B.effect = D.id 
    LEFT JOIN media_version C ON B.version = C.id
    WHERE B.enabled=1";
    if ($black){
        $hotQueryString = $hotQueryString." AND B.black = 0";
    }
    $hotQueryString .= $hot_filter." ORDER BY B.count DESC";

	if($use_cache && ($pageNum+1) * $number <= 200){//使用缓存
		if(!$redis->is_exist($redis_key)){
			$results = $mdb->query($hotQueryString." LIMIT 0,200");
			$redis->set($redis_key, $results, 1800);
		}
		$pageNum = @intval($_REQUEST["page"]);
		$results = $redis->get($redis_key,$pageNum * $number,($pageNum+1) * $number-1);
	}else{
		$results = $mdb->query($hotQueryString.$pageParamater,$handle);
	}
} else {
	if($use_cache && ($pageNum+1) * $number <= 500){
		// $key = md5($queryString.$queryParamater.$type);
		if(!$redis->is_exist($redis_key)){
			$results = $mdb->query($queryString.$queryParamater." LIMIT 0,500", $type);
			$redis->set($redis_key, $results, 1800);
		}
		$pageNum = @intval($_REQUEST["page"]);
		$results = $redis->get($redis_key,$pageNum * $number,($pageNum+1) * $number-1);
	}else{
		$results = $mdb->query($queryString.$queryParamater.$pageParamater, $type,$handle);
	}
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
