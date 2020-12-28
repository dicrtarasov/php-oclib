<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 28.12.20 19:16:07
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;

use function base64_decode;

/**
 * Class Encryption
 */
class Encryption
{
    /** @var string */
    private $key;

    /**
     * Encryption constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = hash('sha256', $key, true);
    }

    /**
     * @param ?string $value
     * @return string
     */
    public function encrypt(?string $value) : string
    {
        $encrypted = Yii::$app->security->encryptByKey((string)$value, $this->key);

        return strtr(base64_encode($encrypted), '+/=', '-_,');
    }

    /**
     * @param ?string $value
     * @return string
     */
    public function decrypt(?string $value) : string
    {
        $unescaped = base64_decode(strtr((string)$value, '-_,', '+/='));

        return Yii::$app->security->decryptByKey($unescaped, $this->key);
    }
}
