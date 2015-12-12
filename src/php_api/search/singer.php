<?php
if (isset($_REQUEST["header"]) || isset($_REQUEST["pinyin"]) || isset($_REQUEST["keyword"])){
	if(isset($_REQUEST["header"])){
		$key_word[] = '(@header '.strval(trim($_REQUEST["header"])).')';
	}
	if(isset($_REQUEST["keyword"])){
		$key_word[] = '(@name '.strval(trim($_REQUEST["keyword"])).')';
	}
	if(isset($_REQUEST["pinyin"])){
		$key_word[] = '(@pinyin '.strval(trim($_REQUEST["pinyin"])).')';
	}
}else{
	include_once('_singer.php');
	exit;
}

//Sphinx 搜索
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';
include_once("../tools/sphinxapi.php");

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 12)
    $number = $_REQUEST["number"];
else
    $number = 12;

$pageNum = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 0;

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);
$sphinx = new SphinxClient();
$sphinx->SetServer ( SPHINX_HOST, SPHINX_PORT );
$sphinx->SetArrayResult ( true );
$sphinx->SetLimits($pageNum*$number, $number, 5000);
$sphinx->SetMaxQueryTime(10);
$sphinx->SetMatchMode ( SPH_MATCH_EXTENDED2);
$sphinx->SetFilter('enabled',array(1));
if (isset($_REQUEST['words'])){
	if(intval($_REQUEST['words']) < 9)
		$sphinx->SetFilter('words', array(intval($_REQUEST['words'])));
	else
		$sphinx->SetFilterRange('words', 9, 100);
}
$sphinx->SetSortMode ( SPH_SORT_EXTENDED, "words ASC, count DESC, head ASC, song_count ASC");

$result = $sphinx->query (implode(' | ',$key_word), 'singer');
if(!isset($result['matches'])){
	include_once('_singer.php');
	exit;
	// $finalResult = array(
		// "songs" => array(),
		// "page" => 0,
		// "total" => 0,
		// "totalNumber" => 0
	// );

	// formatResult($finalResult);
	// exit;
}
$countValue = $result['total'];
if ($countValue == 0)
    $pageCount = 0;
else {
    $pageCount = (int)($countValue / $number);
    if ($countValue % $number !=0){
        $pageCount = $pageCount + 1;
    }
    $totalNumber = intval($countValue);
}
$mid_list = array();
foreach($result['matches'] as $match){
	array_push($mid_list, $match['id']);
}

$queryString = 
"SELECT sid, serial_id, A.name, nation, A.sex, B.name AS sexType, stars, song_count 
FROM actor A 
LEFT JOIN actor_sex B ON A.sex=B.id
WHERE A.sid IN (".implode(',',$mid_list).") ORDER BY FIELD(sid,".implode(',',$mid_list).")";

$result = $mdb->query($queryString);
$artists = formatArtistsResult($result);
$finalResult = array(
    "artists" => $artists,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);
formatResult($finalResult);