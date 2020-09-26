<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 22:22:17
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
use yii\log\Logger;

/**
 * Class Log
 */
class Log
{
    /**
     * @param string|array $message
     * @param int $level
     */
    public function write($message, int $level = Logger::LEVEL_ERROR)
    {
        Yii::getLogger()->log($message, $level, __METHOD__);
    }
}
