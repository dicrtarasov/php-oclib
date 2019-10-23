<?php
namespace dicr\oclib;

/**
 * Виджет.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Widget extends ArrayObject
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

        if (!isset($this->options['id'])) {
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
        return str_replace('\\', '-', strtolower(static::class)) . '-' . rand();
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

    /**
     * Рендерит плагин.
     *
     * @return string
     */
    public function render()
    {
        // генерируем html
    }

    /**
     * Конверирует в строку.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (\Throwable $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
        }
    }
}