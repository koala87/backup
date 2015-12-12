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
    "SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude,A.match, B.name AS effect, C.name AS version 
    FROM songlist_detail D 
    LEFT JOIN media A ON D.mid = A.mid 
    LEFT JOIN media_effect B ON A.effect = B.id 
    LEFT JOIN media_version C ON A.version=C.id";

$queryParamater = " WHERE A.enabled=1";

if (!isset($_REQUEST["lid"])){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "需提交歌单编号（lid）"
    ));
    exit;
}

$queryParamater = $queryParamater." AND D.lid = %d";

//Check the black list option is open or not
$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $queryParamater = $queryParamater." AND B.black = 0";
}

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM songlist_detail WHERE lid=%d", intval($_REQUEST["lid"]));
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

if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
    $pageNum = intval($_REQUEST["page"]);
    $queryParamater = $queryParamater." LIMIT ".($pageNum * $number).",".$number;
} else {
    $pageNum = 0;
    $queryParamater = $queryParamater." LIMIT 0,".$number;
}

$queryString = $queryString.$queryParamater;

//echo $queryString."<br />";

$results = $mdb->query($queryString, intval($_REQUEST["lid"]));

$songs = formatSongsResult($results);

$finalResult = array(
    "songs" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>
