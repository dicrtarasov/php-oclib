<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 14.03.21 03:12:38
 */

declare(strict_types = 1);
namespace dicr\oclib;

use dicr\asset\BaseResAsset;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

use function extract;
use function is_array;
use function is_string;
use function ob_get_clean;
use function ob_start;
use function pathinfo;
use function preg_match;
use function rtrim;
use function trigger_error;
use function trim;

use const E_USER_ERROR;
use const EXTR_REFS;
use const EXTR_SKIP;
use const PATHINFO_EXTENSION;
use const YII_DEBUG;

/**
 * Темплейт для OpenCart с проксированием к Registry.
 * Требует DIR_TEMPLATE.
 */
class Template implements RegistryProps
{
    use RegistryProxy;

    /** @var string расширение по-умолчанию */
    public const EXT_DEFAULT = 'tpl';

    /** @var string маршрут или полный путь файла */
    private $_route;

    /** @var array переменные */
    private $_vars;

    /**
     * Конструктор.
     *
     * @param string $route маршрут или полный путь файла
     * @param array $vars переменные шаблона
     */
    public function __construct(string $route, array $vars = [])
    {
        // удаляем тему вначале
        $matches = null;
        if (preg_match('~^(.+?/)?template/([^/]+/.+)$~uism', $route, $matches)) {
            $route = $matches[2];
        }

        $this->_route = trim($route, '/');

        $this->_vars = $vars;
    }

    /**
     * Директория темплейтов.
     *
     * @return string
     */
    private static function dirTemplate(): string
    {
        /** @noinspection PhpUndefinedConstantInspection */
        return DIR_TEMPLATE;
    }

    /** @var string */
    private $_filePath;

    /**
     * Путь файла.
     *
     * @return string полный путь файла для выполнения
     */
    public function filePath(): string
    {
        if ($this->_filePath === null) {
            $path = $this->_route;

            // добавляем расширение
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (empty($ext)) {
                $path .= '.' . self::EXT_DEFAULT;
            }

            $this->_filePath = rtrim(static::dirTemplate(), '/') . '/' . $path;
        }

        return $this->_filePath;
    }

    /**
     * Рендеринг темплейта в строку
     *
     * @return string
     */
    public function run(): string
    {
        // функция для сокрытия локальных переменных
        $run = function() {
            // распаковываем данные
            extract($this->_vars, EXTR_REFS | EXTR_SKIP);

            /** @noinspection PhpIncludeInspection */
            require $this->filePath();
        };

        if ($this->_route === 'common/header') {
            $this->beginPage();
        }

        try {
            ob_start();
            $run();
        } finally {
            $ret = ob_get_clean();
        }

        if ($this->_route === 'common/footer') {
            // выводим футер
            echo $ret;

            // завершаем всю страницу
            $ret = ob_get_clean();

            // начинаем свой буфер
            ob_start();

            // начинаем буфер страницы
            ob_start();

            // выводим всю страницу
            echo $ret;

            // обрабатываем и завершаем буфер страницы
            $this->endPage();

            // завершаем свой буфер
            $ret = ob_get_clean();
        }

        return $ret;
    }

    /**
     * Конвертирует в строку.
     *
     * @return string
     */
    public function __toString(): string
    {
        try {
            return $this->run();
        } catch (Throwable $ex) {
            trigger_error(YII_DEBUG ? (string)$ex : $ex->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * Рендерит шаблон.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public static function render(string $route, array $params = []): string
    {
        $tpl = new static($route, $params);

        return (string)$tpl;
    }

    /**
     * Начало страницы
     */
    public function beginPage(): void
    {
        Yii::$app->view->beginPage();
    }

    /**
     * Конец страницы.
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function endPage(): void
    {
        Yii::$app->view->endPage();
    }

    /**
     * Помечает заголовок страницы.
     */
    public function head(): void
    {
        Yii::$app->view->head();
    }

    /**
     * Помечает начало страницы.
     */
    public function beginBody(): void
    {
        Yii::$app->view->beginBody();
    }

    /**
     * Помечает конец страницы.
     */
    public function endBody(): void
    {
        Yii::$app->view->endBody();
    }

    /**
     * Регистрирует ресурсы
     *
     * @param string|array $asset
     * @throws Exception
     */
    public function registerAsset($asset): void
    {
        if (is_string($asset)) {
            Yii::$app->view->registerAssetBundle($asset);
        } elseif (is_array($asset)) {
            BaseResAsset::registerConfig(Yii::$app->view, $asset);
        } else {
            throw new InvalidArgumentException('asset');
        }
    }
}
