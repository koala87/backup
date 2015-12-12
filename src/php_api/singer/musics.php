<?php
header("content-type:text/html; charset=utf-8");
require_once '../tools/db.php';
require_once '../tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 9)
    $number = $_REQUEST["number"];
else
    $number = 9;

$queryString =
"SELECT mmid, serial_id, name, singer, path, lyric, prelude
FROM media_music";

$queryParamater = " WHERE enabled=1";

if (!isset($_REQUEST["sid"])){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "需提交歌手编号（sid）"
    ));
    exit;
}

$artistid = intval($_REQUEST["sid"]);

$queryParamater = $queryParamater." AND (artist_sid_1=%d OR artist_sid_2=%d)";

$handle = '';

if (isset($_REQUEST["header"])){
	$handle = '%'.strval($_REQUEST["header"]).'%';
	$queryParamater = $queryParamater." AND header LIKE %s";
} else if (isset($_REQUEST["pinyin"])){
    $handle = '%'.strval($_REQUEST["pinyin"]).'%';
    $queryParamater = $queryParamater." AND pinyin LIKE %s";
} else if (isset($_REQUEST["name"])){
    $handle = '%'.strval($_REQUEST["name"]).'%';
    $queryParamater = $queryParamater." AND name LIKE %s";
}

if (isset($_REQUEST['words'])){
    $words = intval($_REQUEST['words']);
    if (intval($words) < 9)
        $queryParamater = $queryParamater." AND words=".$words;
    else
        $queryParamater = $queryParamater." AND words>8";
}

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM media_music".$queryParamater, $artistid, $artistid, $handle);
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

//echo $count."<br />";

if (isset($_REQUEST["page"]) && $_REQUEST["page"] > $pageCount){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "请求页数超过总页数"
    ));
    exit;
}

if (isset($_REQUEST["sort"]) && $_REQUEST["sort"] == 1){
    $queryParamater = $queryParamater." ORDER BY header, words";
} else {
    $queryParamater = $queryParamater." ORDER BY words, header, count DESC";
}

if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
    $pageNum = intval($_REQUEST["page"]);
    $queryParamater = $queryParamater." LIMIT ".($pageNum * $number).",".$number;
} else {
    $pageNum = 0;
    $queryParamater = $queryParamater." LIMIT 0,".$number;
}

$queryString = $queryString.$queryParamater;

//echo $queryString."<br />";

$results = $mdb->query($queryString, $artistid, $artistid, $handle);

$songs = formatMusicsResult($results);

$finalResult = array(
    "songs" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>
