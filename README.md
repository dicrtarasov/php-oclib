# oc-yii2
**Адаптер Yii2 для OpenCart** позволяет подключить и использоваь в OpenCart библиотеку компонентов Yii методом перенаправления сандартных функций OpenCart в вызовы функций Yii (кроме контроллеров).

## Подключение библиотек Yii2
### composer.json
```composer
"require": {
    "php": ">=7.2",
    "dicr/php-oclib": "~3.1.5",
}
```
### Конфиги
Разделены на:
- `/config/local.php` - опции локальной усановки (пароли, базы)
- `/config/common.php` - общий для Yii и OpenCart содержит основные пути
- `/config/opencart.php` - общий для /catalog и /admin opencart
- `/admin/config.php` - конфиг для /admin
- `/config.php` - конфиг для /catalog
- `/config/yii.php` - общий для yii web и console
- `/config/yii.web.php` - конфиг для yii web
- `/config/yii.console.php` - конфиг для yii console

### Инициализация
Прилложение создается без запуска, так как Yii используется только как компоненты, а конроллеры работают в Opencart.

#### /system/startup.php
```php
// Composer
require(__DIR__ . '/../vendor/autoload.php');

// константы YII_ENV и YII_DEBUG должны быть установлены до загрузки Yii
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', DEBUG);

// подключаем класс Yii
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

// создаем приложение Yii
new yii\web\Application(require(__DIR__ . '/../config/yii.web.php'));
```
## Маршруизация
### /.htaccess, /admin/.htaccess
```
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !.*\.(ico|gif|jpg|jpeg|png|js|css|pdf)
RewriteRule ^([^?]*) index.php?_route_=$1 [L,QSA]
```

Также потребуется такой же .htaccess создать в /admin.

### /admin/index.php
```php
// инициализация маршрута Yii
$controller->addPreAction(new Action('startup/url'));

```

Для работы ЧПУ в catalog используеся аналогичный preAction в /catalog/controllers/startup/url.php

## Прокси-классы
Требуется заменить классы OpenCart на пустышки из каталога `opencart`, наследующие одноименные классы dicr\oclib.

