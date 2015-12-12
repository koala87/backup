<?php 
if(!defined("CHECK_ON"))exit;
require_once '../tools/db.php';
require_once '../tools/main.php';
header("Content-type: text/html; charset=utf-8"); 
$mdb = new MeekroDB(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_TABLE_NAME, DB_PORT, DB_CHARSET);

$ret_str = "";
$check_list = array(
				// array(	
				// 	"table_name"	=>	"media",
				// 	"check_field"	=>	array('field_1','field_2')
				// 	),
				// array(	
				// 	"table_name"	=>	"media",
				// 	"check_field"	=>	array('field_1','field_2')
				// 	)
				);

if($check_list){
	foreach ($check_list as $key => $check_table) {
		$ret = $mdb->query("SHOW COLUMNS FROM {$check_table['table_name']}");
		if($ret){
			$field_arr = array();
			foreach ($ret as $key => $value) {
				array_push($field_arr,$value['Field']);
			}
			foreach ($check_table['check_field'] as $key => $value) {
				if(!in_array($value, $field_arr))
					$ret_str .= "数据表{$check_table['table_name']}缺少字段：{$value}".PHP_EOL;
			}
		}
	}
}

return $ret_str;