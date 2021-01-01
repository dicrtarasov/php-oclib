<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 01.01.21 07:21:54
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
    public function addPreAction(Action $preAction): void
    {
        $this->preActions[] = $preAction;
    }

    /**
     * @param Action $action
     * @param ?Action $errorAction
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function dispatch(Action $action, ?Action $errorAction = null): void
    {
        if ($errorAction === null) {
            $errorAction = $this->errorAction;
        }

        // результат работы по-умолчанию - акция по-умолчанию
        $return = $action;

        try {
            // запускаем пред-акции
            foreach ($this->preActions as $preAction) {
                Yii::$app->requestedRoute = $preAction->route;
                Yii::$app->requestedParams = $preAction->args;
                $res = $preAction->execute();

                // если акция вернула какой-то результат, то останавливаем обработку и используем его
                if ($res !== null) {
                    $return = $res;
                    break;
                }
            }

            // пока в результате акция
            while ($return instanceof Action) {
                Yii::$app->requestedRoute = $return->route;
                Yii::$app->requestedParams = $return->args;
                $return = $return->execute();
            }
        } catch (NotFoundHttpException $ex) {
            if ($errorAction !== null) {
                $return = $errorAction->execute();
            } else {
                throw new RuntimeException('Акция ошибки не найдена', 0, $ex);
            }
        }

        // если вернули строковой результат, то добавляем его в output
        if ($return instanceof \yii\web\Response) {
            Yii::$app->set('response', $return);
        } elseif ($return instanceof Throwable) {
            throw $return;
        } elseif (is_scalar($return)) {
            $return = (string)$return;
            if ($return !== '') {
                Yii::$app->response->content = $return;
            }
        } elseif ($return !== null) {
            Yii::$app->response->data = $return;
        }
    }
}
