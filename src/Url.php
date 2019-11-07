<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use InvalidArgumentException;
use function in_array;
use function is_array;
use function is_string;

/**
 * URL для OpenCart.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Url
{
    /** @var string */
    private $url;

    /** @var array */
    private $rewrite = [];

    /**
     * Конструктор.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Фильтрует аргументы запроса рекурсивно, удаляя пустые параметры
     *
     * @param array $params
     * @return array
     */
    public static function filterParams(array $params)
    {
        foreach ($params as $i => &$v) {
            if (is_array($v)) {
                $v = static::filterParams($v);
                if (empty($v)) {
                    unset($params[$i]);
                }
            } elseif ($v === null || $v === '' || $v === []) {
                unset($params[$i]);
            }
        }

        unset($v);

        if (isset($params['page']) && (int)$params['page'] < 2) {
            unset($params['page']);
        }

        unset($params['_route_']);

        ksort($params);

        return $params;
    }

    /**
     * Редиректит на канонический адрес если екущий оличается.
     *
     * @param string $url
     */
    public static function redirectToCanonical(string $url)
    {
        $urlInfo = parse_url($url);

        $canonical = $urlInfo['path'];
        if (! empty($urlInfo['query'])) {
            $canonical .= '?' . $urlInfo['query'];
        }

        if ($_SERVER['REQUEST_URI'] !== $canonical) {
            header('Location: ' . $canonical, true, 303);
            exit;
        }
    }

    /**
     * Добавляе обработчики ЧПУ.
     *
     * @param object $rewrite
     */
    public function addRewrite($rewrite)
    {
        if ($rewrite !== null && ! in_array($rewrite, $this->rewrite, false)) {
            $this->rewrite[] = $rewrite;
        }
    }

    /**
     * Строит ссылку.
     *
     * @param string $route
     * @param array|string $args
     * @return string
     */
    public function link(string $route, $args = null)
    {
        if (! isset($args)) {
            $args = [];
        }

        $route = trim($route);
        if (empty($route)) {
            if (is_array($args)) {
                $route = $args['route'] ?? '';
            }

            if (empty($route)) {
                throw new InvalidArgumentException('empty route');
            }
        }

        // удаляем служебные параметры
        if (is_array($args)) {
            unset($args['route'], $args['_route_']);
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
     * Ссылка с фильтрованными парамерами.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function canonical(string $route, array $params = [])
    {
        return $this->link($route, self::filterParams($params));
    }
}
