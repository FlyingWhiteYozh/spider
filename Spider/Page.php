<?php
namespace Spider;

use \Amp\Uri\Uri;

class Page
{
    public $uri,
    $status     = Statuses::PENDING,
    $comment    = '',
    $linkedFrom = array(),
    $contentType,
    $title,
    $description,
    $keywords,
    $h1,
        $canonical;

    public function __construct($uri)
    {
        $idn = new \idna_convert(array('idn_version' => 2008));
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        if (preg_match('{[а-я]}ui', $uri->getAuthority())) {
            $uri = str_replace($uri->getAuthority(), $idn->encode($uri->getAuthority()), (string) $uri);
            $uri = new Uri($uri);
        }

        $this->uri = $uri;
    }

    public function skip($reason)
    {
        $this->status  = Statuses::SKIPPED;
        $this->comment = $reason;
    }

    public function addLinkIn($uri)
    {
        $uri = (string) $uri;
        if (!isset($this->linkedFrom[$uri])) {
            $this->linkedFrom[$uri] = 0;
        }

        $this->linkedFrom[$uri]++;
    }
}
