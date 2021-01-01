<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 01.01.21 09:18:34
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\Exception;

/**
 * Модель для масштабирования картинок.
 */
class ModelToolImage extends Model
{
    /**
     * @param string|null $filename
     * @param float|string $width
     * @param float|string $height
     * @return ?string
     * @throws Exception
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function resize(?string $filename, $width = 0, $height = 0): ?string
    {
        return Image::thumb($filename, $width, $height);
    }
}
