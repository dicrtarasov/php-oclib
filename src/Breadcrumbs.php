<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 21.01.21 04:07:44
 */

declare(strict_types = 1);

namespace dicr\oclib;

use yii\base\InvalidConfigException;

use function array_filter;
use function array_map;
use function array_shift;
use function count;
use function is_array;
use function is_scalar;
use function ob_get_clean;
use function ob_start;

/**
 * Хлебные крошки.
 */
class Breadcrumbs extends Widget
{
    /** @var bool кодировать метки */
    public $encodeLabels = true;

    /** @var array|string|null домашняя ссылка */
    public $homeLink = [
        'label' => 'Главная',
        'url' => '/'
    ];

    /** @var array ссылки [label|text, url|href, encode|null] */
    public $links = [];

    /** @var bool добавить микроразметку */
    public $schema = true;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->homeLink !== null) {
            $this->homeLink = $this->parseLink($this->homeLink);
        }

        // обрабатываем ссылки
        $this->links = array_map(fn($link): array => $this->parseLink($link), $this->links ?: []);

        // удаляем ссылку на главную страницу
        if ($this->homeLink !== null) {
            $this->links = array_filter(
                $this->links,
                static fn(array $link): bool => $link['label'] !== 'Главная' && $link['url'] !== '/' &&
                    $link['url'] !== Registry::$app->url->link('common/home')
            );
        }

        Html::addCssClass($this->options, 'widget-breadcrumbs');
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function run(): string
    {
        if (empty($this->links)) {
            return '';
        }

        ob_start();
        echo Html::beginTag('nav', $this->options);

        if (! empty($this->homeLink)) {
            echo $this->renderLink($this->homeLink, empty($this->links));
        }

        echo $this->renderLinks();
        echo $this->schema();
        echo Html::endTag('nav');

        return ob_get_clean();
    }

    /**
     * Парсит ссылку.
     *
     * @param $link
     * @return array
     * @throws InvalidConfigException
     */
    protected function parseLink($link): array
    {
        if (empty($link)) {
            throw new InvalidConfigException('link');
        }

        if (is_scalar($link)) {
            $link = [
                'label' => $link,
            ];
        }

        if (! is_array($link)) {
            throw new InvalidConfigException('Должна быть строка или массив');
        }

        $link = [
            'label' => $link['label'] ?? $link['text'] ?? null,
            'url' => $link['url'] ?? $link['href'] ?? null,
            'encode' => $link['encode'] ?? null
        ];

        if ((string)$link['label'] === '') {
            throw new InvalidConfigException('Пустой текст ссылки');
        }

        return $link;
    }

    /**
     * Рендерит ссылки.
     *
     * @return string
     */
    protected function renderLinks(): string
    {
        ob_start();

        $links = $this->links;

        while (true) {
            $link = array_shift($links);
            if (empty($link)) {
                break;
            }

            echo $this->renderLink($link, empty($links));
        }

        return ob_get_clean();
    }

    /**
     * Рендерит ссылку.
     *
     * @param array $link
     * @param bool $last последняя
     * @return string
     */
    protected function renderLink(array $link, bool $last = false): string
    {
        $label = $link['label'];
        if ($link['encode'] ?? $this->encodeLabels) {
            $label = Html::esc($label);
        }

        return ! $last && ! empty($link['url']) ?
            Html::a($label, $link['url'], ['class' => 'item link']) :
            Html::tag('span', $label, ['class' => 'item last tail']);
    }

    /**
     * Генерирует микроразметку ldJson
     *
     * @return string
     */
    protected function schema(): string
    {
        if (! $this->schema || (empty($this->homeLink) && empty($this->links))) {
            return '';
        }

        $json = [
            '@context' => 'http://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        if (! empty($this->homeLink)) {
            $json['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 1,
                'item' => [
                    '@id' => Url::to($this->homeLink['url'], true),
                    'name' => Html::toText($this->homeLink['label']) ?: 'Главная'
                ]
            ];
        }

        foreach ($this->links as $link) {
            $json['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => count($json['itemListElement']) + 1,
                'item' => [
                    '@id' => Url::to($link['url'] ?? '', true),
                    'name' => Html::toText($link['label'])
                ]
            ];
        }

        return Html::schema($json);
    }
}
