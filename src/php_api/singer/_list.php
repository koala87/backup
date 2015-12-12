<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';
require_once '../tools/redisCache.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);
$redis = new redisCache(REDIS_HOST,REDIS_PORT);

$use_cache = true;
$redis_key = 'songlist#singer#list';

if (isset($_REQUEST["number"]) && intval($_REQUEST["number"]) > 12)
    $number = $_REQUEST["number"];
else
    $number = 12;

$queryString = 
"SELECT sid, serial_id, A.name, nation, A.sex, B.name AS sexType, stars, song_count, baiwei_recommend
FROM actor A 
LEFT JOIN actor_sex B ON A.sex=B.id
";

$queryParamater = " WHERE enabled = 1";

//Check the black list option is open or not
$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $queryParamater = $queryParamater." AND A.black=0";
}

$typeID = -1;

if (isset($_REQUEST["type"])){
    for ($x = 0; $x <= 15; $x++) {
        if ($singerType[$x] == strval($_REQUEST["type"])){
            $typeID = $x;
        }
    }
    if ($typeID == -1){
        echo json_encode(array(
            "result" => null,
            "status" => false,
            "error" => "请求的类型不存在"
        ));
        exit;
    }
}

if (isset($_REQUEST["type"])){
    $type = strval($_REQUEST["type"]);
    $queryParamater = $queryParamater.changeType2NSSQL($type);
}

if(isset($_REQUEST["header"])){
	$use_cache = false;
    $header = strval($_REQUEST["header"]);
    $queryParamater = $queryParamater." AND A.header LIKE '".$header."%'";
}

if (isset($_REQUEST["pinyin"])){
	$use_cache = false;
    $pinyin = strval($_REQUEST["pinyin"]);
    $queryParamater = $queryParamater." AND A.pinyin LIKE '".$pinyin."%'";
}

if (isset($_REQUEST["name"])){
	$use_cache = false;
    $name = strval($_REQUEST["name"]);
    $queryParamater = $queryParamater." AND A.name LIKE '".$name."%'";
}

if (isset($_REQUEST['words'])){
	$use_cache = false;
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

if (isset($_REQUEST["page"]) && $_REQUEST["page"] > $pageCount){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "请求页数超过总页数"
    ));
    exit;
}

if (isset($_REQUEST["sort"]) && $_REQUEST["sort"] == 1){
	$use_cache = false;
    $queryParamater = $queryParamater." ORDER BY header";
} else {
    //$queryParamater = $queryParamater." ORDER BY CASE WHEN A.order IS NULL THEN 1 ELSE 0 END, A.order";
    $queryParamater = $queryParamater." ORDER BY A.baiwei_recommend DESC,A.count DESC, A.order";
//     $queryParamater = $queryParamater." ORDER BY A.count DESC, A.order";
}

// if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
    // $pageNum = intval($_REQUEST["page"]);
    // $queryParamater = $queryParamater." LIMIT ".($pageNum * $number).",".$number;
// } else {
    // $pageNum = 0;
    // $queryParamater = $queryParamater." LIMIT 0,".$number;
// }

$queryString = $queryString.$queryParamater;

//  echo $queryString."<br />";
$pageNum = @intval($_REQUEST["page"]);
if($use_cache && ($pageNum+1) * $number <=500){
	$redis_key .= isset($_REQUEST['type']) ? '#'.$_REQUEST["type"] : '';
	if(!$redis->is_exist($redis_key)){
		$result = $mdb->query($queryString." LIMIT 0,500");
		$redis->set($redis_key,$result,1800);
	}
	$pageNum = @intval($_REQUEST["page"]);
	$result = $redis->get($redis_key,$pageNum * $number,($pageNum+1) * $number-1);
}else{
	if (isset($_REQUEST["page"]) && $_REQUEST["page"] != 0){
		$pageNum = intval($_REQUEST["page"]);
		$pageParamater = " LIMIT ".($pageNum * $number).",".$number;
	} else {
		$pageNum = 0;
		$pageParamater = " LIMIT 0,".$number;
	}

	$queryString = $queryString.$pageParamater;

	$result = $mdb->query($queryString);
}
//$result = $mdb->query($queryString);

$artists = formatArtistsResult($result);

$finalResult = array(
    "artists" => $artists,
    "page" => $pageNum,
    "total" => $pageCount,
    "totalNumber" => $totalNumber
);

formatResult($finalResult);
?>
