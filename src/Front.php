<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 02.01.21 03:14:31
 */

declare(strict_types = 1);

namespace dicr\oclib;

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
                $preAction->populate = true;
                $res = $preAction->execute();

                // если акция вернула какой-то результат, то останавливаем обработку и используем его
                if ($res !== null) {
                    $return = $res;
                    break;
                }
            }

            // пока в результате акция
            while ($return instanceof Action) {
                $return->populate = true;
                $return = $return->execute();
            }
        } catch (NotFoundHttpException $ex) {
            if ($errorAction !== null) {
                $errorAction->populate = true;
                $return = $errorAction->execute();
            } else {
                Yii::error('Акция ошибки не найдена: ' . $action->route, __METHOD__);
                $return = $ex;
            }
        } catch (Throwable $ex) {
            $return = $ex;
        }

        // если вернули строковой результат, то добавляем его в output
        if ($return instanceof \yii\web\Response) {
            Yii::$app->set('response', $return);
        } elseif ($return instanceof Throwable) {
            Yii::error($return, __METHOD__);
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
