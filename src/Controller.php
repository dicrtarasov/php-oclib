<?php
namespace dicr\oclib;

/**
 * Конроллер.
 *
 * @property-read Url $url
 * @property-read DB $db
 * @property-read \Request $request
 * @property-read \Response $response
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
abstract class Controller
{
    /**
     * Получить значение реестра.
     *
     * @param string $key
     * @return mixed
     */
	public function __get(string $key)
	{
		return Registry::app()->get($key);
	}

	/**
	 * Установить значение реестра.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set(string $key, $value)
	{
		Registry::app()->set($key, $value);
	}

	/**
	 * Проверяет метод запроса POST.
	 *
	 * @return boolean
	 */
	public static function isPost()
	{
	    return (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') == 'POST');
	}

	/**
	 * Возвращает ответ как JSON.
	 *
	 * @param mixed $data
	 */
	public function asJson($data)
	{
	    $this->response->setOutput(Html::json($data));
	    header('Content-Type: application/json; charset=UTF-8', true);
	    exit;
	}
}