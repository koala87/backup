<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 9)
    $number = $_REQUEST["number"];
else
    $number = 10;

$queryString = "SELECT lid, title, image, type, count, special FROM songlist";

$queryParamater = "";

$type = "";

if (isset($_REQUEST["type"])){
    $queryParamater = " WHERE type = %s";
	$type = strval($_REQUEST["type"]);
}

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM songlist".$queryParamater, $type);
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

$results = $mdb->query($queryString, $type);

$lists = array();

foreach ($results as $row) {
    $list = array(
        "lid" => $row['lid'],
        "title" => $row['title'],
        "image" => $row['image'],
        "type" => $row['type'],
        "count" => $row['count'],
        "special" => $row['special']
    );
    array_push($lists, $list);
}

$finalResult = array(
    "songlists" => $lists,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>
