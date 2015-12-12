<?php
// header("content-type:application/json");
// require_once '../tools/db.php';
// require_once '../tools/main.php';
// $mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);
define("CHECK_ON",1);
$db_check = include_once("db.php");
//
echo "PHP AUTO_CHECK RESULT:".PHP_EOL;
if($db_check){
	exit($db_check);
}
echo "DB OK".PHP_EOL;
$interface_check = include_once("interface.php");
echo $interface_check;
if($interface_check){
	exit($interface_check);
}

echo "Interface OK".PHP_EOL;