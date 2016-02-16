<?php
namespace Spider;
define('SAVE_FILE', __DIR__.'/pages.szd');

spl_autoload_register(function ($pClassName) {
    include(__DIR__ . "/" . str_replace('\\', '/', $pClassName) . ".php");
});
require __DIR__ . '/vendor/autoload.php';
