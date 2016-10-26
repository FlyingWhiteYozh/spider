<?php
namespace Spider;
use \Amp\Artax\Uri;
require __DIR__ . '/config.php';

/*$u = new Uri('http://petscage.ru/#123');
echo $u->normalize(),PHP_EOL;
echo (string)$u,PHP_EOL;
die;*/

$shutdown = function ($signo = false) {
	static $done = false;
	if($done) return;
	$done = true;

	$pages = PagesStorage::getInstance(SAVE_FILE);
	$pages->save();
	// $pages->dump();
	echo 'Done',PHP_EOL;
	if($signo == SIGINT) exit;
};

pcntl_signal(SIGINT,  $shutdown);

register_shutdown_function($shutdown);

$spider = new Spider('http://www.sendflowers.ru/');

// $spider->addUri('http://petscage.ru/');
// $spider->addUri('http://petscage.ru/category/kleti-dlya-malenkij-ptic/');
/*$u = new Uri('http://petscage.ru/');
echo $u->resolve('/qwe');
echo (string)$u,PHP_EOL;*/

// $spider->check();
$spider->load();

$spider->addFilter('(jpe?g|png|pdf|gif)$');
$spider->addFilter('brand=');
$spider->addFilter('filter=');
$spider->addFilter('set_filter=');
$spider->addFilter('sort=');
$spider->addFilter('count=');
$spider->addFilter('send_if_quant');
$spider->addFilter('per_page=');
$spider->addFilter('ADD2BASKET');
$spider->addFilter('price=');
$spider->addFilter('addToCompare');
$spider->addFilter('www.youtube.com');
$spider->addFilter('banners/go_to');
$spider->addFilter('compare/');
$spider->addFilter('emarket/');

$spider->checkAsync();
// var_dump($spider);

