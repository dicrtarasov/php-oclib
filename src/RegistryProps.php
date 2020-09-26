<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 19:44:52
 */

/** @noinspection PhpUndefinedClassInspection */
declare(strict_types = 1);
namespace dicr\oclib;

use Cart;
use Config;
use Currency;
use Customer;
use Language;
use ModelCatalogAttribute;
use ModelCatalogCategory;
use ModelCatalogManufacturer;
use ModelCatalogProduct;
use ModelLocalisationLanguage;
use ModelToolImage;
use Tax;
use User;

/**
 * Интерфейс динамических свойств объектов Registry и RegistryProxy.
 *
 * @property-read \Document $document
 * @property-read Config $config
 * @property-read Language $language
 * @property-read Currency $currency
 * @property-read Tax $tax
 * @property-read Customer $customer
 * @property-read Cart $cart
 * @property-read User $user
 *
 * @property-read Cache $cache
 * @property-read DB $db
 * @property-read Loader $load
 * @property-read Url $url
 * @property-read Request $request
 * @property-read Response $response
 * @property-read Session $session
 *
 * @property-read ModelCatalogProduct $model_catalog_product
 * @property-read ModelCatalogCategory $model_catalog_category
 * @property-read ModelCatalogManufacturer $model_catalog_manufacturer
 * @property-read ModelToolImage $model_tool_image
 * @property-read ModelCatalogAttribute $model_catalog_attribute
 * @property-read ModelLocalisationLanguage $model_localisation_language
 *
 * @property-read ?UrlAlias $urlAlias ЧПУ-алиас текущей страницы (устанавливается в UrlAliasRule при парсинге url)
 */
interface RegistryProps
{

}
