<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 27.04.21 12:07:32
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Imagick;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use InvalidArgumentException;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

use function constant;
use function dirname;
use function file_exists;
use function filemtime;
use function is_dir;
use function is_file;
use function is_readable;
use function ltrim;
use function mkdir;
use function pathinfo;
use function round;
use function rtrim;
use function trim;

use const PATHINFO_EXTENSION;
use const YII_ENV_DEV;

/**
 * Компонент для создания превью картинок (настраивается через config Yii)
 */
class Image extends Component
{
    /** @var string */
    public const EXT_SVG = 'svg';

    /** @var string */
    public const MIME_SVG = 'image/svg';

    /** @var string название директории кеша */
    public const DIR_CACHE = 'cache';

    /** @var string заполнение белым цветом */
    public const FILL_WHITE = '#fff';

    /** @var string формат масштабированных файлов */
    public const FORMAT = 'jpg';

    /** @var ?string картинка no-image по-умолчанию */
    public $noImage = 'no_image.png';

    /** @var string заполнение по-умолчанию */
    public $fill = self::FILL_WHITE;

    /** @var ?string картинка водяного знака по-умолчанию */
    public $watermark;

    /** @var float качество сжатия файлов */
    public $quality = 0.95;

    /**
     * @inheritDoc
     *
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->quality <= 0 || $this->quality > 1) {
            throw new InvalidConfigException('quality');
        }
    }

    /**
     * Директория картинок.
     *
     * @return string
     */
    protected static function dirImage(): string
    {
        static $dir;

        if ($dir === null) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $dir = @constant('DIR_IMAGE');
            if (empty($dir)) {
                Yii::warning('Не установлена константа DIR_IMAGE', __METHOD__);
                $dir = $_SERVER['DOCUMENT_ROOT'] . '/image/';
            }
        }

        return $dir;
    }

    /**
     * Базовый путь каталога.
     *
     * @return string
     */
    protected static function urlCatalog(): string
    {
        static $url;

        if ($url === null) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $url = @constant('HTTP_CATALOG');
            if (empty($url)) {
                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $url = @constant('HTTP_SERVER');
                if (empty($url)) {
                    Yii::warning('Не установлена константа HTTP_CATALOG или HTTP_SERVER', __METHOD__);
                    $url = '/';
                }
            }
        }

        return $url;
    }

    /**
     * Формирует имя файла назначения.
     *
     * @param string $file относительное имя файла
     * @param int $width
     * @param int $height
     * @return string относительно имя кеша файла
     */
    public static function dst(string $file, int $width, int $height): string
    {
        $pathInfo = pathinfo(ltrim($file, '/'));

        $dirname = (string)($pathInfo['dirname'] ?? '');
        $filename = (string)($pathInfo['filename'] ?? '');
        $extension = (string)($pathInfo['extension'] ?? '');

        if ($filename === '' || $extension === '') {
            throw new InvalidArgumentException('file: ' . $file);
        }

        $dst = self::DIR_CACHE;
        if ($dirname !== '' && $dirname !== '.') {
            $dst .= '/' . $dirname;
        }

        return $dst . '/' . $filename . '-' . $width . 'x' . $height . '.' . $extension;
    }

    /**
     * Возвращает полный путь исходного файла.
     *
     * @param string $file
     * @return string
     */
    public static function path(string $file): string
    {
        return $file === '' ? '' : rtrim(static::dirImage(), '/') . '/' . $file;
    }

    /**
     * Возвращает URL файла.
     *
     * @param string $file относительное имя файла
     * @return string
     */
    public static function url(string $file): string
    {
        return rtrim(static::urlCatalog(), '/') . '/image/' . ltrim($file, '/');
    }

    /**
     * Создает превью картинки.
     *
     * @param string|null $src относительный путь исходного файла
     * @param float|string $width ширина
     * @param float|string $height высота
     * @param array $options опции
     * - string|bool|null $noimage - относительный путь noimage или true если по-умолчанию
     * - string|bool|null $fill - цвет для заполнения пустого пространство при непропорциональном масштабировании
     * - string|bool|null $watermark - относительный путь водяного знака
     * @return string|null относительный URL превью
     * @throws Exception
     * @throws ImagickPixelException
     */
    public function thumb(?string $src, $width = 0, $height = 0, array $options = []): ?string
    {
        // корректируем файл
        $src = trim((string)$src, '/');

        // ширина
        $width = round($width);
        if ($width < 0) {
            throw new InvalidArgumentException('width');
        }

        // высота
        $height = round($height);
        if ($height < 0) {
            throw new InvalidArgumentException('height');
        }

        // noimage
        $noImage = $options['noimage'] ?? null;
        if ($noImage === null || $noImage === true) {
            $noImage = $this->noImage;
        }

        // заполнение (false или цвет, например "fff")
        $fill = $options['fill'] ?? null;
        if ($fill === null || $fill === true) {
            $fill = $this->fill;
        }

        // водяной знак
        $watermark = $options['watermark'] ?? null;
        if ($watermark === null || $watermark === true) {
            $watermark = $this->watermark;
        }

        /** @var bool $isNoImage */
        $isNoImage = false;

        // проверяем наличие исходного файла
        $srcPath = static::path($src);
        if ($srcPath === '' || ! is_file($srcPath) || ! is_readable($srcPath)) {
            if (empty($noImage)) {
                return null;
            }

            $src = $noImage;
            $srcPath = static::path($src);
            $isNoImage = true;

            if (! is_file($srcPath) || ! is_readable($srcPath)) {
                throw new Exception('Недоступен noimage-файл: ' . $srcPath);
            }
        }

        // если файл векторный, то возвращаем исходный
        $ext = mb_strtolower(pathinfo($src, PATHINFO_EXTENSION));
        if ($ext === self::EXT_SVG) {
            return static::url($src);
        }

        // если файл назначения готов, то возвращаем без изменений
        $dst = static::dst($src, (int)$width, (int)$height);
        $dstPath = static::path($dst);

        if (is_file($dstPath) && filemtime($dstPath) >= filemtime($srcPath)) {
            return static::url($dst);
        }

        // создаем директорию назначения
        static::checkDir($dstPath);

        try {
            // читаем картинку
            $image = static::readImage($srcPath);

            // изменяем размер
            if ($width !== 0 || $height !== 0) {
                $image = $this->resizeImage($image, (int)$width, (int)$height, [
                    'fill' => $fill
                ]);
            }

            // водяной знак
            if (! empty($watermark) && ! $isNoImage) {
                static::watermarkImage($image, static::path($watermark));
            }

            // сохраняем
            $this->saveImage($image, $dstPath);
        } catch (ImagickException $ex) {
            throw new Exception('Ошибка обработки картинки', 0, $ex);
        }

        // возвращаем URL
        return static::url($dst);
    }

    /**
     * Проверяет наличие/создает пути.
     *
     * @param string $path полный путь файла
     * @throws Exception
     */
    protected static function checkDir(string $path): void
    {
        $dir = dirname($path);
        if ($dir === '') {
            throw new InvalidArgumentException('path: ' . $path);
        }

        $dir = FileHelper::normalizePath($dir);
        if (! YII_ENV_DEV && mb_strpos($dir, static::dirImage()) !== 0) {
            throw new Exception('Некорректный путь директории: ' . $dir);
        }

        // создаем директорию
        if (! file_exists($dir)) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            if (! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
                throw new Exception('Ошибка создания каталога: ' . $dir);
            }
        } elseif (! is_dir($dir)) {
            throw new Exception('Не директория: ' . $dir);
        }
    }

    /**
     * Читает изображение.
     *
     * @param string $path
     * @return Imagick
     * @throws ImagickException
     */
    protected static function readImage(string $path): Imagick
    {
        $image = new Imagick();
        $image->readImage($path);

        return $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
    }

    /**
     * Масштабирование картинки.
     *
     * @param Imagick $image
     * @param int $width
     * @param int $height
     * @param array $options
     * @return Imagick
     * @throws ImagickException
     * @throws ImagickPixelException
     */
    protected function resizeImage(Imagick $image, int $width, int $height, array $options = []): Imagick
    {
        // проверяем текущие размеры
        $w = $image->getImageWidth();
        $h = $image->getImageHeight();
        if ($w === $width && $h === $height) {
            return $image;
        }

        $fill = $options['fill'] ?? null;
        if ($fill === true) {
            $fill = $this->fill;
        }

        $image->setOption('filter:support', '2.0');
        $image->setColorspace(Imagick::COLORSPACE_SRGB);
        $image->setImageBackgroundColor(new ImagickPixel($fill ?: self::FILL_WHITE));
        $image->setImageInterlaceScheme(Imagick::INTERLACE_JPEG);

        // масштабировать вписывая в заданную область
        $bestFit = $width > 0 && $height > 0;

        // дополняем цветом заполнения до нужных размеров
        $fill = $bestFit && ! empty($fill);

        // масштабируем при необходимости заполняя пустое пространство
        if (! $image->thumbnailImage($width, $height, $bestFit, $fill)) {
            Yii::error('Ошибка создания thumbnail', __METHOD__);
        }

        return $image;
    }

    /**
     * Накладывает водяной знак.
     *
     * @param Imagick $image
     * @param string $path полный путь файла водяного знака
     * @return Imagick
     * @throws ImagickException
     */
    protected static function watermarkImage(Imagick $image, string $path): Imagick
    {
        $iWidth = $image->getImageWidth();
        $iHeight = $image->getImageHeight();

        try {
            // создаем картинку для водяного знака
            $watermark = new Imagick($path);
            $wWidth = $watermark->getImageWidth();
            $wHeight = $watermark->getImageHeight();

            // масштабируем водяной знак
            if ($wWidth !== $iWidth || $wHeight !== $iHeight) {
                $watermark->scaleImage($iWidth, $iHeight, true);
                $wWidth = $watermark->getImageWidth();
                $wHeight = $watermark->getImageHeight();
            }

            // накладываем на изображение
            $image->compositeImage(
                $watermark, Imagick::COMPOSITE_DEFAULT,
                (int)round(($iWidth - $wWidth) / 2), (int)round(($iHeight - $wHeight) / 2)
            );
        } finally {
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ($watermark !== null) {
                $watermark->clear();
                $watermark->destroy();
            }
        }

        return $image;
    }

    /**
     * Сохраняет картинку.
     *
     * @param Imagick $image
     * @param string $path полный путь назначения
     * @throws Exception
     * @throws ImagickException
     */
    protected function saveImage(Imagick $image, string $path): void
    {
        // формат
        if ($image->setImageFormat(self::FORMAT) === false) {
            throw new Exception('Ошибка установки формата картинки: ' . self::FORMAT);
        }

        $image->setImageCompression(Imagick::COMPRESSION_JPEG);

        // сжатие
        if ($image->setImageCompressionQuality((int)round($this->quality * 100)) === false) {
            throw new Exception('Ошибка установки качества изображения: ' . $this->quality);
        }

        // очищаем лишнюю информацию
        $image->stripImage();

        // сохраняем
        $image->writeImage($path);
        $image->clear();
        $image->destroy();
    }
}
