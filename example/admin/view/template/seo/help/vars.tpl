<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types = 1);

use app\models\UrlAlias;

/**
 * Справка по переменным.
 *
 * @var \Template $this
 */

$breadcrumbs = [
    ['text' => 'Главная', 'href' => $this->url->link('common/dashboard', ['token' => $this->session->data['token']])],
    ['text' => 'SEO', 'href' => 'javascript:'],
    ['text' => 'Переменные', 'href' => $this->url->link('tool/seo/help/vars', ['token' => $this->session->data['token']])],
];
?>
<?=$this->load->controller('common/header')?>
<?=$this->load->controller('common/column_left')?>

<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <h1>Справка по переменным в текстах</h1>

            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                    <li>
                        <a href="<?=Html::esc($breadcrumb['href'])?>"><?=Html::esc($breadcrumb['text'])?></a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>

    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-body">
                <table class="table">
                <tbody>

                <tr><th colspan="2"><h2>Город</h2></th></tr>
                <tr><th>${city.name1}</th><td>Город</td></tr>
                <tr><th>${city.name2}</th><td>по Городу</td></tr>
                <tr><th>${city.name3}</th><td>в Городе</td></tr>
                <tr><th>${city.name4}</th><td>в Город</td></tr>
                <tr><th>${city.firstPhone}</th><td>первый телефон</td></tr>
                <tr><th>${city.name98}, ${city.name99}</th><td>/не знаю/</td></tr>

                <tr><th colspan="2"><h2>Категория</h2></th></tr>
                <tr><th>${categ.name}</th><td>название</td></tr>
                <tr><th>${categ.parentName}</th><td>название родительской</td></tr>
                <tr><th>${categ.pathName}</th><td>путь через "/"</td></tr>
                <tr><th>${categ.singular}</th><td>единиственное число названия товаров категории</td></tr>
                <tr><th>${categ.units}</th><td>единицы измерения товаров</td></tr>

                <tr><th colspan="2"><h2>Фильтр товаров</h2></th></tr>
                <tr><th>${prodFilter.filterText}</th><td>парамеры фильтра</td></tr>

                </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('#alias-text1, #alias-text2').summernote({height: 150, lang:'ru-RU'});
    });
</script>

<?=$this->load->controller('common/footer')?>
