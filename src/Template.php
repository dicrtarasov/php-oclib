<?php
namespace dicr\oclib;

/**
 * Темплейт.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2016
 */
class Template extends ArrayObject
{
	/** @var string файл */
	private $_file;

	/** @var array переменные */
	private $_data;

	/**
	 * Конструктор.
	 *
	 * @param string $file
	 * @param array $data
	 */
	public function __construct(string $file, array $data=[])
	{
	    // проверяем аргумент
	    if (empty($file)) {
	        throw new \InvalidArgumentException('file');
	    }

		$this->_file = $file;
		$this->_data = $data;
	}

	/**
	 * Возвращаеть путь файла.
	 *
	 * @return string полный путь файла для выполнения
	 */
	protected function getFilePath()
	{
	    $path = $this->_file;

	    // удаляем тему вначале
	    $matches = null;
	    if (preg_match('~^.+?\/template\/([^\/]+\/.+)$~uism', $path, $matches)) {
	        $path = $matches[1];
	    }

	    // полный путь
	    $path = rtrim(DIR_TEMPLATE, '/') . '/' . ltrim($path, '/');

	    // добавляем расширение
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if (empty($ext)) {
		    $path .= '.tpl';
		}

		return $path;
	}

 	/**
	 * Рендеринг темплейта в строку
	 *
	 * @return string
	 */
	public function render()
	{
	    // распаковываем данные
		extract($this->_data, EXTR_REFS|EXTR_SKIP);

		// выполняем файл
	    ob_start();
		require($this->getFilePath());
		return ob_get_clean();
	}

	/**
	 * Проверка наличия переменной.
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function _isset($key)
	{
	    return Registry::app()->has($key);
	}

	/**
	 * Возвращает значение переменной
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return Registry::app()->get($key);
	}

	/**
	 * Устанавливает значение переменной.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value)
	{
	    $this->_data[$key] = $value;
	}

	/**
	 * омпилирует в текст
	 *
	 * @return string
	 */
	public function __toString() {
	    $ret = null;

		try {
		    $ret = $this->render();
		} catch (\Throwable $ex) {
		    if (DEBUG) {
		        echo $ex;
		        exit;
		    }
		}

		return $ret;
	}

	/**
	 * Делает дамп данных
	 */
	public function dump($var = null)
	{
	    echo '<xmp>';
	    var_dump($this->_data[$var] ?? null);
	    exit;
	}
}
