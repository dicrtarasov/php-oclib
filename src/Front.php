<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 03:19:49
 */

declare(strict_types = 1);

namespace dicr\oclib;

use RuntimeException;
use yii\base\BaseObject;
use yii\web\NotFoundHttpException;

/**
 * Class Front
 */
class Front extends BaseObject
{
    /** @var Action[] */
    public $preActions = [];

    /** @var Action */
    public $errorAction;

    /**
     * @param Action $preAction
     */
    public function addPreAction(Action $preAction) : void
    {
        $this->preActions[] = $preAction;
    }

    /**
     * @param Action $action
     * @param ?Action $errorAction
     * @throws NotFoundHttpException
     */
    public function dispatch(Action $action, ?Action $errorAction = null) : void
    {
        if ($errorAction === null) {
            $errorAction = $this->errorAction;
        }

        // результат выполнения акции
        $res = null;

        try {
            // запускаем пред-акции
            foreach ($this->preActions as $preAction) {
                $result = $preAction->execute();

                // если в результате получили акцию, то заменяем ей основную
                if ($result instanceof Action) {
                    $action = $result;
                    break;
                }
            }

            // пока возвращается акция
            while ($action) {
                $res = $action->execute();
                $action = $res instanceof Action ? $res : null;
            }
        } catch (NotFoundHttpException $ex) {
            if ($errorAction !== null) {
                $res = $errorAction->execute();
            } else {
                throw new RuntimeException('Акция ошибки не найдена', 0, $ex);
            }
        }

        // если вернули строковой результат, то добавляем его в output
        if (is_scalar($res)) {
            $res = (string)$res;
            if ($res !== '') {
                Registry::app()->response->setOutput($res);
            }
        }
    }
}
