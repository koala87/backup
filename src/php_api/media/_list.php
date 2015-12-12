<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';
require_once '../tools/redisCache.php';
// header("content-type:text/html");
$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);
$redis = new redisCache(REDIS_HOST,REDIS_PORT);
if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 9)
    $number = $_REQUEST["number"];
else
    $number = 9;
	
$use_cache = true;
$redis_key = "songlist#media#list";

$queryString =
"SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude, A.match, B.name AS effect, C.name AS version, A.artist_sid_1 AS singer_id 
FROM media A 
LEFT JOIN media_effect B ON A.effect = B.id 
LEFT JOIN media_version C ON A.version=C.id";

$queryParamater = " WHERE A.enabled=1";

$type = "";

if (isset($_REQUEST["type"])){
    $r1 = $mdb->queryFirstRow("SELECT id, name FROM media_type WHERE name=%s", $_REQUEST["type"]);
    $type = $r1["id"];//set type
    if ($type == ""){
        echo json_encode(array(
            "result" => null,
            "status" => false,
            "error" => "请求的类型不存在"
        ));
        exit;
    } else {
        $queryParamater = $queryParamater." AND A.type=".$type;
    }
}

if($type){
	$redis_key .= "#".$type;
}

$language = "";

if (isset($_REQUEST["language"])){
    $r2 = $mdb->queryFirstRow("SELECT id, name FROM media_language WHERE name=%s", $_REQUEST["language"]);
    $language = $r2["id"];//set language
    if ($language == ""){
        echo json_encode(array(
            "result" => null,
            "status" => false,
            "error" => "请求的语种不存在"
        ));
        exit;
    } else {
        $queryParamater = $queryParamater." AND A.language=".$language;
    }
	$redis_key .= "#".trim($_REQUEST["language"]);
}

$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $queryParamater = $queryParamater." AND A.black=0";
}

$handle = '';

if (isset($_REQUEST["header"]) && $_REQUEST["header"]){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["header"]).'%';
    $queryParamater = $queryParamater." AND A.header LIKE %s";
} else if (isset($_REQUEST["pinyin"]) && $_REQUEST["pinyin"]){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["pinyin"]).'%';
    $queryParamater = $queryParamater." AND A.pinyin LIKE %s";
} else if (isset($_REQUEST["name"]) && $_REQUEST["name"]){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["name"]).'%';
    $queryParamater = $queryParamater." AND A.name LIKE %s";
}

if (isset($_REQUEST['words'])){
	$use_cache = false;
    $words = intval($_REQUEST['words']);
    if (intval($words) < 9)
        $queryParamater = $queryParamater." AND words=".$words;
    else
        $queryParamater = $queryParamater." AND words>8";
}

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM media A".$queryParamater, $handle);
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

// echo $pageCount."<br />";

if (isset($_REQUEST["page"]) && intval($_REQUEST["page"]) > $pageCount){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "请求页数超过总页数"
    ));
    exit;
}

if (isset($_REQUEST["sort"]) && $_REQUEST["sort"] == 1){
	$use_cache = false;
    $queryParamater = $queryParamater." ORDER BY A.header, A.words";
} else {
    //$queryParamater = $queryParamater." ORDER BY A.words, A.header ASC, A.count DESC";
    $queryParamater = $queryParamater." ORDER BY A.lang_part, A.words, A.header, A.desc_count ASC";
}

// echo $queryString."<br />";
$pageNum = @intval($_REQUEST["page"]);
if($use_cache && ($pageNum+1) * $number <=500){
	if(!$redis->is_exist($redis_key)){
		$results = $mdb->query($queryString.$queryParamater." LIMIT 0,500", $handle);
		$redis->set($redis_key,$results,1800);
	}
	$pageNum = @intval($_REQUEST["page"]);
	$results = $redis->get($redis_key,$pageNum * $number,($pageNum+1) * $number-1);
}else{
	if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
		$pageNum = intval($_REQUEST["page"]);
		$queryParamater = $queryParamater." LIMIT ".($pageNum * $number).",".$number;
	} else {
		$pageNum = 0;
		$queryParamater = $queryParamater." LIMIT 0,".$number;
	}

	$queryString = $queryString.$queryParamater;
	$results = $mdb->query($queryString, $handle);
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
