<?php
if (isset($_REQUEST["header"]) && $_REQUEST["header"]){
	$sphinx_index = 'media_header';
	$key_word = trim($_REQUEST["header"]);
} else if (isset($_REQUEST["pinyin"]) && $_REQUEST["pinyin"]){
	$sphinx_index = 'media_pinyin';
	$key_word = trim($_REQUEST["pinyin"]);
} else if (isset($_REQUEST["name"]) && $_REQUEST["name"]){
	$sphinx_index = 'media_name';
	$key_word = trim($_REQUEST["name"]);
}else{
	include_once('_songs.php');
	exit;
}