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

/**
 * Виджет.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
abstract class Widget extends AbstractObject
{
    /** @var string id */
    public $id;

    /** @var array опции тэга виджета */
    public $options = [];

    /** @var array опции javascript */
    public $pluginOptions = [];

    /**
     * Конструктор.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (empty($this->id)) {
            $this->id = self::generateId();
        }

        if (! isset($this->options['id'])) {
            $this->options['id'] = $this->id;
        }
    }

    /**
     * Генерирует id для виджета.
     *
     * @return string
     */
    protected static function generateId()
    {
        return str_replace('\\', '-', strtolower(static::class)) . '-' . mt_rand();
    }

    /**
     * Выводит виджет. Для удобства в коде вместо new.
     *
     * @param array $config
     * @return static
     */
    public static function widget(array $config)
    {
        return new static($config);
    }

    /**
     * Конверирует в строку.
     *
     * @return string
     */
    public function __toString()
    {
        $ret = '';

        try {
            $ret = $this->render();
        } catch (Throwable $ex) {
            /** @noinspection PhpUndefinedConstantInspection */
            trigger_error(DEBUG ? (string)$ex : $ex->getMessage(), E_USER_ERROR);
        }

        return $ret;
    }

    /**
     * Рендерит плагин.
     *
     * @return string
     */
    public function render()
    {
        // генерируем html
        return '';
    }

    /**
     * Генерирует HTML подключения плагина.
     *
     * @param string $name название плагина.
     * @return string
     */
    protected function plugin(string $name)
    {
        return Html::plugin('#' . $this->id, $name, $this->pluginOptions);
    }
}
