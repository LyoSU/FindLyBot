<?
chdir(__DIR__);
ini_set("log_errors", 1);
ini_set("error_log", "error.log");

require 'rb.php';

$dbname = '';
$tablename = '';
$password = '';

R::setup( 
	"mysql:host=localhost;dbname=$dbname",
	$tablename,
	$password
);

if ( !R::testConnection() )
{
	header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 300');
	exit ('Нет соединения с базой данных');
}

$tg_token = ''; // Telegram токен бота
$server = 'https://api.telegram.org/bot'.$tg_token; // Адрес сервера API Telegram