<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 22.12.20 23:22:31
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\InvalidArgumentException;

use function header;
use function implode;
use function is_string;
use function parse_url;
use function preg_split;

use const PREG_SPLIT_NO_EMPTY;

/**
 * URL для OpenCart.
 */
class Url extends \dicr\helper\Url
{
    /**
     * Конструктор.
     *
     * @param ?string $url
     * @param ?string $ssl
     */
    public function __construct(?string $url = null, ?string $ssl = null)
    {
        // noop
    }

    /**
     * Переадресует на канонический адрес если текущий отличается.
     *
     * @param string $route
     * @param array|string $params
     */
    public function redirectToCanonical(string $route, $params = []) : void
    {
        $url = $this->link($route, $params);
        $urlInfo = (array)parse_url($url);

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
     * Строит ссылку.
     *
     * @param string $route
     * @param array|string $args
     * @return string
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function link(string $route, $args = []) : string
    {
        if (empty($args)) {
            $args = [];
        } elseif (is_string($args)) {
            $args = static::parseQuery($args);
        }

        // удаляем служебные параметры
        unset($args['route']);
        $args[0] = $route;

        return static::to($args, true);
    }

    /**
     * Фильтрует аргументы запроса рекурсивно, удаляя пустые параметры
     *
     * @param array $params
     * @return array
     */
    public static function filterParams(array $params) : array
    {
        $params = static::filterQuery($params);

        if (isset($params['page']) && (int)$params['page'] < 2) {
            unset($params['page']);
        }

        unset($params['_route_']);

        return self::normalizeQuery($params);
    }

    /**
     * Парсит маршрут.
     *
     * @param string $route
     * @return string[]
     */
    public static function parseRoute(string $route) : array
    {
        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        return preg_split('~/+~u', $route, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Собирает маршрут.
     *
     * @param array $parts
     * @return string
     */
    public static function buildRoute(array $parts) : string
    {
        if (empty($parts)) {
            throw new InvalidArgumentException('parts');
        }

        return implode('/', $parts);
    }

    /**
     * Возвращает идентификатор контроллера по маршруту.
     *
     * @param string $route
     * @return string
     */
    public static function controllerByRoute(string $route) : string
    {
        return $route;
    }
}
