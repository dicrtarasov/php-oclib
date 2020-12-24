<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 24.12.20 05:48:04
 */

declare(strict_types = 1);

namespace dicr\oclib;

use dicr\helper\Url;
use Yii;

/**
 * Class Html
 */
class Html extends \dicr\helper\Html
{
    /**
     * Возвращает параметры запроса в meta-тегах:
     * - meta property="route"
     * - meta property="params"
     *
     * @param ?array $url
     * @return string
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function request(?array $url = null) : string
    {
        $params = Url::buildQuery(Url::normalizeQuery(Url::filterQuery(
            [0 => null] + ($url ?? Registry::$app->request->get)
        )));

        return
            static::meta(['property' => 'route', 'content' => $url[0] ?? Yii::$app->requestedRoute ?? '']) .
            static::meta(['property' => 'params', 'content' => $params]);
    }
}
