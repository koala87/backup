<?php
header("content-type:application/json");
$GLOBALS['timeStart'] = microtime(true);
require_once '../tools/main.php';

$finalResult = array();
$songs = array();
$artists = array();

if (!isset($_REQUEST["keyword"])){
    {//hot media
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => http_build_query(array('type' => 'hot')),
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents('http://'.PHP_HOST.'/media/board.php', false, $context);
        $dic = (array)json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result));
        $output = (array)$dic['result'];
        $songs = (array)$output['songs'];
    }

    {//hot singer
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents('http://'.PHP_HOST.'/singer/list.php', false, $context);
        $dic = (array)json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result));
        $output = (array)$dic['result'];
        $artists = (array)$output['artists'];
    }
	
	{//music
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents('http://'.PHP_HOST.'/music/list.php', false, $context);
        $dic = (array)json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result));
        $output = (array)$dic['result'];
        $music = (array)$output['songs'];
    }

    $finalResult = array(
    "songs" => $songs,
    "artists" => $artists,
	"music" => $music
    );

    formatResult($finalResult);

} else {
    {//search media
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => http_build_query(array('keyword' => $_REQUEST["keyword"],'pinyin' => $_REQUEST["keyword"],'header' => $_REQUEST["keyword"])),
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents('http://'.PHP_HOST.'/search/song.php', false, $context);
        $dic = (array)json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result));
        $output = (array)$dic['result'];
        $songs = (array)$output['songs'];
    }

    {//search singer
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => http_build_query(array('keyword' => $_REQUEST["keyword"],'pinyin' => $_REQUEST["keyword"],'header' => $_REQUEST["keyword"])),
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents('http://'.PHP_HOST.'/search/singer.php', false, $context);
        $dic = (array)json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result));
        $output = (array)$dic['result'];
        $artists = (array)$output['artists'];
    }
	
	{//search singer
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => http_build_query(array('keyword' => $_REQUEST["keyword"],'pinyin' => $_REQUEST["keyword"],'header' => $_REQUEST["keyword"])),
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents('http://'.PHP_HOST.'/search/music.php', false, $context);
        $dic = (array)json_decode(preg_replace('/[^(\x20-\x7F)]*/','', $result));
        $output = (array)$dic['result'];
        $music = (array)$output['songs'];
    }

    $finalResult = array(
    "songs" => $songs,
    "artists" => $artists,
	"music" => $music
    );

    formatResult($finalResult);

}
?>