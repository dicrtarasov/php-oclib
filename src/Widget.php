<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnused */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;
use yii\base\BaseObject;

/**
 * Виджет.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
abstract class Widget extends BaseObject
{
    /** @var string id */
    public $id;

    /** @var array опции тэга виджета */
    public $options = [];

    /** @var array опции javascript */
    public $pluginOptions = [];

    /**
     * Инициализация.
     */
    public function init()
    {
        parent::init();

        if (! isset($this->id)) {
            $this->id = str_replace('\\', '-', strtolower(static::class)) . '-' . mt_rand();
        }

        if (! isset($this->options['id']) && !empty($this->id)) {
            $this->options['id'] = $this->id;
        }
    }

    /**
     * Рендерит плагин.
     *
     * Функция должна выводить методом echo или возвращать string.
     */
    abstract public function run();

    /**
     * Конверирует в строку.
     *
     * @return string
     */
    public function __toString()
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
     * Выводит виджет. Для удобства в коде вместо new.
     *
     * @param array $config
     * @return string
     */
    public static function widget(array $config)
    {
        return (string)(new static($config));
    }

    /**
     * Генерирует HTML подключения плагина.
     *
     * @param string $name название плагина.
     * @return string
     */
    public function plugin(string $name)
    {
        return Html::plugin('#' . $this->id, $name, $this->pluginOptions);
    }
}
