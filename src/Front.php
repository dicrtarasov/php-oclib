<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 28.12.20 18:51:07
 */

declare(strict_types = 1);

namespace dicr\oclib;

use RuntimeException;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\web\NotFoundHttpException;

use function is_scalar;

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
     * @throws Throwable
     */
    public function dispatch(Action $action, ?Action $errorAction = null) : void
    {
        if ($errorAction === null) {
            $errorAction = $this->errorAction;
        }

        Yii::$app->requestedRoute = $action->route;
        Yii::$app->requestedParams = $action->args;

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
        if ($res instanceof \yii\web\Response) {
            Yii::$app->set('response', $res);
        } elseif ($res instanceof Throwable) {
            throw $res;
        } elseif (is_scalar($res)) {
            $res = (string)$res;
            if ($res !== '') {
                Yii::$app->response->content = $res;
            }
        } elseif ($res !== null && $res !== '') {
            Yii::$app->response->data = $res;
        }
    }
}
