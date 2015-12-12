<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 12)
    $number = $_REQUEST["number"];
else
    $number = 12;

$queryString = 
"SELECT sid, serial_id, A.name, nation, A.sex, B.name AS sexType, stars, song_count 
FROM actor A 
LEFT JOIN actor_sex B ON A.sex=B.id
";

$queryParamater = " WHERE A.enabled=1";

if (!isset($_REQUEST["keyword"]) && !isset($_REQUEST["pinyin"]) && !isset($_REQUEST["header"])){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "未提交关键字"
    ));
    exit;
}

// if (isset($_REQUEST["keyword"])){
    // $queryParamater = $queryParamater." OR A.name LIKE ".'\'%'.strval($_REQUEST["keyword"]).'%\'';
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

$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $queryParamater = $queryParamater." AND A.black=0";
}

if (isset($_REQUEST['words'])){
    $words = intval($_REQUEST['words']);
    if (intval($words) < 9)
        $queryParamater = $queryParamater." AND words=".$words;
    else
        $queryParamater = $queryParamater." AND words>8";
}

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM actor A".$queryParamater);
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

$queryParamater = $queryParamater." ORDER BY A.words ASC, A.count DESC, A.head ASC, A.song_count ASC";

if (isset($_REQUEST["page"]) && $_REQUEST["page"] > $pageCount){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "请求页数超过总页数"
    ));
    exit;
}

if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
    $pageNum = intval(($_REQUEST["page"]));
    $queryParamater = $queryParamater." LIMIT ".($pageNum * $number).",".$number;
} else {
    $pageNum = 0;
    $queryParamater = $queryParamater." LIMIT 0,".$number;
}

$queryString = $queryString.$queryParamater;

// echo $queryString."<br />";

$result = $mdb->query($queryString);

$artists = formatArtistsResult($result);

$finalResult = array(
    "artists" => $artists,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>
