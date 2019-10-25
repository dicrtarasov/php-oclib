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

	/**
	 * Конструктор.
	 *
	 * @param string $url
	 * @param string $ssl
	 */
	public function __construct($url, $ssl = '')
	{
		$this->url = $url;
		$this->ssl = $ssl;
	}

	/**
	 * Добавляе обработчики ЧПУ.
	 *
	 * @param object $rewrite
	 */
	public function addRewrite(object $rewrite)
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

        // сроим ссылку
	    $url = rtrim($this->url, '/') . '/index.php';
	    if (empty($args)) {
	        $url .= '?route=' . $route;
	    } elseif (is_string($args)) {
	        $url .= '?route=' . $route . '&' . $args;
	    } else {
            // добавляем маршрут
            $args['route'] = $route;

            // сортируем
            ksort($args);

            // сроим ссылку
    	    $url .= '?' . http_build_query($args);
	    }

	    // формируем чпу
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
	    $args = Filter::params($args);
	    ksort($args);

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