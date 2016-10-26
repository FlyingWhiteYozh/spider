<?php
namespace Spider;

class Robots
{

	public function __construct($text)
	{
		$this->robots = explode("\n", $text);
	}

    public function isDisallowed($url)
    {
        $rules = array(
            'Allow' => array(),
            'Disallow' => array(),
        );
        $UA = '';
        foreach ($this->robots as $line) {
            $matches = array();
            $line = trim($line);
            $line = preg_replace('{#.*}', '', $line);
            if (preg_match('{^User-Agent:\s*(\S*)}i', $line, $matches)) {
                $UA = $matches[1];
            } elseif ($UA == '*' && preg_match('{^(Disallow|Allow):\s*(.*)}', $line, $matches)) {
                $rules[$matches[1]][] = trim($matches[2]);
            } elseif (!$line) {
            	$UA = '';
            }
        }

        // var_dump($this->robots, $rules);

        if ($this->check($rules['Allow'], $url)) {
            return false;
        }

        if ($rule = $this->check($rules['Disallow'], $url)) {
            return $rule;
        }

        return false;
    }

    public function check($rules, $url)
    {
        $escape = ['$' => '\$', '?' => '\?', '.' => '\.', '*' => '.*'];
        foreach ($rules as $rule) {
            foreach ($escape as $search => $replace) {
                $escaped = str_replace($search, $replace, $rule);
            }
            if (preg_match('{^' . $escaped . '}', $url)) {
            	// var_dump('Match:' . $url . ' | ' . $escaped);
                return $rule;
            }
        }
        // var_dump($url, $escaped);
        return false;
    }
}
