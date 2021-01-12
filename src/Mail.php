<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 12.01.21 17:19:41
 */

declare(strict_types = 1);
namespace dicr\oclib;

use dicr\validate\ValidateException;
use Yii;
use yii\base\Exception;

/**
 * Class Mail
 */
class Mail extends \yii\base\Model
{
    /** @var string|array */
    public $from;

    /** @var ?string */
    public $sender;

    /** @var string|array */
    public $to;

    /** @var ?string */
    public $replyTo;

    /** @var string */
    public $subject;

    /** @var ?string */
    public $text;

    /** @var ?string */
    public $html;

    /** @var string[] */
    public $attachments = [];

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            [['from', 'to'], 'required'],

            [['sender', 'replyTo'], 'trim'],
            [['sender', 'replyTo'], 'default'],

            ['subject', 'trim'],
            ['subject', 'required'],

            [['text', 'html'], 'trim'],
            [['text', 'html'], 'default']
        ];
    }

    /**
     * @param array|string $from
     * @return $this
     */
    public function setFrom($from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @param string $sender
     * @return $this
     */
    public function setSender(string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @param string|array $to
     * @return $this
     */
    public function setTo($to): self
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @param string|array $replyTo
     * @return $this
     */
    public function setReplyTo($replyTo): self
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function addAttachment(string $filename): self
    {
        $this->attachments[] = $filename;

        return $this;
    }

    /**
     * Отправка сообщения.
     *
     * @param bool $throw при ошибке выдавать Exception вместо возврата false
     * @return bool
     * @throws Exception
     */
    public function send(bool $throw = true): bool
    {
        if (! $this->validate()) {
            throw new ValidateException($this);
        }

        $message = Yii::$app->mailer->compose()
            ->setFrom($this->sender ? [$this->from => $this->sender] : $this->from)
            ->setTo($this->to)
            ->setSubject($this->subject);

        if ($this->replyTo !== null) {
            $message->setReplyTo($this->replyTo);
        }

        if ($this->text !== null) {
            $message->setTextBody($this->text);
        }

        if ($this->html !== null) {
            $message->setHtmlBody($this->html);
        }

        $ret = $message->send();

        if (! $ret && $throw) {
            throw new Exception('Ошибка отправки сообщения: ' . $this->to);
        }

        return $ret;
    }
}
