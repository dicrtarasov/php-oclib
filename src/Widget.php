<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 21:31:26
 */

declare(strict_types = 1);
namespace dicr\oclib;

use dicr\helper\Html;
use Throwable;
use yii\base\BaseObject;

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
    public function init() : void
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
     * Конвертирует в строку.
     *
     * @return string
     */
    public function __toString() : string
    {
        ob_start();
        ob_implicit_flush(0);

        try {
            echo $this->run();
        } catch (Throwable $ex) {
            /** @noinspection PhpUndefinedConstantInspection */
            trigger_error(DEBUG ? (string)$ex : $ex->getMessage(), E_USER_ERROR);
        } finally {
            $ret = ob_get_clean();
        }

        return trim($ret);
    }

    /**
     * Рендерит плагин.
     *
     * Функция должна выводить методом echo или возвращать string.
     */
    abstract public function run() : string;

    /**
     * Выводит виджет. Для удобства в коде вместо new.
     *
     * @param array $config
     * @return string
     */
    public static function widget(array $config = []) : string
    {
        return (string)(new static($config));
    }

    /**
     * Генерирует HTML подключения плагина.
     *
     * @param string $name название плагина.
     * @return string
     */
    public function plugin(string $name) : string
    {
        return Html::plugin('#' . $this->id, $name, $this->pluginOptions);
    }
}
