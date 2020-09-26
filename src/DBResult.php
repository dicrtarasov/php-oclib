<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 18:55:14
 */

declare(strict_types = 1);
namespace dicr\oclib;

use PDO;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\Command;
use yii\db\Exception;

use function count;
use function is_array;

/**
 * Результат запроса к базе данных OpenCart.
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
     * @throws InvalidConfigException
     */
    public function init() : void
    {
        parent::init();

        if (empty($this->rows)) {
            $this->rows = [];
        } elseif (! is_array($this->rows)) {
            throw new InvalidConfigException('rows');
        }

        if (! isset($this->row)) {
            $this->row = $this->rows[0] ?? null;
        }

        $this->num_rows = count($this->rows);
    }

    /**
     * Создает результат из команды.
     *
     * @param Command $cmd
     * @return self
     * @throws Exception
     */
    public static function fromCommand(Command $cmd): self
    {
        return new self([
            'rows' => (array)$cmd->queryAll(PDO::FETCH_ASSOC)
        ]);
    }
}
