<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 20:27:03
 */

declare(strict_types = 1);

namespace dicr\oclib;

use yii\web\NotFoundHttpException;

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
     * @param string $route
     * @param int $priority
     */
    public function register(string $key, string $route, int $priority = 0) : void
    {
        $this->data[$key][] = [
            'action' => $route,
            'priority' => $priority,
        ];
    }

    /**
     * @param string $key
     * @param string $route
     */
    public function unregister(string $key, string $route) : void
    {
        if (isset($this->data[$key])) {
            foreach ($this->data[$key] as $index => $event) {
                if ($event['action'] === $route) {
                    unset($this->data[$key][$index]);
                }
            }
        }
    }

    /**
     * @param string $key
     * @param array|string|float $args
     * @throws NotFoundHttpException
     */
    public function trigger(string $key, $args = []) : void
    {
        if (isset($this->data[$key])) {
            usort($this->data[$key], static function (array $a, array $b) : int {
                return $a['priority'] <=> $b['priority'];
            });

            foreach ($this->data[$key] as $event) {
                $action = new Action($event['action'], (array)$args);
                $action->execute();
            }
        }
    }
}
