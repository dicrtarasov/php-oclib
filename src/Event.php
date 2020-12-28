<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 28.12.20 19:26:18
 */

declare(strict_types = 1);

namespace dicr\oclib;

use yii\web\NotFoundHttpException;

use function is_string;
use function usort;

/**
 * Class Event
 */
class Event
{
    /** @var array[] */
    private $data = [];

    /**
     * @param string $key
     * @param string|Action $action
     * @param int $priority
     */
    public function register(string $key, $action, int $priority = 0) : void
    {
        if (is_string($action)) {
            $action = new Action($action);
        }

        $this->data[$key][] = [
            'action' => $action,
            'priority' => $priority,
        ];
    }

    /**
     * @param string $key
     * @param string|Action $action
     */
    public function unregister(string $key, $action) : void
    {
        if ($action instanceof Action) {
            $action = $action->route;
        }

        if (isset($this->data[$key])) {
            foreach ($this->data[$key] as $index => $event) {
                if ($event['action'] === $action) {
                    unset($this->data[$key][$index]);
                }
            }
        }
    }

    /**
     * @param string $key
     * @param array|string|float|null $args
     * @throws NotFoundHttpException
     */
    public function trigger(string $key, $args = null) : void
    {
        if (isset($this->data[$key])) {
            usort($this->data[$key], static function (array $a, array $b) : int {
                return $a['priority'] <=> $b['priority'];
            });

            foreach ($this->data[$key] as $event) {
                /** @var Action $action */
                $action = $event['action'];

                if ($args !== null) {
                    $action->args = $args;
                }

                $action->execute();
            }
        }
    }
}
