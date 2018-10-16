<?php
namespace Spider;

use \Amp\Artax\DefaultClient;
use \Amp\Loop;
use \Amp\Uri\Uri;

class Spider
{
    private $pages, $authority, $filters = array(), $idn;

    private function getClient()
    {
        static $client = null;
        if (!$client instanceof DefaultClient) {
            $cookie = new \Amp\Artax\Cookie\ArrayCookieJar;
            $cookie->store(new \Amp\Artax\Cookie\Cookie('flying_white_key', 'd49b43fce5e7919491d544c29ed4e2def6099ccf', null, '/', '.ru'));
            $client = new DefaultClient($cookie);
            $client->setOption(DefaultClient::OP_TRANSFER_TIMEOUT, 0);
            $client->setOption(DefaultClient::OP_MAX_REDIRECTS, 10);
            $client->setOption(DefaultClient::OP_DEFAULT_HEADERS, [
                'User-Agent'         => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0',
                'X-Flying-White-Key' => 'd49b43fce5e7919491d544c29ed4e2def6099ccf',
            ]);
        }
        return $client;
    }

    public function __construct()
    {
        echo 'It works!', PHP_EOL;
        $this->idn   = new \idna_convert(array('idn_version' => 2008));
        $this->pages = PagesStorage::getInstance(SAVE_FILE);

    }

    public function changeSave($saveFile)
    {
        $this->pages = PagesStorage::getInstance($saveFile);
        $this->load();
    }

    public function startFrom($startingPage)
    {
        // echo 'Starting from ' . $startingPage, PHP_EOL;
        $page    = $this->pages->get($startingPage);
        $client  = $this->getClient();
        $promise = $client->request((string) $page->uri->resolve('/robots.txt'));
        // var_dump($page->uri->resolve('/robots.txt'));
        $response        = \Amp\Promise\wait($promise);
        $robots          = \Amp\Promise\wait($response->getBody());
        $this->authority = $this->idn->decode($page->uri->getAuthority());
        $this->robots    = new Robots($robots);
    }

    public function load()
    {
        $this->pages->load();
        if ($page = $this->pages->get()) {
            $this->startFrom((string) $page->uri);
        }

    }

    public function reset()
    {
        $this->pages->reset();
    }

    public function addFilter($pattern)
    {
        $this->filters[] = $pattern;
    }

    private function shouldSkip($page)
    {
        if ($this->idn->decode($page->uri->getAuthority()) != $this->authority) {
            return stripos(ltrim($page->uri->getAuthority(), 'www.'), ltrim($this->authority, 'www.')) !== false ? 'subdomain' : 'external';
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

    public function getHandler()
    {
        $client = $this->getClient();
        return \Amp\coroutine(function ($url, $formBody = null) use ($client) {
            if ($formBody) {
                $request = (new Request($url, 'POST'))->withBody($formBody);
            } else {
                $request = $url;
            }
            $response = yield $client->request($request);
            //$body     = yield $response->getBody();

            return $response;
        });
    }

    public function retry()
    {
        $this->pages->retry();
        $this->max = 5;
    }

    public $max        = 10;
    public $stop       = false;
    public $semaphore  = null;
    public $inProgress = 0;
    public function checkAsync()
    {
        echo 'Loop started' . PHP_EOL;
        $this->stop = false;
        try {
            Loop::run(function () {
                $this->semaphore = new Semaphore($this->max);
                $client          = $this->getClient();

                Loop::repeat(100, function () {
                    echo "\rInProgress: " . $this->inProgress . ' | ';
                });
                Loop::repeat(3000, function () {
                    echo $this->pages->stats();
                });
                Loop::repeat(30000, function () {
                    echo "\r", $this->pages->save();
                });

                Loop::repeat(100, function () use ($client) {
                    // while (true) {
                    pcntl_signal_dispatch();
                    $lock = yield $this->semaphore->acquire();
                    $this->inProgress++;
                    $page = $this->pages->getNew();
                    if (!$page || $this->stop) {
                        if (!$this->pages->getInProgressCount()) {
                            Loop::stop();
                            return;
                            // break;
                        }
                        $this->inProgress--;
                        $lock->release();
                        return;
                    }

                    $page->status = Statuses::IN_PROGRESS;

                    // echo 'Checking "' . (string) $page->uri . '"' . PHP_EOL;
                    $requestHandler = $this->getHandler();
                    $request        = $requestHandler((string) $page->uri);
                    $request->onResolve($this->getResponseHandler($page, $lock));
                    yield $request;
                    // }
                });

            });
        } catch (\Amp\Artax\TimeoutException $e) {
            echo PHP_EOL, $e->getMessage(), PHP_EOL;
        }
    }

    private function getResponseHandler($page, $lock)
    {
        return function ($error, $result) use ($page, $lock) {
            // echo 'Done with ' . $page->uri . PHP_EOL;
            $this->inProgress--;
            $lock->release();
            // var_dump($error, $result);
            if ($error != null) {
                $page->status  = Statuses::ERROR;
                $page->comment = get_class($error);
                if ($page->comment == 'Amp\Artax\TimeoutException') {
                    $page->comment = 'Timeout';
                }
                return;
            }
            if ($result->getStatus() == 503) {
                $page->status  = Statuses::ERROR;
                $page->comment = 'Timeout';
                return;
            }
            $originalResponse = $this->getOriginalResponse($result);

            $status   = $originalResponse->getStatus();
            $ctype    = $result->getHeader('content-type');
            $location = $result->hasHeader('Location') ? $result->getHeader('Location') : false;
            if ($status[0] == 3 && $location) {
                $newPage = $this->addUri($location);
                $newPage->addLinkIn($page->uri);
                unset($page);
                unset($newPage);
                return;
            }

            // var_dump($result->getHeader('content-type'), $result->getHeaders(), $page);

            if (!empty($ctype)) {
                $page->contentType = $ctype;
            }
            // var_dump($page);
            // die;
            if ($status == 200 && preg_match('{text/html}', $page->contentType)) {
                $body = yield $result->getBody();
                $dom  = \str_get_html($body);
                if (!is_object($dom)) {
                    if (!trim($body)) {
                        $page->status  = Statuses::ERROR;
                        $page->comment = 'No content';
                        return;
                    }
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

                $base = $dom->find('base[href]');
                if (isset($base[0])) {
                    $page->base = new Uri($base[0]->href);
                } else {
                    $page->base = null;
                }

                foreach ($dom->find('a') as $a) {
                    if (empty($a->href) || $a->href[0] == '#' || $a->href == 'http://' || (!empty($a->rel) && strpos($a->rel, 'nofollow') !== false) || stripos($a->href, 'javascript') === 0) {
                        continue;
                    }

                    try {
                        $uri = $page->base ? $page->base->resolve($a->href) : $page->uri->resolve($a->href);
                    } catch (\Exception $e) {
                        if (strpos($e->getMessage(), 'Invalid host')) {
                            continue;
                        }
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
            $page->status = $status;
            unset($page);
        };
    }

    public function getOriginalResponse(\Amp\Artax\Response $response)
    {

        $originalResponse = null;
        $current          = $response;
        while ($current = $current->getPreviousResponse()) {
            $originalResponse = $current;
        }

        return $originalResponse ?? $response;
    }

}
