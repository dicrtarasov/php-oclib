<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 13.02.20 16:48:45
 */

declare(strict_types = 1);

namespace app\models;

use dicr\validate\ValidateException;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use function error_get_last;
use function file_get_contents;
use function http_build_query;
use function stream_context_create;
use function strlen;
use function trim;
use const CRM_BASE_URL;
use const CRM_LOGIN;
use const CRM_PASSWORD;

/**
 * Заявка в CRM на создание лида.
 *
 * @package app\components
 */
class CrmLeadRequest extends Model
{
    /** @var string путь запроса API */
    protected const REQUEST_PATH = '/crm/configs/import/lead.php';

    /** @var int таймаут запроса, сек */
    protected const TIMEOUT = 5;

    /** @var string User-Agent */
    protected const USER_AGENT = 'Dicr PHP API (http://dicr.org)';

    /** @var string источник */
    public $source;

    /** @var string страница сайта */
    public $page;

    /** @var string заголовок страницы */
    public $title;

    /** @var string описание заявки */
    public $description;

    /** @var string ФИО контрагента */
    public $name;

    /** @var string телефон контрагента */
    public $phone;

    /** @var string email контрагента */
    public $email;

    /** @var string собщение контрагента */
    public $message;

    /** @var string город доставки заказа */
    public $city;

    /** @var int номер визита roistat */
    public $roistat_visit;

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            ['source', 'trim'],
            ['source', 'default', 'value' => 'WEB'],

            ['page', 'trim'],
            ['page', 'default', 'value' => $_SERVER['HTTP_REFERER'] ?? ''],

            ['title', 'trim'],
            ['title', 'default', 'value' => 'Заявка'],

            ['description', 'trim'],
            ['description', 'default', 'value' => 'Лид с сайта ' . Yii::$app->name],

            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'min' => 3],

            ['phone', 'trim'],
            ['phone', 'required'],
            ['phone', 'string', 'min' => 7],

            ['email', 'trim'],
            ['email', 'email'],

            ['message', 'trim'],

            ['city', 'trim'],

            [
                'roistat_visit', 'default',
                'value' => (int)Yii::$app->request->cookies->getValue('roistat_visit', 0) ?: null
            ],
            ['roistat_visit', 'integer', 'min' => 1],
            ['roistat_visit', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true]
        ];
    }

    /**
     * Отправка запроса.
     *
     * @return void результат отправки
     * @throws ValidateException
     * @throws Exception
     */
    public function send()
    {
        if (! $this->validate()) {
            throw new ValidateException($this);
        }

        $data = [
            'LOGIN' => CRM_LOGIN,
            'PASSWORD' => CRM_PASSWORD,
            'SOURCE_ID' => $this->source,
            'SOURCE_DESCRIPTION' => $this->description,
            'TITLE' => $this->title,
            'NAME' => $this->name,
            'PHONE_WORK' => $this->phone,
            'EMAIL_WORK' => $this->email,
            'COMMENTS' => trim($this->message . "\nСтраница: " . $this->page),
            'ADDRESS_CITY' => $this->city,
            'UF_CRM_1580722495' => $this->roistat_visit
        ];

        Yii::warning($data, __METHOD__);

        $content = http_build_query($data);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen($content)
                ],
                'user_agent' => self::USER_AGENT,
                'content' => $content,
                'max_redirects' => 2,
                'timeout' => self::TIMEOUT,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $ret = @file_get_contents(CRM_BASE_URL . self::REQUEST_PATH, false, $ctx);
        if ($ret === false) {
            $error = error_get_last();
            throw new Exception('Ошибка отправки заявки в CRM: ' . $error['message']);
        }

        Yii::info($data, __METHOD__);
    }
}
