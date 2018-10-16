<?php
namespace Spider;
use \Amp\Artax\Uri;
require __DIR__ . '/config.php';

$pages = PagesStorage::getInstance(SAVE_FILE);
$pages->load();
$pages->dump4xxByPage();
