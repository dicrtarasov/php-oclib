<?php
namespace dicr\oclib;

/**
 * URL.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Url
{
	private $url;

	private $ssl;

	private $rewrite = [];

	public function __construct($url, $ssl = '')
	{
		$this->url = $url;
		$this->ssl = $ssl;
	}

	public function addRewrite($rewrite)
	{
	    if (!empty($rewrite) && !in_array($rewrite, $this->rewrite)) {
            $this->rewrite[] = $rewrite;
	    }
	}

	/**
	 * Строит ссылку.
	 *
	 * @param string $route
	 * @param array|string $args
	 * @param bool $secure
	 */
	public function link(string $route, $args = [], $secure = false)
	{
	    $route = trim($route);
	    if (empty($route)) {
	        throw new \InvalidArgumentException('empty route');
	    }

        if (!is_array($args)) {
            $args = trim($args);
            if (empty($args)) {
                $args = [];
            } else {
                parse_str($args, $args);
            }
        }

        $args['route'] = $route;

	    $url = sprintf('%sindex.php?%s', $this->url,
	        preg_replace(
	        	['~\%2F~uism', '~\%7B~uism', '~%7D~uism'],
	        	['/', '{', '}'],
	        	http_build_query($args)
	        )
        );

	    foreach ($this->rewrite as $rewrite) {
	    	$url = $rewrite->rewrite($url);
	    }

		return $url;
	}

	/**
	 * Редиректит на канонический адрес.
	 *
	 * @param string $route
	 * @param array $args
	 */
	public function redirectToCanonical(string $route, $args = [])
	{
        $urlInfo = parse_url($this->link($route, $args));

        $canonical = $urlInfo['path'];
        if (!empty($urlInfo['query'])) {
            $canonical .= '?' .$urlInfo['query'];
        }

        if ($_SERVER['REQUEST_URI'] != $canonical) {
            header('Location: ' . $canonical, true, 303);
            exit;
        }
	}
}