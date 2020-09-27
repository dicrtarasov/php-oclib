<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 27.09.20 19:55:16
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Class Mail
 */
class Mail
{
    /** @var string|array */
    protected $from;

    /** @var ?string */
    protected $sender;

    /** @var string|array */
    protected $to;

    /** @var string */
    protected $reply_to;

    /** @var string */
    protected $subject;

    /** @var ?string */
    protected $text;

    /** @var ?string */
    protected $html;

    /** @var string[] */
    protected $attachments = [];

    /**
     * Mail constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param array|string $from
     */
    public function setFrom($from) : void
    {
        $this->from = $from;
    }

    /**
     * @param string $sender
     */
    public function setSender(string $sender) : void
    {
        $this->sender = $sender;
    }

    /**
     * @param string|array $to
     */
    public function setTo($to) : void
    {
        $this->to = $to;
    }

    /**
     * @param string|array $reply_to
     */
    public function setReplyTo($reply_to) : void
    {
        $this->reply_to = $reply_to;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject) : void
    {
        $this->subject = $subject;
    }

    /**
     * @param string $text
     */
    public function setText(string $text) : void
    {
        $this->text = $text;
    }

    /**
     * @param string $html
     */
    public function setHtml(string $html) : void
    {
        $this->html = $html;
    }

    /**
     * @param string $filename
     */
    public function addAttachment(string $filename) : void
    {
        $this->attachments[] = $filename;
    }

    /**
     * Отправка сообщения.
     *
     * @return bool
     * @throws InvalidConfigException
     */
    public function send() : bool
    {
        if ($this->from === null) {
            throw new InvalidConfigException('from');
        }

        if ($this->to === null) {
            throw new InvalidConfigException('to');
        }

        if ($this->subject === null) {
            throw new InvalidConfigException('subject');
        }

        $message = Yii::$app->mailer->compose()
            ->setFrom($this->sender ? [$this->from => $this->sender] : $this->from)
            ->setTo($this->to)
            ->setSubject($this->subject);

        if ($this->reply_to !== null) {
            $message->setReplyTo($this->reply_to);
        }

        if ($this->text !== null) {
            $message->setTextBody($this->text);
        }

        if ($this->html !== null) {
            $message->setHtmlBody($this->html);
        }

        return $message->send();
    }
}
