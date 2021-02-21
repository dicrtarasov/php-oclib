<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 21.02.21 10:07:21
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
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
     */
    public function resize(?string $filename, $width = 0, $height = 0): ?string
    {
        /** @var Image $image */
        $image = Yii::$app->get('image');

        return $image->thumb($filename, $width, $height);
    }
}
