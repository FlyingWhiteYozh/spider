<?php
namespace Spider;
use \Amp\Artax\Uri;
require __DIR__ . '/config.php';

class DumpGadgets extends PagesStorage
{
	public function dump4xx() {
		foreach ($this->pages as $page) {
			if (strpos($page->title, 'не найд') === false) {
				continue;
			}

			echo "$page->uri Статус: $page->status $page->comment\nСсылки на страницах:\n";
			$i = 0;
			foreach ($page->linkedFrom as $link => $count) {
				if (++$i > 10) {
					echo 'И ' . (count($page->linkedFrom) - 10) . ' других. ' . PHP_EOL;
					break;
				}
				$count = $count > 1 ? $count : '';
				echo "\t$link $count\n";
			}
			echo PHP_EOL;
		}
	}

}
$pages = DumpGadgets::getInstance(SAVE_FILE);
$pages->load();
$pages->dump4xx();
