<?php
namespace Spider;
use \Amp\Artax\Uri;

class PagesStorage {
	public $fileName;
	private $pages = array();

	private static $instances = array();

	private function __construct($file) {
		$this->fileName = $file;
	}

	public static function getInstance($file) {
		if (!isset(self::$instances[$file])) {
			self::$instances[$file] = new self($file);
		}

		return self::$instances[$file];
	}

	public function load() {
		if (is_file($this->fileName)) {
			$this->pages = unserialize(gzdecode(file_get_contents($this->fileName)));
		}

	}

	public function save() {
		foreach ($this->pages as $page) {
			if ($page->status == Statuses::IN_PROGRESS) {
				$page->status = Statuses::PENDING;
			}
		}

		file_put_contents($this->fileName, gzencode(serialize($this->pages)));
	}

	public function get($uri) {
		if (is_string($uri)) {
			$uri = new Uri($uri);
		}

		$uri = preg_replace('{#.*$}', '', (string) $uri);
		if (!isset($this->pages[$uri])) {
			$this->pages[$uri] = new Page($uri);
		}

		return $this->pages[$uri];
	}

	public function getNew() {
		foreach ($this->pages as $page) {
			if ($page->status == Statuses::PENDING) {
				return $page;
			}
		}

		return false;
	}

	public function getInProgressCount() {
		$i = 0;
		foreach ($this->pages as $page) {
			if ($page->status == Statuses::IN_PROGRESS) {
				++$i;
			}

			// echo $page->uri, ' ', $page->status, PHP_EOL;
		}
		// var_dump($this->pages);
		return $i;
	}

	public function dump() {
		foreach ($this->pages as $page) {
			echo "$page->uri $page->status $page->comment\n";
			foreach ($page->linkedFrom as $link => $count) {
				echo "\t$link $count\n";
			}

		}
	}

	public function dump2() {
		foreach ($this->pages as $page) {
			if (!strpos((string) $page->uri, 'amp')) {
				continue;
			}

			echo "$page->uri $page->status $page->comment\nСсылки на страницах:\n";
			foreach ($page->linkedFrom as $link => $count) {
				echo "\t$link $count\n";
			}

			echo PHP_EOL;
		}
	}

	public function dump4xx() {
		foreach ($this->pages as $page) {
			if (substr($page->status, 0, 1) != 4) {
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

	public function dump3xx() {
		foreach ($this->pages as $page) {
			if (substr($page->status, 0, 1) != 3) {
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

	public function dumpExternal() {
		$static = '';
		$dynamic = '';
		foreach ($this->pages as $page) {
			if (preg_match('{(jpe?g|png|pdf|gif)$}i', $page->uri)) {
				$out = &$static;
			} else {
				$out = &$dynamic;
			}

			if ($page->status != Statuses::SKIPPED || $page->comment != 'external') {
				continue;
			}

			$out .= "$page->uri \nСсылки на страницах:\n";
			$i = 0;
			foreach ($page->linkedFrom as $link => $count) {
				if (++$i > 10) {
					$out .= 'И ' . (count($page->linkedFrom) - 10) . ' других. ' . PHP_EOL;
					break;
				}
				$count = $count > 1 ? $count : '';
				$out .= "\t$link $count\n";
			}
			$out .= PHP_EOL;
		}
		echo 'Внешние ссылки:' . PHP_EOL . $dynamic;
		echo 'Внешние ресурсы:' . PHP_EOL . $static;
	}

	public function dumpTA() {
		$this->dumpExternal();

		echo 'Битые ссылки: ' . PHP_EOL;
		$this->dump4xx();

		echo 'Ссылки через редиректы: ' . PHP_EOL;
		$this->dump3xx();
	}

	public function dumpMeta() {
		foreach ($this->pages as $page) {
			if (substr($page->status, 0, 1) != 2) {
				continue;
			}

			echo "$page->uri\t'$page->title'\t'$page->description'\t'$page->keywords'\t'$page->h1'";
			
			echo PHP_EOL;
		}

	}

	public function genSitemapXml() {
		$f = fopen('/home/yaroslav/scripts/sitemap.xml', 'w');
		fwrite($f, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
		fwrite($f, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);

		foreach ($this->pages as $page)
			if (stripos($page->contentType, 'text') !== false)
				fwrite($f, '<url><loc>' . $page->uri . '</loc></url>' . PHP_EOL);

		fwrite($f, '</urlset>' . PHP_EOL);

	}

	public function stats() {
		$all = 0;
		$checked = 0;
		foreach ($this->pages as $page) {
			if ($page->status != Statuses::PENDING && $page->status != Statuses::IN_PROGRESS) {
				++$checked;
			}

			++$all;
		}
		return $checked . '/' . ($all) . ' (' . number_format(($checked / $all) * 100, 2) . '%)';
	}

	public function printH1() {
		foreach ($this->pages as $page) {
			if ($page->status == '200' && stripos($page->contentType, 'text') !== false) {
				echo $page->uri, "\t", $page->h1, PHP_EOL;
			}
		}

	}

	public function printTitle() {
		foreach ($this->pages as $page) {
			if ($page->status == '200' && stripos($page->contentType, 'text') !== false) {
				echo $page->uri, "\t", $page->title, PHP_EOL;
			}
		}

	}
	public function printCanonical() {
		foreach ($this->pages as $page) {
			if ($page->status == '200' && stripos($page->contentType, 'text') !== false && $page->canonical) {
				echo $page->uri, "\t", $page->canonical, PHP_EOL;
			}
		}

	}
}