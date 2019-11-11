<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

use app\models\Categ;
use app\models\Prod;
use app\models\UrlAlias;
use app\models\UrlAliasFilter;
use PharIo\Version\InvalidPreReleaseSuffixException;
use yii\db\Query;

/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/**
 * Редактор алиасов.
 */
class ControllerSeoUrlAlias extends Controller
{
    /**
     * Индекс.
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function index()
    {
        $filter = new UrlAliasFilter();
        $filter->load(\Yii::$app->request->get());

        if (\Yii::$app->request->isPost) {
            foreach (\Yii::$app->request->post(UrlAlias::instance()->formName(), []) as $data) {
                $url_alias_id = (int)($data['url_alias_id'] ?? 0);
                $alias = $url_alias_id > 0 ? UrlAlias::findOne(['url_alias_id' => $url_alias_id]) : new UrlAlias();
                if (empty($alias)) {
                    continue;
                }

                if ($alias->load($data, '')) {
                    $alias->save(true);
                }
            }
        }

        $this->response->setOutput($this->load->view('seo/url_alias/index', [
            'filter' => $filter
        ]));
    }

    /**
     * Удаляе алиас.
     *
     * @throws \Throwable
     * @throws \yii\base\ExitException
     * @throws \yii\db\StaleObjectException
     */
    public function delete()
    {
        $url_alias_id = (int)($this->request->post['url_alias_id'] ?? 0);
        if ($url_alias_id < 1) {
            throw new \yii\base\InvalidArgumentException('url_alias_id');
        }

        $alias = UrlAlias::findOne(['url_alias_id' => $url_alias_id]);
        if (!empty($alias)) {
            $alias->delete();
        }

        self::asJson([]);
    }

    /**
     * Очистка неиспользуемых.
     */
    public function clean()
    {
        header('Content-Type: text/plain; charset=UTF-8');
        printf("Проверка и удаление не используемых алиасов....\n");

        $stat = UrlAlias::cleanUnused();

        foreach ($stat as $table => $deleted) {
            printf("%s удалено %d\n", $table, $deleted);
        }

        exit;
    }
}
