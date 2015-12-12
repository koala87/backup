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
"SELECT mmid, serial_id, name, singer, path, lyric, prelude
FROM media_music A";

$queryParamater = " WHERE enabled=1";

if (!isset($_REQUEST["keyword"]) && !isset($_REQUEST["pinyin"]) && !isset($_REQUEST["header"])){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "未提交关键字"
    ));
    exit;
}

// if (isset($_REQUEST["keyword"])){
    // $queryParamater = $queryParamater." OR name LIKE ".'\'%'.strval($_REQUEST["keyword"]).'%\'';
// }

// if (isset($_REQUEST["pinyin"]) && isset($_REQUEST["header"])){
    // $queryParamater = $queryParamater." OR (pinyin LIKE ".'\'%'.strval($_REQUEST["pinyin"]).'%\''."OR header LIKE ".'\'%'.strval($_REQUEST["header"]).'%\''.")";
// }
$par_arr = array();
if (isset($_REQUEST["keyword"])){
    $str = " A.name LIKE ".'\'%'.strval($_REQUEST["keyword"]).'%\'';
	array_push($par_arr,$str);
}

if (isset($_REQUEST["pinyin"])){
	$str = " A.pinyin LIKE ".'\'%'.strval($_REQUEST["pinyin"]).'%\'';
	array_push($par_arr,$str);
    //$queryParamater = $queryParamater." OR (A.pinyin LIKE ".'\'%'.strval($_REQUEST["pinyin"]).'%\''."OR A.header LIKE ".'\'%'.strval($_REQUEST["header"]).'%\''.")";
}

if(isset($_REQUEST["header"])){
	$str = " A.header LIKE ".'\'%'.strval($_REQUEST["header"]).'%\'';
	array_push($par_arr,$str);
}

$queryParamater .= ' AND ('.implode(' OR ',$par_arr).')';

if (isset($_REQUEST['words'])){
    $words = intval($_REQUEST['words']);
    if (intval($words) < 9)
        $queryParamater = $queryParamater." AND words=".$words;
    else
        $queryParamater = $queryParamater." AND words>8";
}

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM media_music A".$queryParamater);
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

$queryParamater = $queryParamater." ORDER BY words ASC, count DESC, head ASC";

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

// echo $queryString."<br />";

$results = $mdb->query($queryString);

$songs = formatMusicsResult($results);

$finalResult = array(
    "songs" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>
