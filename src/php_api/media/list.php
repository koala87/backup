<?php
if (isset($_REQUEST["header"]) && $_REQUEST["header"]){
	$key_word = '@header '.trim($_REQUEST["header"]);
} else if (isset($_REQUEST["pinyin"]) && $_REQUEST["pinyin"]){
	$key_word = '@pinyin '.trim($_REQUEST["pinyin"]);
} else if (isset($_REQUEST["name"]) && $_REQUEST["name"]){
	$key_word = '@name '.trim($_REQUEST["name"]);
}else{
	include_once('_list.php');
	exit;
}

//$sphinx_index = 'media';
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
$sphinx->SetLimits($pageNum*$number, $number, 5000);
$sphinx->SetMaxQueryTime(10);
$sphinx->SetMatchMode ( SPH_MATCH_EXTENDED2 );
$sphinx->SetFilter('enabled',array(1));
if (isset($_REQUEST["type"])){
    $r1 = $mdb->queryFirstRow("SELECT id, name FROM media_type WHERE name=%s", $_REQUEST["type"]);
    $type = $r1["id"];//set type
    if ($type == ""){
        echo json_encode(array(
            "result" => null,
            "status" => false,
            "error" => "请求的类型不存在"
        ));
        exit;
    }
	$sphinx->SetFilter('type',array($type));
}

if(isset($_REQUEST['language'])){
	$r2 = $mdb->queryFirstRow("SELECT id, name FROM media_language WHERE name=%s", $_REQUEST["language"]);
    $language = $r2["id"];//set language
    if ($language == ""){
        echo json_encode(array(
            "result" => null,
            "status" => false,
            "error" => "请求的语种不存在"
        ));
        exit;
    }
	$sphinx->SetFilter('language', array($language));
}

if (isset($_REQUEST['words'])){
	if(intval($_REQUEST['words']) < 9)
		$sphinx->SetFilter('words', array(intval($_REQUEST['words'])));
	else
		$sphinx->SetFilterRange('words', 9, 100);
}

$sphinx->SetSortMode ( SPH_SORT_EXTENDED, "lang_part ASC, words ASC, header_sort ASC, count DESC");
//索引源是配置文件中的 index 类，如果有多个索引源可使用,号隔开：'email,diary' 或者使用'*'号代表全部索引源
$result = $sphinx->query ($key_word, 'media'); 
// echo '<pre>';
// print_r($result);
// echo '</pre>';
if(!isset($result['matches'])){
	include_once('_list.php');
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
"SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude, A.match, B.name AS effect, C.name AS version, A.artist_sid_1 AS singer_id 
FROM media A 
LEFT JOIN media_effect B ON A.effect = B.id 
LEFT JOIN media_version C ON A.version=C.id WHERE A.mid IN (".implode(',',$mid_list).") ORDER BY FIELD(mid,".implode(',',$mid_list).")";
$results = $mdb->query($queryString);
$songs = formatSongsResult($results);



$finalResult = array(
    "songs" => $songs,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);




