# oc-yii2
**Адаптер Yii2 для OpenCart** позволяет подключить и использоваь в OpenCart библиотеку компонентов Yii методом перенаправления сандартных функций OpenCart в вызовы функций Yii (кроме контроллеров).

## Подключение библиотек Yii2
Автозагрузка классов как сторонних библиотек, так и папки `system` выполняется через composer.

### composer.json
```composer
"require": {
    "php": ">=7.2",
    "dicr/php-oclib": "~3.1.5",
},
"autoload": {
    "classmap": ["system/"]
}
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
Web-контроллеры оставлены OpenCart, поэтому приложении Yii создается, настраивается и используются его компоненты.

##### /system/startup.php
```php
// Подключаем автозагрузчик Composer
require(__DIR__ . '/../vendor/autoload.php');

// удаляем автозагрузчик OpenCart
// spl_autoload_register('library');
// spl_autoload_register('vendor');
// spl_autoload_extensions('.php');

// константы YII_ENV и YII_DEBUG должны быть установлены до загрузки Yii
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', DEBUG);

// подключаем класс Yii
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

// создаем приложение Yii
new yii\web\Application(require(__DIR__ . '/../config/yii.web.php'));
```
## Маршруизация
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

