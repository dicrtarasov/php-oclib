<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 28.12.20 21:10:41
 */

/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
declare(strict_types = 1);

namespace dicr\oclib;

use Cart\Cart;
use Currency;
use Customer;
use ModelCatalogAttribute;
use ModelCatalogCategory;
use ModelCatalogManufacturer;
use ModelCatalogProduct;
use ModelCheckoutOrder;
use ModelExtensionExtension;
use ModelLocalisationLanguage;
use ModelToolImage;
use Tax;
use User;

/**
 * Интерфейс динамических свойств объектов Registry и RegistryProxy.
 *
 * @property-read Registry $registry
 *
 * @property-read Document $document
 * @property-read Config $config
 * @property-read Language $language
 *
 * @property-read Cache $cache
 * @property-read DB $db
 * @property-read Loader $load
 * @property-read Log $log
 * @property-read Url $url
 * @property-read Request $request
 * @property-read Response $response
 * @property-read Session $session
 */
interface RegistryProps
{
    // noop
}
