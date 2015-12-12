<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';
require_once '../tools/redisCache.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);
$redis = new redisCache(REDIS_HOST,REDIS_PORT);

$use_cache = true;
$redis_key = 'songlist#music#list';

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 9)
    $number = $_REQUEST["number"];
else
    $number = 9;

$queryString =
"SELECT mmid, serial_id, name, singer, path, lyric, prelude, has_lyric
FROM media_music";

$queryParamater = " WHERE enabled=1";

$handle = '';

if (isset($_REQUEST["header"])){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["header"]).'%';
    $queryParamater = $queryParamater." AND header LIKE %s";
} else if (isset($_REQUEST["pinyin"])){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["pinyin"]).'%';
    $queryParamater = $queryParamater." AND pinyin LIKE %s";
} else if (isset($_REQUEST["name"])){
	$use_cache = false;
    $handle = '%'.strval($_REQUEST["name"]).'%';
    $queryParamater = $queryParamater." AND name LIKE %s";
}

if (isset($_REQUEST['words'])){
	$use_cache = false;
    $words = intval($_REQUEST['words']);
    if (intval($words) < 9)
        $queryParamater = $queryParamater." AND words=".$words;
    else
        $queryParamater = $queryParamater." AND words>8";
}

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM media_music".$queryParamater, $handle);
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
    $queryParamater = $queryParamater." ORDER BY header, words";
} else {
    //$queryParamater = $queryParamater." ORDER BY words, count DESC, header";
    $queryParamater = $queryParamater." ORDER BY count DESC";
}

if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
    $pageNum = intval($_REQUEST["page"]);
    $pageParamater = $queryParamater." LIMIT ".($pageNum * $number).",".$number;
} else {
    $pageNum = 0;
    $pageParamater = $queryParamater." LIMIT 0,".$number;
}

//$queryString = $queryString.$pageParamater;

// echo $queryString."<br />";
// $pageNum = @intval($_REQUEST["page"]);
if($use_cache && ($pageNum+1) * $number <=500){
	if(!$redis->is_exist($redis_key)){
		$result = $mdb->query($queryString.$queryParamater." LIMIT 0,500");
		$redis->set($redis_key,$result,1800);
	}
	$results = $redis->get($redis_key,$pageNum * $number,($pageNum+1) * $number-1);
}else{
	$queryString = $queryString.$pageParamater;

	$results = $mdb->query($queryString, $handle);
}

$songs = formatMusicsResult($results);

$finalResult = array(
    "songs" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>
