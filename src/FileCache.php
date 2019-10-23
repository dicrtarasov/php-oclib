<?php
namespace dicr\oclib;

/**
 * Файловый кэш.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class FileCache extends \ArrayObject
{
    /** @var int время TTL по-умолчанию */
	public $ttl;

	/** @var float вероятность очистки кэша */
	public $gcProbability = 0.3;

	/**
	 * Конструктор.
	 *
	 * @param number $expire
	 */
	public function __construct(array $config = [])
	{
	    parent::__construct($config);

	    // очищаем кэш с заданной вероятностью
        $this->gc();
	}

	/**
	 * Возвращает ключ кэша.
	 *
	 * @param mixed $key
	 * @return string
	 */
	protected static function cacheKey($key)
	{
	    return md5(serialize($key));
	}

	/**
	 * Читает файлы из кэша.
	 *
	 * @param mixed $key
	 * @throws \Exception
	 * @return string[]
	 */
	protected function globFiles($key = null)
	{
	    if (empty($key)) {
	        $mask = '*';
	    } else {
	        $mask = self::cacheKey($key) . '.*';
	    }

	    $files = glob(DIR_CACHE . $mask, GLOB_NOSORT);
	    if ($files === false) {
	        throw new \Exception('Ошибка чтения кеша: ' . DIR_CACHE);
	    }

	    return $files;
	}

	/**
	 * Кодирует данные.
	 *
	 * @param mixed $data
	 * @return string
	 */
	protected function encode($data)
	{
	    return serialize($data);
	}

	/**
	 * Декодирует данные
	 *
	 * @param string $data
	 * @return mixed
	 */
	protected function decode(string $data)
	{
	    return unserialize($data);
	}

	/**
	 * Возвращает данные по ключу.
	 *
	 * @param mixed $key
	 * @throws \Exception
	 * @return mixed|false
	 */
	public function get($key)
	{
	    $files = $this->globFiles($key);
	    $filename = reset($files);
	    if (empty($filename)) {
	        return false;
	    }

	    $data = file_get_contents($filename, false);
	    if ($data === false) {
	        throw new \Exception('Ошибка чтения из кэша: ' . DIR_CACHE);
	    }

	    return $this->decode($data);
	}

	/**
	 * Сохраняет значение в кеше.
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @param int $ttl
	 * @throws \Exception
	 */
	public function set($key, $val, int $ttl = null)
	{
	    $filename = DIR_CACHE . sprintf('%s.%d', self::cacheKey($key), time() + $ttl ?: $this->ttl);
	    $val = $this->encode($val);

	    if (file_put_contents($filename, $val, LOCK_EX) === false) {
	        throw new \Exception('Ошибка сохранения файла в кеше: ' . DIR_CACHE);
	    }
	}

	/**
	 * Удалить значение из кэша.
	 *
	 * @param mixed $key
	 */
	public function delete($key)
	{
	    $files = $this->globFiles($key);
	    foreach ($files as $file) {
	        unlink($file);
	    }
	}

	/**
	 * Очищает старые файлы.
	 *
	 * @throws \Exception
	 */
	public function gc()
	{
	    if (rand(1, 100) > $this->gcProbability * 100) {
	        return;
	    }

        $time = time();
	    $files = $this->globFiles();
	    foreach ($files as $file) {
	        $matches = null;
	        if (preg_match('~^[^\.]+\.(\d+)$~uism', pathinfo($file, PATHINFO_BASENAME)) && $matches[1] < $time) {
                unlink($file);
	        }
	    }
	}

	/**
	 * Очищает весь кэш.
	 */
	public function clean()
	{
	    foreach ($this->globFiles() as $file) {
	        unlink($file);
	    }
	}
}