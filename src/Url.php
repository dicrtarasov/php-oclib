<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 00:46:01
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\InvalidArgumentException;
use function is_string;
use const PREG_SPLIT_NO_EMPTY;

/**
 * URL для OpenCart.
 */
class Url extends \dicr\helper\Url
{
    /**
     * Конструктор.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {

    }

    /** @noinspection PhpMethodMayBeStaticInspection */

    /**
     * Добавляе обработчики ЧПУ.
     *
     * @param object $rewrite
     */
    public function addRewrite($rewrite)
    {

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
     * Строит ссылку.
     *
     * @param string $route
     * @param array|string $args
     * @return string
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function link(string $route, $args = null)
    {
        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        if ($args === null) {
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
    public static function filterParams(array $params)
    {
        $params = static::filterQuery($params);

        if (isset($params['page']) && (int)$params['page'] < 2) {
            unset($params['page']);
        }

        unset($params['_route_']);

        return self::normalizeQuery($params);
    }

    /**
     * Парсит роут.
     *
     * @param string $route
     * @return string[]
     */
    public static function parseRoute(string $route)
    {
        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        return preg_split('~/+~u', $route, - 1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Собирает роут.
     *
     * @param array $parts
     * @return string
     */
    public static function buildRoute(array $parts)
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
    public static function controllerByRoute(string $route)
    {
        return $route;
    }

}
