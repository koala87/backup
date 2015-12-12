<?php
header("content-type:application/json");
require_once '../tools/main.php';

if (!isset($_REQUEST["mids"]) || !is_array(json_decode($_REQUEST["mids"]))){
    echo json_encode(array(
        "result" => "",
        "status" => false,
        "error" => "需提交歌曲编号(mids)"
    ));
    exit;
}

$finalResult = array();

foreach (json_decode($_REQUEST["mids"]) as $mid) {
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => http_build_query(array('mid' => $mid)),
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents('http://'.PHP_HOST.'media/recommend.php', false, $context);
    $dic = (array)json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result));
    $output = (array)$dic['result'];
    $song = array(
        "mid" => $mid,
        "songs" => (array)$output['songs'],
    );
    array_push($finalResult, $song);
}

formatResult($finalResult);
?>