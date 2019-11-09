# oc-yii2
**Адаптер Yii2 для OpenCart** позволяет подключить и использоваь в OpenCart библиотеку компонентов Yii методом перенаправления сандартных функций OpenCart в вызовы функций Yii (кроме контроллеров).

## Подключение библиотек Yii2
### composer.json
```composer
"require": {
    "php": ">=7.2",
    "ext-mbstring": "*",
    "ext-pdo": "*",
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
// Yii
$config = require(__DIR__ . '/../config/yii.web.php');

// YII_ENV и YII_DEBUG должны быть усановлены в конфиге ранее
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

new yii\web\Application($config);
```
YII_ENV и YII_DEBUG должны быь установлены до загрузки библиотек Yii.

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
\Yii::$app->defaultRoute = 'common/dashboard';

if (!empty($request->get['route'])) {
    \Yii::$app->requestedRoute = $request->get['route'];
} elseif (!empty($request->get['_route_'])) {
    \Yii::$app->requestedRoute = $request->get['_route_'];
} else {
    \Yii::$app->requestedRoute = \Yii::$app->defaultRoute;
}
```

В catalog используеся preAction в /catalog/controllers/common/seo_url.php

## Прокси-классы
Требуется заменить классы OpenCart на пустышки из каталога `opencart`, наследующие одноименные классы dicr\oclib.

