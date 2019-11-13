<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use app\models\Categ;
use app\models\Prod;
use dicr\helper\ArrayHelper;
use dicr\helper\Url;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\di\Instance;
use yii\web\UrlRuleInterface;
use function count;
use const PREG_SPLIT_NO_EMPTY;
use const SORT_NATURAL;

/**
 * Правило обработки URL для каталога.
 *
 * @package app\components
 */
class UrlAliasRule extends BaseObject implements UrlRuleInterface
{
    /** @var bool поддержка мультипараметровых алиасов (алиас для нескольких праметров - медленно через RLIKE) */
    public $multiQueryAliases = false;

    /** @var \yii\caching\CacheInterface */
    public $cache = 'cache';

    /**
     * Инициализация.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function createUrl($manager, $route, $params)
    {
        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        if (empty($params)) {
            $params = [];
        } else {
            $params = Url::normalizeQuery($params);
        }

        return Yii::$app->cache->getOrSet([__METHOD__, $route, $params], function() use ($route, $params) {
            // если роут объекта, то получаем ЧПУ объекта, иначе ЧПУ для маршрута
            $slug = ! empty(UrlAlias::paramByRoute($route)) ? $this->createObjectSlug($route, $params) :
                $this->createRouteSlug($route);

            $url = null;

            // ЧПУ найдено
            if (! empty($slug)) {
                // строим URL
                $url = '/' . trim($slug, '/');

                // дополнительно получаем ЧПУ для парамеров
                if (! empty($params)) {
                    $slug = $this->createParamsSlug($params);
                    if (! empty($slug)) {
                        $url .= '/' . $slug;
                    }

                    // оставшиеся парамеры добавляем как параметры запроса
                    if (! empty($params)) {
                        $url .= '?' . Url::buildQuery($params);
                    }
                }
            }

            return $url ?: '';
        }, null, new TagDependency([
            'tags' => [UrlAlias::class]
        ])) ?: false;
    }

    /**
     * @inheritDoc
     * @property \yii\web\Request $request
     */
    public function parseRequest($manager, $request)
    {
        // путь
        $path = $request->pathInfo;

        // нормализируем путь для кэша
        if (! empty($manager->normalizer)) {
            $path = $manager->normalizer->normalizePathInfo($path, '');
        }

        // конвертируем путь в маршрут и параметры
        [$route, $params] = $this->cache->getOrSet([__METHOD__, $path], static function() use ($path) {
            // если путь это prettyUrl (роут), то пытаемся найти его среди известных маршрутов
            foreach (UrlAlias::paramRoutes() as $paramRoute) {
                if ($path === $paramRoute['route']) {
                    return [$paramRoute['route'], []];
                }
            }

            $route = null;
            $params = [];

            // обходим весь путь алиасов
            foreach (explode('/', $path) as $keyword) {
                $alias = UrlAlias::findOne(['keyword' => $keyword]);
                if (empty($alias)) {
                    $route = null;
                    break;
                }

                // а улиаса параметров нет маршрута
                if (! empty($alias->route)) {
                    $route = $alias->route;
                }

                /** @noinspection SlowArrayOperationsInLoopInspection */
                $params = ArrayHelper::merge($params, $alias->params);
            }

            return [$route, $params];
        }, null, new TagDependency([
            'tags' => [UrlAlias::class]
        ]));

        // так как UrlManager складывает парамеры не рекурсивно, то обьединять будем сами
        if (! empty($route)) {
            $params = ArrayHelper::merge(Yii::$app->request->get(), $params);
            return [$route, $params];
        }

        return false;
    }

    /**
     * Строит ЧПУ объекта (товара, категории, новости, статьи).
     *
     * @param $route
     * @param array $params
     * @return string|false
     */
    protected function createObjectSlug($route, array &$params)
    {
        // для товара и категории строим специальное ЧПУ с полным путем
        if ($route === 'product/category') {
            return $this->createCategorySlug($params);
        }

        // для категории тоже ЧПУ с полным путем
        if ($route === 'product/product') {
            return $this->createProductSlug($params);
        }

        $alias = UrlAlias::findObjectAlias($route, $params);    // удаляе из параметров параметр объекта
        return ! empty($alias) ? $alias->keyword : false;
    }

    /**
     * Возвращает ЧПУ для маршрута.
     *
     * @param string $route
     * @return bool|string
     */
    protected function createRouteSlug(string $route)
    {
        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        $alias = UrlAlias::findRouteAlias($route);
        return ! empty($alias) ? $alias->keyword : false;
    }

    /**
     * Создает ЧПУ для дополнительных парамеров, используя алиасы параметров.
     *
     * @param array $params ссылка на праметры из которых удаляются использованные
     * @return bool|string путь ЧПУ для парамеров
     */
    protected function createParamsSlug(array &$params)
    {
        if (empty($params)) {
            return false;
        }

        $keywords = [];

        if ($this->multiQueryAliases) {
            while (! empty($params)) {
                $alias = UrlAlias::findMultiParamAlias($params);
                if (empty($alias)) {
                    break;
                }

                $keywords[] = $alias->keyword;
            }
        } else {
            $keywords = ArrayHelper::getColumn(UrlAlias::findSingleParamAliases($params), 'keyword');
        }

        if (empty($keywords)) {
            return false;
        }

        sort($keywords, SORT_NATURAL);
        return implode('/', $keywords);
    }

    /**
     * Создает путь ЧПУ категории по параметрам.
     *
     * @param array $params ссылка на параметры URL (удаляются использованные параметры)
     * @return bool|string путь ЧПУ категории
     */
    protected function createCategorySlug(array &$params)
    {
        // получаем ID категории
        $category_id = (int)($params['category_id'] ?? 0);

        if (empty($category_id) && ! empty($params['path'])) {
            $path = preg_split('~[_]+~um', trim($params['path']), - 1, PREG_SPLIT_NO_EMPTY);
            $category_id = ! empty($path) ? (int)array_pop($path) : 0;
            if (empty($category_id)) {
                return false;
            }
        }

        // получаем путь категории
        $path = Categ::path($category_id);
        if (empty($path)) {
            return false;
        }

        // получаем все алиасы пути категории
        $keywords = UrlAlias::find()->select(['keyword', 'query'])->indexBy('query')->where([
            'query' => array_map(static function($path_id) {
                return 'category_id=' . (int)$path_id;
            }, array_keys($path))
        ])->asArray(true)->column();

        if (count($keywords) !== count($path)) {
            return false;
        }

        // составляем путь из keywords
        foreach ($path as $path_id => &$val) {
            $val = $keywords['category_id=' . (int)$path_id] ?? null;
            if (empty($val)) {
                Yii::error('Не совпадают алиасы с путем категории: ' . $category_id, __METHOD__);
                return false;
            }
        }

        // удаляем использованные параметры
        unset($val, $params['category_id'], $params['path']);

        // возвращаем путь ЧПУ
        return implode('/', $path);
    }

    /**
     * Строит путь ЧПУ товара из праметров.
     *
     * @param array $params ссылка на парамеры (удаляются использованные)
     * @return bool|string путь ЧПУ товара
     */
    protected function createProductSlug(array &$params)
    {
        // получаем id товара
        $product_id = (int)($params['product_id'] ?? 0);
        if (empty($product_id)) {
            return false;
        }

        // получаем алиас товара
        $prodKeyword =
            UrlAlias::find()->select('keyword')->where(['query' => 'product_id=' . $product_id])->limit(1)->scalar();

        if (empty($prodKeyword)) {
            return false;
        }

        // получаем id категории
        $category_id = Prod::categId($product_id);
        if (empty($category_id)) {
            return false;
        }

        // получаем ЧПУ категории
        $params['category_id'] = $category_id;
        $slug = $this->createCategorySlug($params);
        if (empty($slug)) {
            return false;
        }

        // удаляем использованные парамеры
        unset($params['product_id']);

        return $slug . '/' . $prodKeyword;
    }
}
