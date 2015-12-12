<?php
header("content-type:application/json");

define("VERSION", "2.1");
define("BUILD", "20150716");
define("ENCRYPTION_KEY", "!@#$%^&*chmdeploy");

$config = include dirname(__FILE__).'/../config.php';
//mysql configuration
define("DB_HOST", $config["DB_HOST"]);
define("DB_USERNAME", $config["DB_USERNAME"]);
define("SPHINX_HOST",$config['SPHINX_HOST']);
define("SPHINX_PORT",$config['SPHINX_PORT']);
//deal with db password

// if (substr($config["DB_PASSWORD"], -1) == 'X'){
	// define("DB_PASSWORD", decrypt(substr($config["DB_PASSWORD"], 0, -1), ENCRYPTION_KEY));
// } else {
	// define("DB_PASSWORD", $config["DB_PASSWORD"]);
	//Disable Rewrite Password
	
	// $config["DB_PASSWORD"] = encrypt($config["DB_PASSWORD"], ENCRYPTION_KEY)."X";
	// file_put_contents(dirname(__FILE__).'/../config.php', '<?php return ' . var_export($config, true) . ';? >');
// }

if(preg_match("/#LEN\d{4}#/i", base64_decode($config["DB_PASSWORD"]))){
	define("DB_PASSWORD", xor_decode($config["DB_PASSWORD"], ENCRYPTION_KEY));
} else {
	define("DB_PASSWORD", $config["DB_PASSWORD"]);
	// Disable Rewrite Password
	$config["DB_PASSWORD"] = xor_encode($config["DB_PASSWORD"], ENCRYPTION_KEY);
	file_put_contents(dirname(__FILE__).'/../config.php', "<?php \n return " . var_export($config, true) . ';?>');
	
}
//define("DB_PASSWORD", $config['DB_PASSWORD']);
define("DB_TABLE_NAME", $config["DB_TABLE_NAME"]);
define("DB_PORT", $config["DB_PORT"]);
define("DB_CHARSET", $config["DB_CHARSET"]);
//host configuration
define("PHP_HOST", $config["PHP_HOST"]);
define("SAVE_LOG", $config['SAVE_LOG']);
define("REDIS_HOST", $config['REDIS_HOST']);
define("REDIS_PORT", $config['REDIS_PORT']);

if (isset($_GET['v']) && intval($_GET['v']) == 1314){
	echo 'version '.VERSION.'('.BUILD.')  \nCopyright 2013-2016 Lohas Co., Ltd., All rights reserved.';
} else if (isset($_GET['author']) && strval($_GET['author']) == 'kaola'){
	echo base64_decode("QnVpbGQgQnkg56iL5oWn5piOKHNvODk4KQ==");
}

$values = $_POST;

foreach ($values as &$value) {
	$search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
    $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");
    $value = str_replace($search, $replace, $value);
}

//function start here
/**
 * Returns an encrypted & utf8-encoded
 */
function encrypt($pure_string, $encryption_key) {
    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv);
    return $encrypted_string;
}

/**
 * Returns decrypted original string
 */
function decrypt($encrypted_string, $encryption_key) {
    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);
    return $decrypted_string;
}

function xor_encode($str,$key){
	$key=md5($key);
	$k = '#LEN'.str_pad(strlen($str),4,0,STR_PAD_LEFT).'#';
	$tmp="";
	for($i=0;$i<strlen($str);$i++){
	   	$tmp.=substr($str,$i,1) ^ substr($key,$i%strlen($key),1);
	}
	return base64_encode($k.$tmp);
}

function xor_decode($str,$key){
	$len=strlen($str);
	$key=md5($key);
	$str=base64_decode($str);
	$str=substr($str,9,$len-9);
	$tmp="";
	for($i=0;$i<strlen($str);$i++){
		$tmp.=substr($str,$i,1) ^ substr($key,$i%strlen($key),1);
	}    
	return $tmp;
} 

function formatResult($finalResult){
	global $timeStart;
	echo json_encode(array(
		"result" => $finalResult,
		"status" => true,
		"error" => ""
		)
	);
	
	if(SAVE_LOG) {
		saveLog($GLOBALS['timeStart'], $_SERVER['REQUEST_URI']);
		unset($GLOBALS['timeStart']);
	}
}

function saveLog($timeStart, $page) {
	if(SAVE_LOG) {
		$t = microtime(true)-$timeStart;
		$str = PHP_EOL .date("Y-m-d H:i:s").' '. sprintf('%01.17f', $t) . ' -> ' . $page . PHP_EOL;
		$str .= '--------------------------------------------------------' . PHP_EOL;
		saveFile($str);
	}
}

function saveSqlLog($sqlTimeStart, $sql) {
	if(SAVE_LOG) {
		$t = microtime(true)-$sqlTimeStart;
		$str = date("Y-m-d H:i:s").' '.sprintf('%01.17f', $t) . ' -> ' . $sql . PHP_EOL;
		saveFile($str);
	}
}

function saveFile( $text) {
	if(SAVE_LOG) {
		$fileName = date('Y-m-d').'.log';
		if(!is_dir(dirname(__FILE__).'/../../../Log/phpLog/')){
			@mkdir(dirname(__FILE__).'/../../../Log/phpLog/',0777,true);
		}
		if($fp = fopen(dirname(__FILE__).'/../../../Log/phpLog/' . $fileName, 'a')) {
			if(@fwrite($fp, $text)) {
				@fclose($fp);
			} else {
				@fclose($fp);
			}
		}
	}
}
//for media
function formatSongResult($song){
	if ($song)
		return array(
			"mid" => $song['mid'],
			"serialid" => $song['serial_id'],
			"name" => $song['name'],
			"singer" => $song['singer'],
			"path" => $song['path'],
			"orginal" => $song['original_track'],
			"accompaniment" => $song['sound_track'],
			"svolume1" => $song['start_volume_1'],
			"svolume2" => $song['start_volume_2'],
			"match" => $song['match'],
			"lyric" => $song['lyric'],
			"prelude" => $song['prelude'],
			"effect" => $song['effect'],
			"version" => $song['version'],
			"singer_id" => isset($song['singer_id']) ? $song['singer_id'] : '',
		);
	else
		return array();
}

function formatSongsResult($results){
	$songs = array();
	foreach ($results as $row) {
		array_push($songs, formatSongResult($row));
	}
	return $songs;
}

//for music
function formatMusicResult($song){
	if ($song)
		return array(
			"mmid" => $song['mmid'],
			"serialid" => $song['serial_id'],
			"name" => $song['name'],
			"singer" => $song['singer'],
			"path" => $song['path'],
			"lyric" => $song['lyric'],
			"prelude" => $song['prelude']
		);
	else
		return array();
}

function formatMusicsResult($results){
	$songs = array();
	foreach ($results as $row) {
		array_push($songs, formatMusicResult($row));
	}
	return $songs;
}

//for singer
$singerType = array (
  0 => 'male', 				//大陆男歌星
  1 => 'female', 			//大陆女歌星
  2 => 'othermale', 		//港台男歌星
  3 => 'otherfemale', 	//港台女歌星
  4 => 'foreigner', 		//欧美日韩所有
  5 => 'chinagroup', 		//大陆港台组合
  6 => 'othergroup', 		//非大陆港台组合
  7 => 'other', 				//其它国家或地区
  8 => 'mainlangroup', 	//大陆组合
  9 => 'hktwgroup',		//港台组合
  10 => 'jpskmale',		//日韩男歌星
  11 => 'jpskfemale',		//日韩女歌星
  12 => 'jpskgroup',		//日韩组合
  13 => 'euusmale',		//欧美男歌星
  14 => 'euusfemale',	//欧美女歌星
  15 => 'euusgroup'		//欧美组合
);

function changeType2NSSQL($type){
	global $singerType;
	switch ($type) {
		case $singerType[0]:
			return ' AND nation=1 AND sex=1';
			break;
		case $singerType[1]:
			return ' AND nation=1 AND sex=2';
			break;
		case $singerType[2]:
			return ' AND nation=2 AND sex=1';
			break;
		case $singerType[3]:
			return ' AND nation=2 AND sex=2';
			break;
		case $singerType[4]:
			return ' AND (nation=3 OR nation=4)';
			break;
		case $singerType[5]:
			return ' AND (nation=1 OR nation=2) AND sex=3';
			break;
		case $singerType[6]:
			return ' AND nation!=1 AND nation!=2 AND sex=3';
			break;
		case $singerType[7]:
			return ' AND nation=5';
			break;
		case $singerType[8]:
			return ' AND nation=1 AND sex=3';
			break;
		case $singerType[9]:
			return ' AND nation=2 AND sex=3';
			break;
		case $singerType[10]:
			return ' AND nation=4 AND sex=1';
			break;
		case $singerType[11]:
			return ' AND nation=4 AND sex=2';
			break;
		case $singerType[12]:
			return ' AND nation=4 AND sex=3';
			break;
		case $singerType[13]:
			return ' AND nation=3 AND sex=1';
			break;
		case $singerType[14]:
			return ' AND nation=3 AND sex=2';
			break;
		case $singerType[15]:
			return ' AND nation=3 AND sex=3';
			break;
		default:
			return '';
	}
}

function changeNS2Type($nation, $sex){
	global $singerType;
	if ($nation==1 && $sex==1){
		return $singerType[0];
	} else if ($nation==1 && $sex==2){
		return $singerType[1];
	} else if ($nation==2 && $sex==1){
		return $singerType[2];
	} else if ($nation==2 && $sex==2){
		return $singerType[3];
	} else if (($nation==3 || $nation==4) && $sex<>3){
		return $singerType[4];
	} else if (($nation==1 || $nation==2) && $sex==3){
		return $singerType[5];
	} else if (($nation==3 || $nation==4 || $nation==5) && $sex==3){
		return $singerType[6];
	} else {
		return $singerType[7];
	}
}

function formatArtistResult($artist){
	if ($artist){
		return array(
			"sid" => $artist['sid'],
			"serialid" => $artist['serial_id'],
			"name" => $artist['name'],
			"type" => changeNS2Type($artist['nation'], $artist['sex']),
			"sex" => $artist['sexType'],
			"stars" => $artist['stars'],
			"songCount" => $artist['song_count'],
			"baiwei_recommend" => isset($artist['baiwei_recommend']) ? $artist['baiwei_recommend'] : '' ,
		);
	} else
		return array();
}

function formatArtistsResult($result){
	$artists = array();
	foreach ($result as $row) {
		array_push($artists, formatArtistResult($row));
	}
	return $artists;
}

?>
