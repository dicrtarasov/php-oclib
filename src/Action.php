<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 19:07:26
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
use yii\web\NotFoundHttpException;

use function method_exists;

/**
 * Class Action.
 */
class Action extends \yii\base\Action
{
    /**
     * Выполнение акции.
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function run()
    {
        if (Yii::$app->requestedAction === null) {
            Yii::$app->requestedAction = $this;
        }

        if (Yii::$app->requestedParams === null) {
            Yii::$app->requestedParams = $this->controller->actionParams;
        }

        // устанавливаем маршрут в Yii
        Yii::$app->requestedRoute = $this->uniqueId;

        Yii::$app->controller = $this->controller;

        // сохраняем парамеры в Yii
        Yii::$app->request->queryParams = Registry::app()->request->get;

        // проверяем наличие метода
        if (! method_exists($this->controller, $this->id)) {
            throw new NotFoundHttpException('method=' . $this->id . ', controller=' . $this->controller->id);
        }

        return $this->controller->{$this->id}($this->controller->actionParams);
    }

    /**
     * @inheritDoc
     * @param array $params
     * @return mixed|void|null
     * @throws NotFoundHttpException
     */
    public function runWithParams($params = [])
    {
        $this->controller->actionParams = $params;

        return $this->run();
    }
}
