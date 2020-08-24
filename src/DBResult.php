<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 24.08.20 16:50:59
 */

declare(strict_types = 1);

use yii\base\BaseObject;
use yii\db\Command;

/**
 * Результат запроса к базе данных OpenCart.
 *
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class DBResult extends BaseObject
{
    /** @var array[] все строки */
    public $rows = [];

    /** @var ?array первая строка */
    public $row;

    /** @var int */
    public $num_rows;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if (! isset($this->row)) {
            $this->row = ! empty($this->rows) ? $this->rows[0] : null;
        }

        $this->num_rows = ! empty($this->rows) ? count($this->rows) : 0;
    }

    /**
     * Создает результат из команды.
     *
     * @param Command $cmd
     * @return self
     * @throws \yii\db\Exception
     */
    public static function fromCommand(Command $cmd): self
    {
        return new self([
            'rows' => (array)$cmd->queryAll(PDO::FETCH_ASSOC)
        ]);
    }
}
