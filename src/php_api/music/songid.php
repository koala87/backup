<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

if (!isset($_REQUEST["serialid"]) && !isset($_REQUEST["mmid"])){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "请提交正确的参数"
    ));
    exit;
}

$queryString = 
"SELECT mmid, serial_id, name, singer, path, lyric, prelude, has_lyric
FROM media_music";

$queryParamater = " WHERE enabled=1";

if (isset($_REQUEST["serialid"])){
    $queryParamater = $queryParamater." AND serial_id=%d";
    $handler = $_REQUEST["serialid"];
}
else if (isset($_REQUEST["mmid"])){
    $queryParamater = $queryParamater." AND mmid=%d";
    $handler = $_REQUEST["mmid"];
}

$queryString = $queryString.$queryParamater;

//echo $queryString."<br />";

$result = $mdb->queryFirstRow($queryString, $handler);

if (!$result){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "查询内容不存在"
    ));
    exit;
}

$finalResult = array(
    "song" => formatMusicResult($result)
);

echo json_encode(array(
    "result" => $finalResult,
    "status" => true,
    "error" => ""
));

saveLog($GLOBALS['timeStart'], $_SERVER['REQUEST_URI']);
unset($GLOBALS['timeStart']);
?>