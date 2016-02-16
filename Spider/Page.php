<?php
namespace Spider;
use \Amp\Artax\Uri;

class Page
{
	public 
		$uri, 
		$status = Statuses::PENDING, 
		$comment = '',
		$linkedFrom = array(),
		$contentType,
		$title,
		$description,
		$keywords,
		$h1;

	public function __construct($uri)
	{
		if(is_string($uri)) $uri = new Uri($uri);

		$this->uri = $uri;
	}

	public function skip($reason)
	{
		$this->status = Statuses::SKIPPED;
		$this->comment = $reason;
	}

	public function addLinkIn($uri)
	{
		$uri = (string)$uri;
		if(!isset($this->linkedFrom[$uri])) $this->linkedFrom[$uri] = 0;
		$this->linkedFrom[$uri]++;
	}
}