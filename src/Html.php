<?php
namespace dicr\oclib;

/**
 * Html-helper.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Html
{
    /**
     * Экранирует строку HTML.
     *
     * @param string $val
     * @return string
     */
    public static function esc($val)
    {
        return htmlspecialchars($val, ENT_QUOTES, 'utf-8');
    }

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
	public static function toText(string $html)
	{
	    $html = html_entity_decode($html, null, 'utf-8');
	    $html = html_entity_decode($html);
	    $html = strip_tags($html);
	    $html = preg_replace('~[[:cntrl:]]+~uism', '', $html);
	    return trim($html);
	}

	/**
	 * Конвертирует в json
	 *
	 * @param mixed $obj
	 * @return string json
	 */
	public static function json($obj)
	{
		return json_encode($obj, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Ссылка
	 *
	 * @param string $href
	 * @param string $text
	 * @return string
	 */
	public static function a(string $href, string $text='', string $attrs='')
	{
	    return sprintf('<a href="%s" %s>%s</a>', self::esc($href), $attrs, self::esc($text ?: $href));
	}

    /**
	 * Тег meta.
	 *
	 * @param array $options
	 * @return string
	 */
	public static function meta(array $options)
	{
	    $opts = [];
	    foreach ($options as $key => $val) {
	        $opts[] = sprintf('%s="%s"', $key, self::esc($val));
	    }

	    return sprintf('<meta %s/>', implode(' ', $opts));
	}

	/**
	 * Тег link.
	 *
	 * @param array|string $options если string, то href и rel=stylesheet
	 * @return string
	 */
	public static function link($options)
	{
	    if (empty($options)) {
	        return '';
	    } elseif (!is_array($options)) {
	        $options = [
	            'href' => $options,
	            'rel' => 'stylesheet'
	        ];
	    }

	    $opts = [];
	    foreach ($options as $key => $val) {
	        $opts[] = sprintf('%s="%s"', $key, self::esc($val));
	    }

	    return sprintf('<link %s/>', implode(' ', $opts));
	}

	/**
	 * Генерирует скрипт подключения jQuery плагина.
	 *
	 * @param string $id элемент
	 * @param string $name плагин
	 * @param array $options опции плагина
	 * @return string html
	 */
    public static function plugin(string $target, string $name, array $options = [])
    {
        return sprintf('<script>$("%s").%s(%s);</script>',
            $target, $name, self::json($options)
        );
    }
}