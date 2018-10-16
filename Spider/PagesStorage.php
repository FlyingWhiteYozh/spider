<?php
namespace Spider;

use \Amp\Uri\Uri;

class PagesStorage
{
    public $fileName;
    protected $pages = array();

    protected static $instances = array();

    protected function __construct($file)
    {
        $this->fileName = $file;
    }

    public static function getInstance($file)
    {
        if (!isset(self::$instances[$file])) {
            self::$instances[$file] = new static($file);
        }

        return self::$instances[$file];
    }

    public function load()
    {
        if (is_file($this->fileName)) {
            $this->pages = unserialize(gzdecode(file_get_contents($this->fileName)));
        }
    }

    public function save()
    {
        foreach ($this->pages as $page) {
            if ($page->status == Statuses::IN_PROGRESS) {
                $page->status = Statuses::PENDING;
            }
        }

        file_put_contents($this->fileName, gzencode(serialize($this->pages)));
    }

    public function reset()
    {
        $pages = [];
    }

    public function get($uri = null)
    {
        if ($uri === null) {
            return reset($this->pages);
        }
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $uri = preg_replace('{#.*$}', '', (string) $uri);
        if (!isset($this->pages[$uri])) {
            $this->pages[$uri] = new Page($uri);
        }

        return $this->pages[$uri];
    }

    public function getNew()
    {
        foreach ($this->pages as $page) {
            if ($page->status == Statuses::PENDING) {
                return $page;
            }
        }

        return false;
    }

    public function getInProgressCount()
    {
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

    public function dump()
    {
     /*   $urls = [
            'https://mc.ru/metalloprokat/alyuminy_dyural',
            'https://mc.ru/metalloprokat/armatura_katanka/10',
            'https://mc.ru/metalloprokat/balka_2t_22',
            'https://mc.ru/metalloprokat/chugunnye_chushki',
            'https://mc.ru/metalloprokat/chushka_alyuminievaya',
            'https://mc.ru/metalloprokat/cinkovyj_anod',
            'https://mc.ru/metalloprokat/cvetnoj',
            'https://mc.ru/metalloprokat/cynk',
            'https://mc.ru/metalloprokat/dyuralevyj_profil',
            'https://mc.ru/metalloprokat/dyuralyuminievyj_profil',
            'https://mc.ru/metalloprokat/dyuralyuminy',
            'https://mc.ru/metalloprokat/ehlektrody',
            'https://mc.ru/metalloprokat/elektrody_e42',
            'https://mc.ru/metalloprokat/instrumentalnaya_stal',
            'https://mc.ru/metalloprokat/kanaty_stalnye_gost_2688_80',
            'https://mc.ru/metalloprokat/konstrukcyonnaya_stal',
            'https://mc.ru/metalloprokat/kruglye_alyuminievye_truby',
            'https://mc.ru/metalloprokat/latun',
            'https://mc.ru/metalloprokat/lenta_holodnokatanaya_shtampovalnaya',
            'https://mc.ru/metalloprokat/list_goryachekatannyj',
            'https://mc.ru/metalloprokat/list_nerzhavejka',
            'https://mc.ru/metalloprokat/list_ocynkovannyj',
            'https://mc.ru/metalloprokat/listovoy',
            'https://mc.ru/metalloprokat/listy_10hsnd',
            'https://mc.ru/metalloprokat/listy_20x13',
            'https://mc.ru/metalloprokat/listy_stalnye_09g2s',
            'https://mc.ru/metalloprokat/med',
            'https://mc.ru/metalloprokat/mednaya_lenta_krovelnaya',
            'https://mc.ru/metalloprokat/mednaya_provoloka',
            'https://mc.ru/metalloprokat/metizy',
            'https://mc.ru/metalloprokat/nerzhaveyuschaya_svarochnaya_provoloka',
            'https://mc.ru/metalloprokat/nerzhaveyuschie_metizy',
            'https://mc.ru/metalloprokat/nerzhaveyuschie_pischevye_listy',
            'https://mc.ru/metalloprokat/nerzhaveyuschy',
            'https://mc.ru/metalloprokat/profnastil',
            'https://mc.ru/metalloprokat/prokat_trub',
            'https://mc.ru/metalloprokat/provoloka_kanaty',
            'https://mc.ru/metalloprokat/provoloka_poligraficheskaya_obyknovennogo_kachestva',
            'https://mc.ru/metalloprokat/prutki_stalnye',
            'https://mc.ru/metalloprokat/royalnaya_provoloka',
            'https://mc.ru/metalloprokat/setka_lenta',
            'https://mc.ru/metalloprokat/shveller_10p',
            'https://mc.ru/metalloprokat/shveller_10u',
            'https://mc.ru/metalloprokat/shveller_12p',
            'https://mc.ru/metalloprokat/shveller_12u',
            'https://mc.ru/metalloprokat/shveller_14p',
            'https://mc.ru/metalloprokat/shveller_16p',
            'https://mc.ru/metalloprokat/shveller_18p',
            'https://mc.ru/metalloprokat/shveller_27p',
            'https://mc.ru/metalloprokat/shveller_ugolok',
            'https://mc.ru/metalloprokat/sortovoy',
            'https://mc.ru/metalloprokat/stal_listovaya_2mm',
            'https://mc.ru/metalloprokat/stal_listovaya_3mm',
            'https://mc.ru/metalloprokat/stal_listovaya_4mm',
            'https://mc.ru/metalloprokat/stal_listovaya_nerzhaveyushchaya_nikelsoderzhashchaya/mark/20h23n18',
            'https://mc.ru/metalloprokat/stal_listovaya_ocink_v_rulonah',
            'https://mc.ru/metalloprokat/stal_listovaya_okrashennaya',
            'https://mc.ru/metalloprokat/svincovaya_chushka',
            'https://mc.ru/metalloprokat/svincovyj_list',
            'https://mc.ru/metalloprokat/truba_alyuminievaya',
            'https://mc.ru/metalloprokat/truba_dyuralevaya',
            'https://mc.ru/metalloprokat/truby_besshovnye_goryachedeformirovannye',
            'https://mc.ru/metalloprokat/truby_elektrosvarnye_57',
            'https://mc.ru/metalloprokat/truby_elektrosvarnye_pryamoshovnye',
            'https://mc.ru/metalloprokat/truby_nerzhaveyuschie',
            'https://mc.ru/metalloprokat/truby_profilnye',
            'https://mc.ru/metalloprokat/truby_pryamougolnye',
            'https://mc.ru/metalloprokat/ugolok',
            'https://mc.ru/metalloprokat/ugolok/09g2s',
            'https://mc.ru/metalloprokat/ugolok/125',
            'https://mc.ru/metalloprokat/ugolok/140',
            'https://mc.ru/metalloprokat/ugolok/160',
            'https://mc.ru/metalloprokat/ugolok/180',
            'https://mc.ru/metalloprokat/ugolok/200',
            'https://mc.ru/metalloprokat/ugolok/40',
            'https://mc.ru/metalloprokat/ugolok/63',
            'https://mc.ru/metalloprokat/ugolok/70',
            'https://mc.ru/metalloprokat/ugolok/75',
        ];*/
        foreach ($this->pages as $page) {
            // if (!in_array($page->uri, $urls)) {
                // continue;
            // }
            echo "$page->uri $page->status $page->comment\n";
            // foreach ($page->linkedFrom as $link => $count) {
                // echo "\t$link $count\n";
            // }

        }
    }

    public function dumpErrors()
    {
        foreach ($this->pages as $page) {
            if ($page->status == 200) {
                continue;
            }

            if ($page->status == Statuses::SKIPPED && (strpos($page->comment, 'custom_filter') === 0 || strpos($page->comment, 'robots') === 0)) {
                continue;
            }

            echo "$page->uri $page->status $page->comment\n";

            /*foreach ($page->linkedFrom as $link => $count) {
        echo "\t$link $count\n";
        }*/

        }
    }

    public function dumpTextPages()
    {
        $i = 0;
        foreach ($this->pages as $page) {
            if (preg_match('{text/html}', $page->contentType)) {
                $i++;
                echo "$page->uri $page->status $page->comment\n";
            }
        }
        echo "Summary: $i\n";
    }

    public function dump2()
    {
        foreach ($this->pages as $page) {
            if ($page->status != Statuses::SKIPPED) {
                echo "$page->uri $page->status $page->comment\n";
            }

        }
    }

    public function dump4xx()
    {
        foreach ($this->pages as $page) {
            if (substr($page->status, 0, 1) != 4) {
                continue;
            }

            echo "$page->uri Статус: $page->status $page->comment\nСсылки на страницах:\n";
            $i = 0;
            foreach ($page->linkedFrom as $link => $count) {
                if (++$i > 10) {
                    // echo 'И ' . (count($page->linkedFrom) - 10) . ' других. ' . PHP_EOL;
                    break;
                }
                echo "\t$link\n";
            }
            echo PHP_EOL;
        }
    }

    public function dump4xxByPage()
    {
        $fromTo = [];
        foreach ($this->pages as $page) {
            if (substr($page->status, 0, 1) != 4) {
                continue;
            }

            // echo "$page->uri Статус: $page->status $page->comment\nСсылки на страницах:\n";
            $i = 0;
            foreach ($page->linkedFrom as $link => $count) {
                if (!isset($fromTo[$link])) {
                    $fromTo[$link] = [];
                }
                $fromTo[$link][] = $page->uri;
                // echo "\t$link\n";
            }
            // echo PHP_EOL;
        }
        foreach($fromTo as $page => $links) {
            echo $page, PHP_EOL;
            foreach($links as $link) {
                echo "\t$link\n";
            }
            echo PHP_EOL;
        }
    }

    public function dump3xx()
    {
        foreach ($this->pages as $page) {
            if (substr($page->status, 0, 1) != 3) {
                continue;
            }

            echo "$page->uri Статус: $page->status $page->comment\nСсылки на страницах:\n";
            $i = 0;
            foreach ($page->linkedFrom as $link => $count) {
                if (++$i > 10) {
                    // echo 'И ' . (count($page->linkedFrom) - 10) . ' других. ' . PHP_EOL;
                    break;
                }
                $count = $count > 1 ? $count : '';
                echo "\t$link\n";
            }
            echo PHP_EOL;
        }
    }

    public function dumpExternal()
    {
        $static  = '';
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
                    // $out .= 'И ' . (count($page->linkedFrom) - 10) . ' других. ' . PHP_EOL;
                    break;
                }
                $out .= "\t$link\n";
            }
            $out .= PHP_EOL;
        }
        echo 'Внешние ссылки:' . PHP_EOL . $dynamic;
        echo 'Внешние ресурсы:' . PHP_EOL . $static;
    }

    public function dumpTA()
    {
        $this->dumpExternal();

        echo 'Битые ссылки: ' . PHP_EOL;
        $this->dump4xx();

        echo 'Ссылки через редиректы: ' . PHP_EOL;
        $this->dump3xx();
    }

    public function dumpMeta()
    {
        foreach ($this->pages as $page) {
            if (substr($page->status, 0, 1) != 2) {
                continue;
            }

            echo "$page->uri\t'$page->title'\t'$page->description'\t'$page->keywords'\t'$page->h1'";

            echo PHP_EOL;
        }

    }

    public function genSitemapXml($file = '/home/yaroslav/scripts/sitemap.xml')
    {
        $f = fopen($file, 'w');
        fwrite($f, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($f, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);

        foreach ($this->pages as $page) {
            if (stripos($page->contentType, 'text') !== false || $page->status == Statuses::PENDING) {
                fwrite($f, '<url><loc>' . $page->uri . '</loc></url>' . PHP_EOL);
            }
        }

        fwrite($f, '</urlset>' . PHP_EOL);

    }

    public function retry()
    {
        foreach ($this->pages as $page) {
            if ($page->status == Statuses::ERROR && $page->comment == 'Timeout') {
                $page->status  = Statuses::PENDING;
                $page->comment = '';
            }
        }
    }

    public function stats()
    {
        $all     = 0;
        $checked = 0;
        $actual  = 0;
        $timeout = 0;
        foreach ($this->pages as $page) {
            if ($page->status == Statuses::ERROR && $page->comment == 'Timeout') {
                ++$timeout;
            } elseif (!in_array($page->status, [Statuses::PENDING, Statuses::IN_PROGRESS, Statuses::SKIPPED])) {
                ++$checked;
            }
            if ($page->status != Statuses::SKIPPED) {
                ++$actual;
            }
            ++$all;
        }
        return $checked . '/' . ($actual) . ' (' . number_format(($checked / $actual) * 100, 2) . '%) | ' . $all . " | Timeout: $timeout\033[K";
    }

    public function printH1()
    {
        foreach ($this->pages as $page) {
            if ($page->status == '200' && stripos($page->contentType, 'text') !== false) {
                echo $page->uri, "\t", $page->h1, PHP_EOL;
            }
        }

    }

    public function printTitle()
    {
        foreach ($this->pages as $page) {
            if ($page->status == '200' && stripos($page->contentType, 'text') !== false) {
                echo $page->uri, "\t", $page->title, PHP_EOL;
            }
        }

    }
    public function printCanonical()
    {
        foreach ($this->pages as $page) {
            if ($page->status == '200' && stripos($page->contentType, 'text') !== false && $page->canonical) {
                echo $page->uri, "\t", $page->canonical, PHP_EOL;
            }
        }

    }
}
