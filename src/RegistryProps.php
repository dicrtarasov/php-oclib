<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 00:46:01
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Интерфейс динамических свойств объектов Registry и RegistryProxy.
 *
 * @property-read \Document $document
 * @property-read \Config $config
 * @property-read \Language $language
 * @property-read \Currency $currency
 * @property-read \Tax $tax
 * @property-read \Customer $customer
 * @property-read \Cart $cart
 * @property-read \User $user
 *
 * @property-read \dicr\oclib\Cache $cache
 * @property-read \dicr\oclib\DB $db
 * @property-read \dicr\oclib\Loader $load
 * @property-read \dicr\oclib\Url $url
 * @property-read \dicr\oclib\Request $request
 * @property-read \dicr\oclib\Response $response
 * @property-read \dicr\oclib\Session $session
 *
 * @property-read \ModelCatalogProduct $model_catalog_product
 * @property-read \ModelCatalogCategory $model_catalog_category
 * @property-read \ModelCatalogManufacturer $model_catalog_manufacturer
 * @property-read \ModelToolImage $model_tool_image
 * @property-read \ModelCatalogAttribute $model_catalog_attribute
 * @property-read \ModelLocalisationLanguage $model_localisation_language
 *
 * @property-read \app\models\UrlAlias|null $urlAlias ЧПУ-алиас текущей страницы (устанавливается в UrlAliasRule при
 *     парсинге url)
 *
 * @package dicr\oclib
 * @noinspection PhpUndefinedClassInspection
 */
interface RegistryProps
{

}
