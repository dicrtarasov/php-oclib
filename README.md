# oc-yii2

Адаптер Yii2 позволяет подключить и использовать в OpenCart компоненты Yii методом реализации функций через библиотеку
Yii (кроме контроллеров).

## Подключение библиотек Yii2

Автозагрузка классов как сторонних библиотек, так и папки `system` выполняется через composer.

### composer.json

```composer
"require": {
    "php": ">=7.2",
    "dicr/php-oclib": "~3.1.5",
},
"autoload": {
    "classmap": ["system/engine/", "system/library/"],
    "files": ["system/helper/general.php", "system/helper/json.php", "system/helper/utf8.php"]
},
```

### Конфиги
- `/config/local.php` - опции локальной установки (протокол, домен, пароли, базы)
- `/config/common.php` - общий для Yii и OpenCart содержит основные пути
---
- `/config/opencart.php` - общий конфиг OpenCart для приложений /admin и /catalog
- `/config.php` - конфиг OpenCart для приложения /catalog
- `/admin/config.php` - OpenCart конфиг для приложения /admin
----
- `/config/yii.php` - общий конфиг Yii 
- `/config/yii.web.php` - конфиг Yii для Web
- `/config/yii.console.php` - конфиг для Yii для Console

### Инициализация
Yii Application создаётся и используется как контейнер компонентов и сервисов без `run`, а Web-контроллеры оставлены OpenCart.

##### /system/startup.php
// удаляем авто-загрузчик OpenCart
// spl_autoload_register('library');
// spl_autoload_register('vendor');
// spl_autoload_extensions('.php');

```php
// Подключаем авто-загрузчик Composer
require(__DIR__ . '/../vendor/autoload.php');

// константы YII_ENV и YII_DEBUG должны быть установлены до загрузки Yii
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', DEBUG);

// подключаем класс Yii
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

// создаем приложение Yii
new yii\web\Application(require(__DIR__ . '/../config/yii.web.php'));
```
## Маршрутизация
##### `/.htaccess` и `/admin/.htaccess`

```htaccess
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !.*\.(ico|gif|jpg|jpeg|png|js|css|pdf)
RewriteRule ^([^?]*) index.php?_route_=$1 [L,QSA]
```

### ЧПУ
Для работы ЧПУ добавляем стандартный preAction-контроллер ЧПУ в OpenCart, в `/index.php`,
и также в `/admin/index.php`  

```php
// Инициализация SEO ЧПУ и маршрутизации Yii
$controller->addPreAction(new Action('startup/url'));
```

При этом будут работать также короткие маршруты:
- вместо `/index.php?route=catalog/product&product_id=123`
- можно `/catalog/product?product_id=123`

В контроллере `/catalog/controllers/startup/url.php` используем наследование от контроллера oclib:

```php
class ControllerStartupUrl extends ControllerCatalogStartupUrl {}
```

### Прокси-классы
Требуется заменить классы OpenCart на пустышки из каталога `opencart`, наследующие одноименные классы `dicr\oclib`.

