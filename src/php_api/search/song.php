<?php
if (isset($_REQUEST["header"]) || isset($_REQUEST["pinyin"]) || isset($_REQUEST["keyword"]) || isset($_REQUEST['sid'])){
	$key_word = array();
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
	include_once('_song.php');
	exit;
}

//Sphinx 搜索
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';
include_once("../tools/sphinxapi.php");

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 9)
    $number = $_REQUEST["number"];
else
    $number = 9;

$pageNum = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 0;

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);
$sphinx = new SphinxClient();
$sphinx->SetServer ( SPHINX_HOST, SPHINX_PORT );
$sphinx->SetArrayResult ( true );
$sphinx->SetLimits($pageNum*$number, $number, 20000);
$sphinx->SetMaxQueryTime(10);
$sphinx->SetMatchMode ( SPH_MATCH_EXTENDED2);
$sphinx->SetFilter('enabled',array(1));
if (isset($_REQUEST['words'])){
	if(intval($_REQUEST['words']) < 9)
		$sphinx->SetFilter('words', array(intval($_REQUEST['words'])));
	else
		$sphinx->SetFilterRange('words', 9, 100);
}
$sphinx->SetSortMode ( SPH_SORT_EXTENDED, "lang_part ASC, words ASC, header_sort ASC, count DESC");

if(isset($_REQUEST["sid"])){ 
	$key_word[] = '(@artist_sid_1 '.intval($_REQUEST['sid']).' | @artist_sid_2 '.intval($_REQUEST['sid']).')';
}

$result = $sphinx->query (implode(' | ',$key_word), 'media'); 
if(!isset($result['matches'])){
	include_once('_song.php');
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
"SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude, A.match, B.name AS effect, C.name AS version 
FROM media A 
LEFT JOIN media_effect B ON A.effect = B.id 
LEFT JOIN media_version C ON A.version=C.id
WHERE A.mid IN (".implode(',',$mid_list).") ORDER BY FIELD(mid,".implode(',',$mid_list).")";

$result = $mdb->query($queryString);
$songs = formatSongsResult($result);
$finalResult = array(
    "songs" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
