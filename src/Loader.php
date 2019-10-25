<?php
use dicr\oclib\Registry;
use dicr\oclib\Template;

/**
 * Загрузчик.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Loader
{
	public function controller($route, $data = array())
	{
		$parts = explode('/', str_replace('../', '', (string)$route));

		// Break apart the route
		while ($parts) {
			$file = DIR_APPLICATION . 'controller/' . implode('/', $parts) . '.php';
			$class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', implode('/', $parts));

			if (is_file($file)) {
				include_once($file);
				break;
			} else {
				$method = array_pop($parts);
			}
		}

		$controller = new $class(Registry::app());

		if (!isset($method)) {
			$method = 'index';
		}

		// Stop any magical methods being called
		if (substr($method, 0, 2) == '__') {
			return false;
		}

		$output = '';

		if (is_callable(array($controller, $method))) {
			$output = call_user_func(array($controller, $method), $data);
		}

		return $output;
	}

	/**
	 * Загрузка модели.
	 *
	 * @param string $model
	 * @param array $data аргументы
	 * @return object модель
	 */
	public function model(string $name)
	{
		$name = str_replace('../', '', (string)$name);
		$key = 'model_' . str_replace('/', '_', $name);

		// проверяем уже загруженную модель
		$model = Registry::app()->get($key);
		if (!empty($model)) {
		    return $model;
		}

		$file = DIR_APPLICATION . 'model/' . $name . '.php';
		if (file_exists($file)) {
			include_once($file);
            $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $name);
			$model = new $class(Registry::app());
			$this->registry->set($key, $model);
		} else {
			trigger_error('Error: Could not load model ' . $file . '!');
		}

		return $model;
	}

	/**
	 * Загружает темплейт.
	 *
	 * @param string $file относительный файл темплейа
	 * @param array $data данные для темплейта
	 * @return dicr\oclib\Template
	 */
	public function view($file, $data = [])
	{
        return (string)(new Template($file, $data));
	}


	public function helper($helper) {
		$file = DIR_SYSTEM . 'helper/' . str_replace('../', '', (string)$helper) . '.php';

		if (file_exists($file)) {
			include_once($file);
		} else {
			trigger_error('Error: Could not load helper ' . $file . '!');
			exit();
		}
	}

	public function config($config) {
		$this->registry->get('config')->load($config);
	}

	public function language($language) {
		return $this->registry->get('language')->load($language);
	}
}
