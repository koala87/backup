<?php
header("content-type:application/json");
require_once '../tools/db.php';
require_once '../tools/main.php';

$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

$queryString =
"SELECT B.mid, B.serial_id, B.name, B.singer, B.path, B.original_track, B.sound_track, B.start_volume_1, B.start_volume_2, B.lyric, B.prelude, B.match, C.name AS effect, D.name AS version 
FROM media_recommand A 
LEFT JOIN media B ON A.rmid = B.mid 
LEFT JOIN media_effect C ON B.effect = C.id 
LEFT JOIN media_version D ON B.version=D.id";

$queryParamater = " WHERE B.enabled=1";

if (!isset($_REQUEST["mid"])){
    echo json_encode(array(
        "result" => null,
        "status" => false,
        "error" => "需提交歌曲编号(mid)"
    ));
    exit;
}

$queryParamater = $queryParamater." AND A.mid=%d";

//Check the black list option is open or not
$rb = $mdb->queryFirstRow("SELECT value FROM config_resource WHERE name = 'filter_black'");
if ($rb["value"] == 1){
    $queryParamater = $queryParamater." AND B.black = 0";
}

$queryString = $queryString.$queryParamater;

// echo $queryString."<br />";

$results = $mdb->query($queryString, $_REQUEST["mid"]);

$songs = formatSongsResult($results);

//When ther is no recommend, add songs from this singer.
if (count($songs) < 5){
    $add_result = $mdb->query(
        "SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude,A.match, B.name AS effect, C.name AS version 
        FROM media A 
        LEFT JOIN media_effect B ON A.effect = B.id 
        LEFT JOIN media_version C ON A.version=C.id
        LEFT JOIN media D ON A.mid <> D.mid AND A.artist_sid_1=D.artist_sid_1 OR A.artist_sid_1=D.artist_sid_2
        WHERE D.mid=%d LIMIT 0,10", $_REQUEST["mid"]);
    foreach ($add_result as $row) {
        $song = formatSongResult($row);
        array_push($songs, $song);
        if (count($songs) == 5) break;
    }
}

//when the singer get no enough songs, add random songs in the database
if (count($songs) < 5){
    $add_result = $mdb->query(
        "SELECT A.mid, A.serial_id, A.name, A.singer, A.path, A.original_track, A.sound_track, A.start_volume_1, A.start_volume_2, A.lyric, A.prelude,A.match, B.name AS effect, C.name AS version 
        FROM media A 
        LEFT JOIN media_effect B ON A.effect = B.id 
        LEFT JOIN media_version C ON A.version=C.id 
        WHERE A.enabled=1 AND RAND()<=0.00009 limit 0,10");
    foreach ($add_result as $row) {
        $song = formatSongResult($row);
        array_push($songs, $song);
        if (count($songs) == 5) break;
    }
}

//when the singer get no enough songs, add songs from host list
if (count($songs) < 5){
    $add_result = $mdb->query(
        "SELECT B.mid, B.serial_id, B.name, B.singer, B.path, B.original_track, B.sound_track, B.start_volume_1, B.start_volume_2, B.lyric, B.prelude,B.match, C.name AS effect, D.name AS version 
        FROM media_list A 
        LEFT JOIN media B ON A.mid = B.mid 
        LEFT JOIN media_effect C ON B.effect = C.id 
        LEFT JOIN media_version D ON B.version=D.id 
        WHERE B.enabled = 1 AND A.type=%s", "hot");
    foreach ($add_result as $row) {
        $song = formatSongResult($row);
        array_push($songs, $song);
        if (count($songs) == 5) break;
    }
}

$finalResult = array(
    "songs" => $songs
);

formatResult($finalResult);
?>