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
$sphinx->SetMatchMode ( SPH_MATCH_EXTENDED2 );
$sphinx->SetFilter('enabled',array(1));
if (isset($_REQUEST["type"])){
	$type = trim($_REQUEST["type"]); 
	switch ($type) {
		case $singerType[0]:
			$sphinx->SetFilter('nation',array(1));
			$sphinx->SetFilter('sex',array(1));
			//return ' AND nation=1 AND sex=1';
			break;
		case $singerType[1]:
			$sphinx->SetFilter('nation',array(1));
			$sphinx->SetFilter('sex',array(2));
			//return ' AND nation=1 AND sex=2';
			break;
		case $singerType[2]:
			$sphinx->SetFilter('nation',array(2));
			$sphinx->SetFilter('sex',array(1));
			//return ' AND nation=2 AND sex=1';
			break;
		case $singerType[3]:
			$sphinx->SetFilter('nation',array(21));
			$sphinx->SetFilter('sex',array(2));
			//return ' AND nation=2 AND sex=2';
			break;
		case $singerType[4]:
			$sphinx->SetFilter('nation',array(3,4));
			//return ' AND (nation=3 OR nation=4)';
			break;
		case $singerType[5]:
			$sphinx->SetFilter('nation',array(1,2));
			$sphinx->SetFilter('sex',array(3));
			//return ' AND (nation=1 OR nation=2) AND sex=3';
			break;
		case $singerType[6]:
			$sphinx->SetFilter('nation',array(1,2),false);
			$sphinx->SetFilter('sex',array(3));
			//return ' AND nation!=1 AND nation!=2 AND sex=3';
			break;
		case $singerType[7]:
			$sphinx->SetFilter('nation',array(5));
			$sphinx->SetFilter('sex',array(1));
			//return ' AND nation=5';
			break;
		case $singerType[8]:
			$sphinx->SetFilter('nation',array(1));
			$sphinx->SetFilter('sex',array(3));
			//return ' AND nation=1 AND sex=3';
			break;
		case $singerType[9]:
			$sphinx->SetFilter('nation',array(2));
			$sphinx->SetFilter('sex',array(3));
			//return ' AND nation=2 AND sex=3';
			break;
		case $singerType[10]:
			$sphinx->SetFilter('nation',array(4));
			$sphinx->SetFilter('sex',array(1));
			//return ' AND nation=4 AND sex=1';
			break;
		case $singerType[11]:
			$sphinx->SetFilter('nation',array(4));
			$sphinx->SetFilter('sex',array(2));
			//return ' AND nation=4 AND sex=2';
			break;
		case $singerType[12]:
			$sphinx->SetFilter('nation',array(4));
			$sphinx->SetFilter('sex',array(3));
			//return ' AND nation=4 AND sex=3';
			break;
		case $singerType[13]:
			$sphinx->SetFilter('nation',array(3));
			$sphinx->SetFilter('sex',array(1));
			//return ' AND nation=3 AND sex=1';
			break;
		case $singerType[14]:
			$sphinx->SetFilter('nation',array(3));
			$sphinx->SetFilter('sex',array(2));
			//return ' AND nation=3 AND sex=2';
			break;
		case $singerType[15]:
			$sphinx->SetFilter('nation',array(3));
			$sphinx->SetFilter('sex',array(3));	
			//return ' AND nation=3 AND sex=3';
			break;
		default:
			$sphinx->SetFilter('nation',array(0));
			$sphinx->SetFilter('sex',array(0));
	}
}

if (isset($_REQUEST['words'])){
	if(intval($_REQUEST['words']) < 9)
		$sphinx->SetFilter('words', array(intval($_REQUEST['words'])));
	else
		$sphinx->SetFilterRange('words', 9, 100);
}
$sphinx->SetSortMode ( SPH_SORT_EXTENDED, "count DESC,order ASC");
//索引源是配置文件中的 index 类，如果有多个索引源可使用,号隔开：'email,diary' 或者使用'*'号代表全部索引源
$result = $sphinx->query ($key_word, 'singer'); 
// echo '<pre>';
// print_r($result);
// echo '</pre>';
if(!isset($result['matches'])){
	include_once('_list.php');
	exit;
	// $finalResult = array(
		// "artists" => array(),
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
"SELECT sid, serial_id, A.name, nation, A.sex, B.name AS sexType, stars, song_count, baiwei_recommend
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
