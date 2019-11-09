<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

use app\models\UrlAlias;
use app\models\UrlAliasFilter;
use PharIo\Version\InvalidPreReleaseSuffixException;

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

    public function alias()
    {
        $selfRoute = $this->request->get['route'];
        $pageParams = ['token' => $this->session->data['token']];
        $page = ! empty($this->request->request['page']) ? (int)$this->request->request['page'] : 0;
        if ($page < 0) {
            throw new Exception('invalid page');
        }
        if ($page > 0) {
            $pageParams['page'] = $page;
        }
        $limit = ! empty($this->request->request['limit']) ? (int)$this->request->request['limit'] : 0;
        if ($limit < 0) {
            throw new Exception('invalid limit');
        }
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit != 100) {
            $pageParams['limit'] = $limit;
        }
        foreach (['query', 'keyword'] as $field) {
            $$field = ! empty($this->request->request[$field]) ? trim($this->request->request[$field]) : null;
            if (! empty($$field)) {
                $pageParams[$field] = $$field;
            }
        }
        $urlAction = $this->url->link($selfRoute, http_build_query($pageParams), 'SSL');
        $this->request->server['REQUEST_METHOD'] = ! empty($this->request->server['REQUEST_METHOD']) ?
            strtoupper(trim($this->request->server['REQUEST_METHOD'])) : 'GET';
        switch ($this->request->server['REQUEST_METHOD']) {
            case 'GET':
                $wheres = [];
                foreach (['keyword', 'query'] as $field) {
                    if (! empty($$field)) {
                        $wheres[] = sprintf('`%s` like "%%%s%%"', $field, $this->db->escape($$field));
                    }
                }
                // get total count
                $sql = sprintf('select count(`url_alias_id`) as `count` from `%surl_alias`', DB_PREFIX);
                if (! empty($wheres)) {
                    $sql .= ' where ' . implode(' and ', $wheres);
                }
                $res = $this->db->query($sql);
                $total = $res->num_rows > 0 ? (int)$res->row['count'] : 0;
                // get aliases
                $aliases = [];
                if ($total > 0) {
                    $sql = sprintf('select * from `%surl_alias`', DB_PREFIX);
                    if (! empty($wheres)) {
                        $sql .= ' where ' . implode(' and ', $wheres);
                    }
                    $sql .= sprintf(' order by `keyword` limit %d,%d', $page * $limit, $limit);
                    $aliases = $this->db->query($sql);
                    $aliases = $aliases->num_rows > 0 ? $aliases->rows : [];
                }
                $totalPages = (int)ceil($total / $limit);
                if ($page > $totalPages - 1) {
                    $page = $totalPages - 1;
                }
                $showPages = 5;
                if ($showPages > $totalPages) {
                    $showPages = $totalPages;
                }
                $firstPage = $page - (int)floor($showPages / 2);
                $lastPage = $page + (int)floor($showPages / 2);
                if ($firstPage < 0) {
                    $firstPage = 0;
                    $lastPage = $firstPage + $showPages - 1;
                }
                if ($lastPage > $totalPages - 1) {
                    $lastPage = $totalPages - 1;
                    $firstPage = $lastPage - $showPages + 1;
                }
                $pager = ['prev' => null, 'pages' => [], 'next' => null];
                $tmp = $pageParams;
                if ($page > 0) {
                    $tmp['page'] = $page - 1;
                    $pager['prev'] = $this->url->link($selfRoute, $tmp, 'SSL');
                }
                if ($page < $totalPages - 1) {
                    $tmp['page'] = $page + 1;
                    $pager['next'] = $this->url->link($selfRoute, $tmp, 'SSL');
                }
                for ($i = $firstPage; $i <= $lastPage; $i ++) {
                    $tmp['page'] = $i;
                    $pager['pages'][$i + 1] = $this->url->link($selfRoute, $tmp, 'SSL');
                }
                $this->response->setOutput($this->load->view('tool/seo_alias.tpl', [
                    'total' => $total,
                    'aliases' => $aliases,
                    'page' => $page,
                    'limit' => $limit,
                    'pager' => $pager,
                    'keyword' => $keyword, // определена как $$field
                    'query' => $query, // определена как $$field
                    'header' => $this->load->controller('common/header'),
                    'column_left' => $this->load->controller('common/column_left'),
                    'footer' => $this->load->controller('common/footer'),
                    'breadcrumbs' => [
                        [
                            'text' => 'Главная',
                            'href' => $this->url->link('common/dashboard', ['token' => $this->session->data['token']],
                                'SSL')
                        ],
                        [
                            'text' => 'ЧПУ алиасы',
                            'href' => $this->url->link('tool/seo/alias', ['token' => $this->session->data['token']],
                                'SSL')
                        ]
                    ],
                    'urlAction' => $urlAction
                ]));
                break;

            case 'POST':
                $aliases = ! empty($this->request->post['aliases']) ? $this->request->post['aliases'] : [];
                if (! is_array($aliases)) {
                    throw new Exception('invalid aliases');
                }
                foreach ($aliases as $alias) {
                    $alias['url_alias_id'] = ! empty($alias['url_alias_id']) ? (int)$alias['url_alias_id'] : 0;
                    if ($alias['url_alias_id'] < 0) {
                        throw new Exception('invalid url_alias_id');
                    }
                    $alias['keyword'] = ! empty($alias['keyword']) ? trim($alias['keyword']) : null;
                    $alias['query'] = ! empty($alias['query']) ? trim($alias['query']) : null;
                    if (empty($alias['keyword']) && empty($alias['query'])) {
                        if (! empty($alias['url_alias_id'])) {
                            $this->db->query(sprintf('delete from `%surl_alias` where `url_alias_id`=%d limit 1',
                                DB_PREFIX, $alias['url_alias_id']));
                        }
                    } else {
                        $fields = [];
                        if (empty($alias['keyword'])) {
                            throw new Exception('empty alias keyword');
                        }
                        $fields[] = sprintf('`keyword`="%s"', $this->db->escape($alias['keyword']));
                        if (empty($alias['query'])) {
                            throw new Exception('empty alias query');
                        }
                        parse_str($alias['query'], $alias['query']);
                        $alias['query'] = Seo::normalizeArgs($alias['query']);
                        if (empty($alias['query'])) {
                            throw new Exception('empty alias query');
                        }
                        $fields[] = sprintf('`query`="%s"', $this->db->escape(http_build_query($alias['query'])));
                        if ($alias['url_alias_id'] > 0) {
                            $this->db->query(sprintf('update `%surl_alias` set %s where `url_alias_id`=%d limit 1',
                                DB_PREFIX, implode(',', $fields), $alias['url_alias_id']));
                        } else {
                            $this->db->query(sprintf('insert into `%surl_alias` set %s', DB_PREFIX,
                                implode(',', $fields)));
                        }
                    }
                }
                header('Location: ' . $urlAction, true, 303);
                exit;

            case 'DELETE':
                $_DELETE = [];
                parse_str(file_get_contents('php://input'), $_DELETE);
                $url_alias_id = ! empty($_DELETE['url_alias_id']) ? (int)$_DELETE['url_alias_id'] : 0;
                if ($url_alias_id < 1) {
                    throw new Exception('invalid url_alias_id');
                }
                $this->db->query(sprintf('delete from `%surl_alias` where `url_alias_id`=%d limit 1', DB_PREFIX,
                    $url_alias_id));
                header('Content-Type: application/json; charset=UTF-8', true, 200);
                echo json_encode([]);
                exit;
        }
    }

    public function crosslinks()
    {
        $this->request->server['REQUEST_METHOD'] = ! empty($this->request->server['REQUEST_METHOD']) ?
            strtoupper(trim($this->request->server['REQUEST_METHOD'])) : 'GET';
        switch ($this->request->server['REQUEST_METHOD']) {
            case 'GET':
                if (! empty($this->request->get['export'])) {
                    header('Content-Type: application/csv; charset=windows-1251', true, 200);
                    header('Content-Disposition: attachment; filename="seo_crosslinks-' . date('ymd') . '.csv"');
                    $fields = ['host', 'path', 'query', 'text', 'href'];
                    $f = fopen('php://output', 'wt');
                    if (! $f) {
                        throw new Exception('error opening output');
                    }
                    fputcsv($f, $fields, ';');
                    $res =
                        $this->db->query(sprintf('select * from `%sseo_crosslinks` order by `host`,`path`,`query`,`text`',
                            DB_PREFIX));
                    foreach ($res->rows as $row) {
                        $data = [];
                        foreach ($fields as $field) {
                            $data[] = isset($row[$field]) ? iconv('utf8', 'cp1251//TRANSLIT', trim($row[$field])) : '';
                        }
                        fputcsv($f, $data, ';');
                    }
                    fclose($f);
                    exit;
                } else {
                    $this->response->setOutput($this->load->view('tool/seo_crosslinks.tpl'));
                }
                break;

            case 'POST':
                $_SESSION['errors'] = [];
                $_SESSION['messages'] = [];
                try {
                    if (empty($_FILES['file']['size']) || ! empty($_FILES['file']['error'])) {
                        throw new Exception('invalid file');
                    }
                    $f = fopen($_FILES['file']['tmp_name'], 'rt');
                    if (! $f) {
                        throw new Exception('eror opening file');
                    }
                    $this->db->query(sprintf('TRUNCATE TABLE `%sseo_crosslinks`', DB_PREFIX));
                    $line_no = 0;
                    while (($line = fgetcsv($f, 0, ';')) !== false) {
                        try {
                            $line_no ++;
                            if (empty($line)) {
                                continue;
                            }

                            foreach ($line as $k => $v) {
                                $line[$k] = iconv('cp1251', 'utf8//TRANSLIT', $v);
                            }
                            $fields = [];

                            $host = trim(array_shift($line));
                            if ($host == 'host') {
                                continue;
                            }
                            if (! empty($host)) {
                                $fields[] = sprintf('`host`="%s"', $this->db->escape($host));
                            }

                            $path = array_shift($line);
                            $path = preg_replace('~(^[\s\/]+)|([\s\/]+$)~uism', '', $path);
                            if (! empty($path)) {
                                $fields[] = sprintf('`path`="%s"', $this->db->escape($path));
                            }

                            $query = trim(array_shift($line));
                            if (! empty($query)) {
                                parse_str($query, $query);
                                if (empty($query)) {
                                    $query = null;
                                } else {
                                    ksort($query);
                                    $query = http_build_query($query);
                                }
                            }
                            if (! empty($query)) {
                                $fields[] = sprintf('`query`="%s"', $this->db->escape($query));
                            }

                            $text = trim(array_shift($line));
                            if (empty($text)) {
                                throw new Exception('empty text');
                            }
                            $fields[] = sprintf('`text`="%s"', $this->db->escape($text));

                            $href = trim(array_shift($line));
                            if (empty($href)) {
                                throw new Exception('empty hef');
                            }
                            $fields[] = sprintf('`href`="%s"', $this->db->escape($href));
                            $this->db->query(sprintf('insert into `%sseo_crosslinks` set %s', DB_PREFIX,
                                implode(',', $fields)));
                        } catch (Exception $ex) {
                            $_SESSION['errors'][] = sprintf("ОШИБКА %s в строке %d", $ex->getMessage(), $line_no);
                        }
                    }

                    fclose($f);

                    $_SESSION['messages'][] = 'Импорт выполнен';
                } catch (Exception $ex) {
                    $_SESSION['errors'][] = $ex->getMessage();
                }

                header('Location: ' .
                       $this->url->link('tool/seo/crosslinks', ['token' => $this->session->data['token']], 'SSL'), true,
                    303);
                exit;

                break;
        }
    }
}
