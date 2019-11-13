<?php
/** @noinspection PhpUnhandledExceptionInspection */

use app\models\City;

// Version
define('VERSION', '2.1.0.1');

/*include_once $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'Siter' . DIRECTORY_SEPARATOR . 'buffer_worker.php';
siter_read_cache();
ob_start();*/

//redirect
$st_query = substr($_SERVER['REQUEST_URI'], 0, - 1);
if (substr($_SERVER['REQUEST_URI'], - 1) == '?') {
    header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . DOMAIN . $st_query, true, 301);
}

// Configuration
require_once(__DIR__ . '/config.php');

// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Registry
$registry = new Registry();

// Loader
$registry->set('load', new Loader());

// Config
$config = new Config();
$registry->set('config', $config);

$zone = explode('.', $_SERVER['HTTP_HOST']);
switch (end($zone)) {
    case 'kz':
        $config->set('config_currency', 'KZT');
        $currency = 'KZT';
        break;
    case 'by':
        $config->set('config_currency', 'BYN');
        $currency = 'BYN';
        break;
    default:
        $config->set('config_currency', 'RUB');
        $currency = 'RUB';
        break;
}

define('CURRENCY', $currency);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

// Domain_id
define('DOMAIN_ID', City::current()->id);

// Store
if ($_SERVER['HTTPS']) {
    $store_query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`ssl`, 'www.', '') = '" .
                              $db->escape('https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) .
                                          rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
} else {
    $store_query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`url`, 'www.', '') = '" .
                              $db->escape('http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) .
                                          rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
}

if ($store_query->num_rows) {
    $config->set('config_store_id', $store_query->row['store_id']);
} else {
    $config->set('config_store_id', 0);
}

// Settings
$query = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' OR store_id = '" .
                    (int)$config->get('config_store_id') . "' ORDER BY store_id ASC");

foreach ($query->rows as $result) {
    if (! $result['serialized']) {
        $config->set($result['key'], $result['value']);
    } else {
        $config->set($result['key'], json_decode($result['value'], true));
    }
}

if (! $store_query->num_rows) {
    $config->set('config_url', HTTP_SERVER);
    $config->set('config_ssl', HTTPS_SERVER);
}

// Url
$registry->set('url', new Url($config->get('config_url')));

// Request
$request = new Request();
$registry->set('request', $request);

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$response->setCompression($config->get('config_compression'));
$registry->set('response', $response);

// Cache
$registry->set('cache', new Cache());

// Session
if (isset($request->get['token']) && isset($request->get['route']) && substr($request->get['route'], 0, 4) == 'api/') {
    $db->query("DELETE FROM `" . DB_PREFIX . "api_session` WHERE TIMESTAMPADD(HOUR, 1, date_modified) < NOW()");

    $query = $db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "api` `a` LEFT JOIN `" . DB_PREFIX .
                        "api_session` `as` ON (a.api_id = as.api_id) LEFT JOIN " . DB_PREFIX .
                        "api_ip `ai` ON (as.api_id = ai.api_id) WHERE a.status = '1' AND as.token = '" .
                        $db->escape($request->get['token']) . "' AND ai.ip = '" .
                        $db->escape($request->server['REMOTE_ADDR']) . "'");

    if ($query->num_rows) {
        // Does not seem PHP is able to handle sessions as objects properly so so wrote my own class
        $session = new Session($query->row['session_id'], $query->row['session_name']);
        $registry->set('session', $session);

        // keep the session alive
        $db->query("UPDATE `" . DB_PREFIX . "api_session` SET date_modified = NOW() WHERE api_session_id = '" .
                   $query->row['api_session_id'] . "'");
    }
} else {
    $session = new Session();
    $registry->set('session', $session);
}

// Language Detection
$languages = [];

$query = $db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE status = '1'");

foreach ($query->rows as $result) {
    $languages[$result['code']] = $result;
}

if (isset($session->data['language']) && array_key_exists($session->data['language'], $languages)) {
    $code = $session->data['language'];
} elseif (isset($request->cookie['language']) && array_key_exists($request->cookie['language'], $languages)) {
    $code = $request->cookie['language'];
} else {
    $detect = '';

    if (isset($request->server['HTTP_ACCEPT_LANGUAGE']) && $request->server['HTTP_ACCEPT_LANGUAGE']) {
        $browser_languages = explode(',', $request->server['HTTP_ACCEPT_LANGUAGE']);

        foreach ($browser_languages as $browser_language) {
            foreach ($languages as $key => $value) {
                if ($value['status']) {
                    $locale = explode(',', $value['locale']);

                    if (in_array($browser_language, $locale)) {
                        $detect = $key;
                        break 2;
                    }
                }
            }
        }
    }

    $code = $detect ? $detect : $config->get('config_language');
}

if (! isset($session->data['language']) || $session->data['language'] != $code) {
    $session->data['language'] = $code;
}

if (! isset($request->cookie['language']) || $request->cookie['language'] != $code) {
    setcookie('language', $code, time() + 60 * 60 * 24 * 30, '/', $request->server['HTTP_HOST']);
}

$config->set('config_language_id', $languages[$code]['language_id']);
$config->set('config_language', $languages[$code]['code']);

// Language
$language = new Language($languages[$code]['directory']);
$language->load($languages[$code]['directory']);
$registry->set('language', $language);

// Document
$registry->set('document', new Document());

// Customer
$customer = new Customer($registry);
$registry->set('customer', $customer);

// Customer Group
if ($customer->isLogged()) {
    $config->set('config_customer_group_id', $customer->getGroupId());
} elseif (isset($session->data['customer']) && isset($session->data['customer']['customer_group_id'])) {
    // For API calls
    $config->set('config_customer_group_id', $session->data['customer']['customer_group_id']);
} elseif (isset($session->data['guest']) && isset($session->data['guest']['customer_group_id'])) {
    $config->set('config_customer_group_id', $session->data['guest']['customer_group_id']);
}

// Tracking Code
if (isset($request->get['tracking'])) {
    setcookie('tracking', $request->get['tracking'], time() + 3600 * 24 * 1000, '/');

    $db->query("UPDATE `" . DB_PREFIX . "marketing` SET clicks = (clicks + 1) WHERE code = '" .
               $db->escape($request->get['tracking']) . "'");
}

// Affiliate
$registry->set('affiliate', new Affiliate($registry));

// Currency
$registry->set('currency', new Currency($registry));

// Tax
$registry->set('tax', new Tax($registry));

// Weight
$registry->set('weight', new Weight($registry));

// Length
$registry->set('length', new Length($registry));

// Cart
$registry->set('cart', new Cart($registry));

// Encryption
$registry->set('encryption', new Encryption($config->get('config_encryption')));

$registry->set('currentCity', City::current());

$zone = explode('.', $_SERVER['HTTP_HOST']);

// Event
$event = new Event($registry);
$registry->set('event', $event);

$query = $db->query("SELECT * FROM " . DB_PREFIX . "event");

foreach ($query->rows as $result) {
    $event->register($result['trigger'], $result['action']);
}

// Front Controller
$controller = new Front($registry);

// Maintenance Mode
$controller->addPreAction(new Action('common/maintenance'));

// Инициализация SEO ЧПУ и маршрутизации Yii
$controller->addPreAction(new Action('startup/url'));

// Dispatch
$controller->dispatch(new Action($request->get['route'] ?? 'common/home'), new Action('error/not_found'));

// Output
$response->output();

/*

$content = siter_buffer_worker(ob_get_contents());
ob_end_clean();
siter_write_cache($content);
echo $content;*/

