<?php
namespace Spider;

class Spider
{
    private $pages, $authority, $filters = array();
    public function __construct($startingPage)
    {
        echo 'It works!', PHP_EOL;
        $this->pages     = PagesStorage::getInstance(SAVE_FILE);
        $page            = $this->pages->get($startingPage);
        $this->authority = $page->uri->getAuthority();
        $client          = new \Amp\Artax\Client;
        $client->setOption(\Amp\Artax\Client::OP_CRYPTO, [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]);
        $promise = $client->request((string) $page->uri->resolve('/robots.txt'));
        // var_dump($page->uri->resolve('/robots.txt'));
        $response     = \Amp\wait($promise);
        $this->robots = $robots = new Robots($response->getBody());
    }

    public function addFilter($pattern)
    {
        $this->filters[] = $pattern;
    }

    private function shouldSkip($page)
    {
        if ($page->uri->getAuthority() != $this->authority) {
            return stripos($page->uri->getAuthority(), $this->authority) ? 'subdomain' : 'external';
        }

        if ($rule = $this->robots->isDisallowed($page->uri->getPath() . ($page->uri->getQuery() ? '?' . $page->uri->getQuery() : ''))) {
            return 'robots.txt:' . $rule;
        }

        if (!in_array($page->uri->getScheme(), array('http', 'https'))) {
            return 'not_http';
        }

        foreach ($this->filters as $filter) {
            if (preg_match('{' . $filter . '}i', (string) $page->uri)) {
                return 'custom_filter:' . $filter;
            }
        }

    }

    public function addUri($uri)
    {
        $page = $this->pages->get($uri);
        if ($page->status == Statuses::PENDING) {
            if ($reason = $this->shouldSkip($page)) {
                $page->skip($reason);
            }

        }
        return $page;
    }

    public function check()
    {
        $t      = microtime(true);
        $client = new \Amp\Artax\Client;
        foreach ($this->pages->getNew() as $page) {
            \Amp\wait($client->request((string) $page->uri)->when(function ($error, $result) {
                // var_dump($error, $result);
                $dom = \str_get_html($result->getBody());
                echo $dom->find('h1')[0]->text(), PHP_EOL;
            }));
        }
        echo microtime(true) - $t, PHP_EOL;
    }

    public function checkAsync()
    {
        \Amp\run(function () {
            $client = new \Amp\Artax\Client;
            $client->setOption(\Amp\Artax\Client::OP_HOST_CONNECTION_LIMIT, 20);
            // $client->setOption(\Amp\Artax\Client::OP_VERBOSITY, \Amp\Artax\Client::VERBOSE_ALL);
            $client->setOption(\Amp\Artax\Client::OP_FOLLOW_LOCATION, true);
            $client->setOption(\Amp\Artax\Client::OP_CRYPTO, [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]);

            \Amp\Repeat(function () use ($client) {
                pcntl_signal_dispatch();
                $page = $this->pages->getNew();
                if (!$page) {
                    if (!$this->pages->getInProgressCount()) {
                        \Amp\stop();
                    }

                    return;
                }
                $page->status = Statuses::IN_PROGRESS;
                // echo 'Checking "' . (string) $page->uri . '"' . PHP_EOL;
                $client->request((string) $page->uri)->when(function ($error, $result) use ($page) {
                    // echo 'Done with ' . $page->uri . PHP_EOL;
                    // var_dump($error, $result);
                    if ($error != null) {
                        $page->status  = Statuses::ERROR;
                        $page->comment = get_class($error);
                        return;
                    }
                    $page->status = $this->getOriginalResponse($result)->getStatus();
                    $ctype        = $result->getHeader('Content-Type');
                    if (isset($ctype[0])) {
                        $page->contentType = $ctype[0];
                    }
                    if ($page->status == 200 && preg_match('{text/html}', $page->contentType)) {

                        $dom = \str_get_html($result->getBody());
                        if (!is_object($dom)) {

                            var_dump((string) $page->uri, $result->getBody());
                            die;
                        }
                        $h1 = $dom->find('h1');
                        if (isset($h1[0])) {
                            $page->h1 = $h1[0]->text();
                        }

                        $title = $dom->find('title');
                        if (isset($title[0])) {
                            $page->title = $title[0]->text();
                        }

                        $description = $dom->find('meta [name="description"]');
                        if (isset($description[0])) {
                            $page->description = $description[0]->content;
                        }

                        $keywords = $dom->find('meta [name="keywords"]');
                        if (isset($keywords[0])) {
                            $page->keywords = $keywords[0]->content;
                        }

                        $canonical = $dom->find('link [rel="canonical"]');
                        if (isset($canonical[0])) {
                            $page->canonical = $canonical[0]->href;
                        }

                        $base = $dom->find('base [href]');
                        if (isset($base[0])) {
                            $page->base = new \Amp\Artax\Uri($base[0]->href);
                        } else {
                        	$page->base = NULL;
                        }

                        foreach ($dom->find('a') as $a) {
                            if (empty($a->href) || $a->href[0] == '#' || $a->href == 'http://' || (!empty($a->rel) && $a->rel == 'nofollow') || stripos($a->href, 'javascript') === 0) {
                                continue;
                            }

                            try {
                                $uri = $page->base ? $page->base->resolve($a->href) : $page->uri->resolve($a->href);
                            } catch (\Exception $e) {
                                var_dump($e->getMessage(), $e->getTraceAsString(), $page->uri, $a->href);
                                die;
                            }
                            if (!$uri->getScheme() || ($uri->getScheme() != 'http' && $uri->getScheme() != 'https')) {
                                continue;
                            }

                            $newPage = $this->addUri($uri);
                            $newPage->addLinkIn($page->uri);
                        }
                    }
                });
            }, 50);

            \Amp\Repeat(function () {
                echo "\r", $this->pages->stats();
            }, 1000);
            \Amp\Repeat(function () {
                echo "\r", $this->pages->save();
            }, 30000);
        });
    }

    public function getOriginalResponse(\Amp\Artax\Response $response)
    {
        if (empty($response->previousResponse)) {
            return $response;
        }

        $current = $response;
        while ($current = $current->getPreviousResponse()) {
            $originalResponse = $current;
        }

        return $originalResponse;
    }

    public function load()
    {
        $this->pages->load();
    }
}
