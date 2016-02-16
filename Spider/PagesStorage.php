<?php
namespace Spider;
use \Amp\Artax\Uri;

class PagesStorage
{
	public $fileName;
	private $pages = array();

	private static $instances = array();

	private function __construct($file)
	{
		$this->fileName = $file;
	}

	public static function getInstance($file) 
	{
		if(!isset(self::$instances[$file])) self::$instances[$file] = new self($file);

		return self::$instances[$file];
	}

	public function load()
	{
		if(is_file($this->fileName))
			$this->pages = unserialize(file_get_contents($this->fileName));
	}

	public function save()
	{
		foreach($this->pages as $page)
			if($page->status == Statuses::IN_PROGRESS) $page->status = Statuses::PENDING;
		file_put_contents($this->fileName, serialize($this->pages));
	}

	public function get($uri)
	{
		if(is_string($uri)) $uri = new Uri($uri);
		$uri = preg_replace('{#.*$}', '', (string)$uri);
		if(!isset($this->pages[$uri])) $this->pages[$uri] = new Page($uri);
		return $this->pages[$uri];
	}

	public function getNew()
	{
		foreach($this->pages as $page)
			if($page->status == Statuses::PENDING) return $page;
		return false;
	}

	public function getInProgressCount()
	{
		$i = 0;
		foreach($this->pages as $page) {
			if($page->status == Statuses::IN_PROGRESS) ++$i;
			// echo $page->uri, ' ', $page->status, PHP_EOL;
		}
		// var_dump($this->pages);
		return $i;
	}

	public function dump()
	{
		foreach($this->pages as $page) {
			echo "$page->uri $page->status $page->comment\n";
			foreach($page->linkedFrom as $link=>$count) echo "\t$link $count\n";
		}
	}

	public function dump2()
	{
		foreach($this->pages as $page) {
			if(!strpos((string)$page->uri, 'amp')) continue;
			echo "$page->uri $page->status $page->comment\nСсылки на страницах:\n";
			foreach($page->linkedFrom as $link=>$count) echo "\t$link $count\n";
			echo PHP_EOL;
		}
	}

	public function stats()
	{
		$all = 0;
		$checked = 0;
		foreach($this->pages as $page) {
			if($page->status != Statuses::PENDING && $page->status != Statuses::IN_PROGRESS) ++$checked;
			++$all;
		}
		return $checked . '/' . ($all) . ' (' . number_format(($checked / $all) * 100, 2) . '%)';
	}

	public function printH1()
	{
		foreach($this->pages as $page) 
			if($page->status == '200' && stripos($page->contentType, 'text') !== false)
				echo $page->uri, "\t", $page->h1, PHP_EOL;
	}

	public function printTitle()
	{
		foreach($this->pages as $page) 
			if($page->status == '200' && stripos($page->contentType, 'text') !== false)
				echo $page->uri, "\t", $page->title, PHP_EOL;
	}
}