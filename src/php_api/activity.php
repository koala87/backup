<?php
header("content-type:text/html; charset=utf-8");
require_once 'tools/db.php';
require_once 'tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

if (isset($_REQUEST["number"]) && $_REQUEST["number"] > 10)
    $number = $_REQUEST["number"];
else
    $number = 10;

$queryString = "SELECT aid, title, thumb, type, member_count, start_date, end_date, start_time, end_time, status, address, fee, sponsor, photos, description FROM activity";

$queryParamater = "";

$countResult = $mdb->queryFirstRow("SELECT COUNT(*) AS count FROM activity");
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

if (isset($_REQUEST["page"]) && $_REQUEST["page"] > $count){
    echo json_encode(array(
        "result" => "",
        "status" => false,
        "error" => "请求页数超过总页数"
    ));
    exit;
}

if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
    $pageNum = intval(mysql_real_escape_string($_REQUEST["page"]));
    $queryParamater = $queryParamater." LIMIT ".($pageNum * $number).",".$number;
} else {
    $pageNum = 0;
    $queryParamater = $queryParamater." LIMIT 0,".$number;
}

$queryString = $queryString.$queryParamater;

//echo $queryString."<br />";

$results = $mdb->query($queryString);

$songs = array();

foreach ($results as $row) {
    $song = array(
            "said" => $row['aid'],
            "title" => $row['title'],
            "description" => $row['description'],
            "thumb" => $row['thumb'],
            "type" => $row['type'],
            "memberCount" => $row['member_count'],
            "startdate" => $row['start_date'],
            "enddate" => $row['end_date'],
            "starttime" => $row['start_time'],
            "endtime" => $row['end_time'],
            "status" => $row['status'],
            "address" => $row['address'],
            "fee" => $row['fee'],
            "sponsor" => $row['sponsor'],
            "photos" => $row['photos']
        );
    array_push($songs, $song);
}

$finalResult = array(
    "activities" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);

?>
