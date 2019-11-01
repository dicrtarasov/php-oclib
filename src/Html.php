<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use function in_array;
use function is_array;

/**
 * Html-helper.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Html
{
    /** @var string[] закрывающиеся теги */
    public const NOBODY_TAGS = [
        'meta',
        'link',
        'img',
        'input'
    ];

    /**
     * Деэкранирует из html.
     *
     * @param string $str
     * @return string
     */
    public static function decode($str)
    {
        return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Преобразует html в текст.
     *
     * @param string $html
     * @return string
     */
    public static function toText($html)
    {
        $html = (string)$html;
        $html = html_entity_decode($html, \ENT_QUOTES, 'utf-8');
        $html = html_entity_decode($html);
        $html = strip_tags($html);
        $html = (string)preg_replace('~[[:cntrl:]]+~uim', '', $html);
        return trim($html);
    }

    /**
     * Добавляет CSS-класс в аттрибуты
     *
     * @param array $attrs
     * @param string $class
     * @return array аттрибуты
     */
    public static function addCssClass(array &$attrs, string $class)
    {
        $class = trim($class);
        if (empty($class)) {
            return $attrs;
        }

        $classes = self::parseClasses($attrs['class'] ?? []);
        $classes[] = $class;
        $classes = array_unique($classes);
        $attrs['class'] = implode(' ', $classes);

        return $attrs;
    }

    /**
     * Парсит классы
     *
     * @param string|array $classes
     * @return string[]
     */
    protected static function parseClasses($classes)
    {
        if (empty($classes)) {
            return [];
        }

        return preg_split('~[\s]+~um', $classes, - 1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * HTML select
     *
     * @param string $name имя select
     * @param string|null $value текущее значение
     * @param array $vals значения val => text
     * @param array $options аттрибуты
     * @return string
     */
    public static function select(string $name, string $value, array $vals, array $options = [])
    {
        $value = (string)$value;

        $prepend = (array)($options['prepend'] ?? []);
        unset($options['prepend']);

        ob_start();

        $options['name'] = $name;

        echo self::startTag('select', $options);

        foreach ($prepend as $val => $text) {
            $opts = [
                'value' => $val
            ];

            if ($value === (string)$val) {
                $opts['selected'] = 'selected';
            }

            echo self::tag('option', self::esc($text), $opts);
        }

        foreach ($vals as $val => $text) {
            $opts = [
                'value' => $val
            ];

            if ($value === (string)$val) {
                $opts['selected'] = 'selected';
            }

            echo self::tag('option', self::esc($text), $opts);
        }

        echo self::endTag('select');

        return ob_get_clean();
    }

    /**
     * Начало тега
     *
     * @param string $name
     * @param array $attrs
     * @return string
     */
    public static function startTag(string $name, array $attrs = [])
    {
        ob_start();

        echo '<' . $name;

        $attrstr = self::attrs2str($attrs);
        if ($attrstr !== '') {
            echo ' ' . $attrstr;
        }

        if (in_array($name, self::NOBODY_TAGS)) {
            echo '/';
        }

        echo '>';

        return ob_get_clean();
    }

    /**
     * Html-тэг
     *
     * @param string $name
     * @param string $content
     * @param array $attrs
     * @return string
     */
    public static function tag(string $name, string $content = '', array $attrs = [])
    {
        ob_start();
        echo self::startTag($name, $attrs);
        if (! in_array($name, self::NOBODY_TAGS)) {
            echo $content;
            echo self::endTag($name);
        }
        return ob_get_clean();
    }

    /**
     * Экранирует строку HTML.
     *
     * @param string $val
     * @return string
     */
    public static function esc($val)
    {
        return htmlspecialchars((string)$val, ENT_QUOTES, 'utf-8');
    }

    /**
     * Конец тега
     *
     * @param string $name
     * @return string
     */
    public static function endTag(string $name)
    {
        return in_array($name, self::NOBODY_TAGS) ? '' : sprintf('</%s>', $name);
    }

    /**
     * Преобразует массив аттриутов в строку
     *
     * @param array $attrs
     * @return string
     */
    public static function attrs2str(array $attrs)
    {
        $str = [];
        foreach ($attrs as $name => $val) {
            switch ($name) {
                case 'class':
                    $val = self::class2str($val);
                    if ($val !== '') {
                        $str[] = sprintf('class="%s"', self::esc($val));
                    }
                    break;

                case 'style':
                    $val = self::style2str($val);
                    if ($val !== '') {
                        $str[] = sprintf('style="%s"', self::esc($val));
                    }
                    break;

                case 'data':
                    $val = self::data2attr($val);
                    if ($val !== '') {
                        $str[] = $val;
                    }

                    break;

                default:
                    if ($val === null || $val === false) {
                        continue 2;
                    }

                    if ($val === true) {
                        $str[] = trim($name);
                    } elseif (is_numeric($name)) {
                        $str[] = trim($val);
                    } else {
                        $str[] = sprintf('%s="%s"', $name, self::esc($val));
                    }
            }
        }

        return implode(' ', $str);
    }

    /**
     * Конвертирует значение HTML-аттрибуты class в строку
     *
     * @param string|array $class
     * @return string
     */
    protected static function class2str($class)
    {
        return trim(is_array($class) ? implode(' ', $class) : (string)$class);
    }

    /**
     * Конвертирует значение HTML-атрибута style в строку
     *
     * @param mixed $style
     * @return string
     */
    protected static function style2str($style)
    {
        if (is_array($style)) {
            $str = [];
            foreach ($style as $key => $val) {
                $str[] = is_numeric($key) ? $val : sprintf('%s: %s', $key, $val);
            }
            $str = implode('; ', $style);
        } else {
            $str = (string)$style;
        }

        return trim($str);
    }

    /**
     * Конвертирует значения в HTML data-аттрибуты
     *
     * @param array $data
     * @return string
     */
    protected static function data2attr(array $data)
    {
        $str = [];

        foreach ($data as $key => $val) {
            $val = trim($val);
            if ($val !== '') {
                $str[] = sprintf('data-%s="%s"', $key, self::esc($val));
            }
        }

        $str = implode(' ', $str);

        return trim($str);
    }

    /**
     * HTML img element
     *
     * @param string $src
     * @param array $attrs
     * @return string
     */
    public static function img(string $src, array $attrs = [])
    {
        if (! isset($attrs['alt'])) {
            $attrs['alt'] = 'img';
        }

        $attrs['src'] = $src;

        return self::tag('img', '', $attrs);
    }

    /**
     * HTML Meta element
     *
     * @param array $attrs
     * @return string
     */
    public static function meta(array $attrs = [])
    {
        return self::tag('meta', '', $attrs);
    }

    /**
     * HTML link rel="stylesheet".
     *
     * @param string $css
     * @return string
     */
    public static function cssLink(string $css)
    {
        return self::link(['rel' => 'stylesheet', 'href' => $css]);
    }

    /**
     * HTML Link element
     *
     * @param array $attrs
     * @return string
     */
    public static function link(array $attrs = [])
    {
        return self::tag('link', '', $attrs);
    }

    /**
     * Подключение скрипта
     *
     * @param string $js
     * @return string
     */
    public static function jsLink(string $js)
    {
        return self::tag('script', '', ['src' => $js]);
    }

    /**
     * HTML Button
     *
     * @param string $text
     * @param array $attrs
     * @return string
     */
    public static function button(string $text, array $attrs = [])
    {
        if (empty($attrs['type'])) {
            $attrs['type'] = 'button';
        }

        $esc = $attrs['esc'] ?? true;
        unset($attrs['esc']);

        if ($esc) {
            $text = self::esc($text);
        }

        return self::tag('button', $text, $attrs);
    }

    /**
     * HTML textarea
     *
     * @param string $name
     * @param string|null $text
     * @param array $attrs
     * @return string
     */
    public static function textarea(string $name, $text, array $attrs = [])
    {
        $attrs['name'] = $name;
        return self::tag('textarea', trim($text), $attrs);
    }

    /**
     * Html checkbox
     *
     * @param string $name
     * @param bool $checked
     * @param array $attrs
     * @return string
     */
    public static function checkbox(string $name, bool $checked, array $attrs = [])
    {
        if ($checked) {
            $attrs['checked'] = 'checked';
        }

        return self::input('hidden', $name, '0') . self::input('checkbox', $name, '1', $attrs);
    }

    /**
     * Html input
     *
     * @param string $type
     * @param string $name
     * @param string|null $value
     * @param array $attrs
     * @return string
     */
    public static function input(string $type, string $name, string $value = null, array $attrs = [])
    {
        if (! empty($type)) {
            $attrs['type'] = $type;
        }

        if (! empty($name)) {
            $attrs['name'] = $name;
        }

        if (isset($value)) {
            $attrs['value'] = $value;
        }

        return self::tag('input', '', $attrs);
    }

    /**
     * Html font awesome icon
     *
     * @param string $name
     * @param array $attrs
     * @return string
     */
    public static function fa(string $name, array $attrs = [])
    {
        if (empty($attrs['class'])) {
            $attrs['class'] = [];
        } elseif (is_scalar($attrs['class'])) {
            $attrs['class'] = preg_split('~\s+~um', $attrs['class'], - 1, PREG_SPLIT_NO_EMPTY);
        }

        $attrs['class'][] = 'fa';
        $attrs['class'][] = 'fa-' . $name;

        return self::tag('i', '', $attrs);
    }

    /**
     * Html font awesome icon
     *
     * @param string $name
     * @param array $attrs
     * @return string
     */
    public static function fas(string $name, array $attrs = [])
    {
        if (empty($attrs['class'])) {
            $attrs['class'] = [];
        } elseif (is_scalar($attrs['class'])) {
            $attrs['class'] = preg_split('~\s+~um', $attrs['class'], - 1, PREG_SPLIT_NO_EMPTY);
        }

        $attrs['class'][] = 'fas';
        $attrs['class'][] = 'fa-' . $name;

        return self::tag('i', '', $attrs);
    }

    /**
     * Ссылка
     *
     * @param string $href
     * @param string $html
     * @param string $attrs
     * @return string
     */
    public static function a(string $href, string $html = '', string $attrs = '')
    {
        return sprintf('<!--suppress HtmlUnknownAttribute, HtmlUnknownTarget --><a href="%s" %s>%s</a>',
            self::esc($href), $attrs, $html ?: self::esc($href));
    }

    /**
     * Генерирует скрипт подключения jQuery плагина.
     *
     * @param string $target
     * @param string $name плагин
     * @param array $options опции плагина
     * @return string html
     */
    public static function plugin(string $target, string $name, array $options = [])
    {
        return '<script>$(function() {$("' . $target . '").' . $name . '(' . self::json($options) . ');})</script>';
    }

    /**
     * Конвертирует в json
     *
     * @param mixed $obj
     * @return string json
     */
    public static function json($obj)
    {
        return json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
