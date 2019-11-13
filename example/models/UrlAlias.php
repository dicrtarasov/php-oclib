<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace app\models;

/**
 * Алиас Url.
 *
 * @property-read int $url_alias_id
 * @property string $query
 * @property string $keyword
 *
 * @property-read string $type тип алиаса (TYPE_*)
 * @property-read string $route маршрут алиаса (или пустая строка для алиаса парамеров)
 * @property-read array $params параметры URL алиаса (или пустой массив для алиаса маршрута)
 * @property-read array $link ссылка алиаса [0 => $route, ... $params]
 *
 * @package app\models
 */
class UrlAlias extends \dicr\oclib\UrlAlias
{

}
