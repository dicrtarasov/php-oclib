<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 01.01.21 09:29:10
 */

declare(strict_types = 1);
namespace dicr\oclib;

use dicr\helper\Html;
use Throwable;
use yii\base\BaseObject;

use function trigger_error;

use const E_USER_ERROR;
use const YII_DEBUG;

/**
 * Виджет.
 */
abstract class Widget extends BaseObject
{
    /** @var string id */
    public $id;

    /** @var array опции тега виджета */
    public $options = [];

    /** @var array опции javascript */
    public $pluginOptions = [];

    /**
     * Инициализация.
     */
    public function init(): void
    {
        parent::init();

        if (! isset($this->id)) {
            $this->id = str_replace('\\', '-', strtolower(static::class)) . '-' . mt_rand();
        }

        if (! isset($this->options['id']) && ! empty($this->id)) {
            $this->options['id'] = $this->id;
        }
    }

    /**
     * Рендерит плагин.
     * Функция должна выводить методом echo или возвращать string.
     *
     * @return string
     */
    abstract public function run(): string;

    /**
     * Выводит виджет. Для удобства в коде вместо new.
     *
     * @param array $config
     * @return string
     */
    public static function widget(array $config = []): string
    {
        return (string)new static($config);
    }

    /**
     * Генерирует HTML подключения плагина.
     *
     * @param string $name название плагина.
     * @return string
     */
    public function plugin(string $name): string
    {
        return Html::plugin('#' . $this->id, $name, $this->pluginOptions);
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
}
