<?php
namespace Spider;
use \Amp\Uri\Uri;
require __DIR__ . '/config.php';

/*$u = new Uri('http://petscage.ru/#123');
echo $u->normalize(),PHP_EOL;
echo (string)$u,PHP_EOL;
die;*/

$shutdown = function ($signo = false) {
	static $done = false;
	if($done) return;
	$done = true;
	$saveFile = defined('CUSTOM_SAVE_FILE') ? CUSTOM_SAVE_FILE : SAVE_FILE;
	$pages = PagesStorage::getInstance($saveFile);
	$pages->save();
	// $pages->dump();
	echo "\r", $pages->stats(), PHP_EOL;
	echo 'Done',PHP_EOL;
	if(in_array($signo, [SIGTERM, SIGINT])) {
		$GLOBALS['spider']->stop = true;
	}
};

pcntl_signal(SIGINT,  $shutdown);

register_shutdown_function($shutdown);

function argv()
{
	static $i = 1;
	return $_SERVER['argv'][$i++] ?? null;
}

$spider = new Spider();

// $spider->addUri('http://petscage.ru/');
// $spider->addUri('http://petscage.ru/category/kleti-dlya-malenkij-ptic/');
/*$u = new Uri('http://petscage.ru/');
echo $u->resolve('/qwe');
echo (string)$u,PHP_EOL;*/

// $spider->check();
$spider->load();

$spider->addFilter('brand=');
$spider->addFilter('(jpe?g|png|pdf|gif|swf)($|\?)');
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
$spider->addFilter('&amp;page=');
$spider->addFilter('%2F');
$spider->addFilter('print=');
$spider->addFilter('%3B');
// $spider->addFilter('\?(?!page)=');
$spider->addFilter('/{3,}');
$spider->addFilter('/wishlist');
$spider->addFilter('order');
$spider->addFilter('social');
$spider->addFilter('utm_');

while($arg = argv()) {
	echo $arg, PHP_EOL;
	if (strpos($arg, 'http') === 0) {
		$spider->reset();
		$spider->startFrom($arg);
	} elseif ($arg == 'r') {
		$spider->retry();
	} elseif (ctype_digit($arg)) {
		$spider->max = $arg;
	} elseif (is_file($arg) || $arg == 'c') {
		if ($arg == 'c') {
			$arg = argv();
			touch($arg);
		}
		define('CUSTOM_SAVE_FILE', $arg);
		$spider->changeSave($arg);
	}
}

$spider->checkAsync();
// var_dump($spider);

