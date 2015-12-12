<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 9)
    $number = $_REQUEST["number"];
else
    $number = 9;

$queryString =
"SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude, A.match, B.name AS effect, C.name AS version 
FROM media A 
LEFT JOIN media_effect B ON A.effect = B.id 
LEFT JOIN media_version C ON A.version=C.id";

$queryParamater = " WHERE A.enabled=1";

if (!isset($_REQUEST["sid"])){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "需提交歌手编号（sid）"
    ));
    exit;
}

$artistid = intval($_REQUEST["sid"]);

$queryParamater = $queryParamater." AND (A.artist_sid_1=%d OR A.artist_sid_2=%d)";

//Check the black list option is open or not
$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $queryParamater = $queryParamater." AND A.black=0";
}

$handle = '';

if (isset($_REQUEST["header"])){
	$handle = '%'.strval($_REQUEST["header"]).'%';
	$queryParamater = $queryParamater." AND A.header LIKE %s";
} else if (isset($_REQUEST["pinyin"])){
    $handle = '%'.strval($_REQUEST["pinyin"]).'%';
    $queryParamater = $queryParamater." AND A.pinyin LIKE %s";
} else if (isset($_REQUEST["name"])){
    $handle = '%'.strval($_REQUEST["name"]).'%';
    $queryParamater = $queryParamater." AND A.name LIKE %s";
}

if (isset($_REQUEST['words'])){
    $words = intval($_REQUEST['words']);
    if (intval($words) < 9)
        $queryParamater = $queryParamater." AND A.words=".$words;
    else
        $queryParamater = $queryParamater." AND A.words>8";
}

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM media A".$queryParamater, $artistid, $artistid, $handle);
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
    $queryParamater = $queryParamater." ORDER BY A.header, A.words";
} else {
    $queryParamater = $queryParamater." ORDER BY A.words, A.header, A.count DESC";
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

$songs = formatSongsResult($results);

$finalResult = array(
    "songs" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>
