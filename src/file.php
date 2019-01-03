<?
require 'BotLy.php';

$file_path = Ly::sendMethod ( 'getFile', ['file_id' => $_GET['file_id'] ])['result']['file_path'];

header('Content-type: attachment');
header('Content-Transfer-Encoding: binary');
readfile( "https://api.telegram.org/file/bot$tg_token/$file_path" );