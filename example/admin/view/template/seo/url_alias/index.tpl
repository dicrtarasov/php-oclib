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
 * ЧПУ алиасы.
 *
 * @var \Template $this
 * @var \app\models\UrlAliasFilter $filter
 */

$provider = $filter->provider;
$provider->pagination->defaultPageSize = 50;

$breadcrumbs = [
    ['text' => 'Главная', 'href' => $this->url->link('common/dashboard', ['token' => $this->session->data['token']])],
    ['text' => 'SEO', 'href' => 'javascript:'],
    ['text' => 'ЧПУ алиасы', 'href' => $this->url->link('tool/seo/alias', ['token' => $this->session->data['token']])]
];
?>
<?=$this->load->controller('common/header')?>
<?=$this->load->controller('common/column_left')?>

<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-aliases" data-toggle="tooltip" title="Сохранить"
                        class="btn btn-primary">
                    <i class="fa fa-save"></i> Сохранить
                </button>
            </div>

            <h1>ЧПУ алиасы</h1>

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
        <?php if (! empty($error_warning)) { ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> <?=$error_warning?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php } ?>

        <?php if (! empty($success)) { ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?=$success?>
                <button type="button" form="form-backup" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php } ?>

        <div class="panel panel-default">
            <div class="panel-body">
                <!-- Фильтр -->
                <?=Html::beginForm($this->url->link('seo/url_alias', ['token' => $this->session->data['token']]), 'GET',
                    [
                        'class' => 'form-filter',
                        'style' => 'margin-bottom: 1.5rem'
                    ])?>
                <?=Html::hiddenInput('sort', Yii::$app->request->get('sort'))?>
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label class="control-label">Алиас:</label>
                            <?=Html::activeTextInput($filter, 'keyword', ['class' => 'form-control'])?>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label class="control-label">Маршрут/параметры:</label>
                            <?=Html::activeTextInput($filter, 'query', ['class' => 'form-control'])?>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label class="control-label">Тип</label>
                            <?=Html::activeDropDownList($filter, 'type', UrlAlias::TYPES, [
                                'prompt' => '',
                                'class' => 'form-control'
                            ])?>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label class="control-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-default">Фильтровать</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?=Html::endForm()?>

                <!-- Алиасы -->
                <?=Html::beginForm($this->url->link('seo/url_alias', [
                    'token' => $this->session->data['token'],
                    'sort' => Yii::$app->request->get('sort'),
                    'page' => Yii::$app->request->get('page'),
                    $filter->formName() => $filter->attributes
                ]), 'POST', [
                    'id' => 'form-aliases',
                    'class' => 'form-aliases',
                    'style' => 'margin-bottom: 1rem'
                ])?>
                <table class="aliases table table-condensed table-hover">
                    <thead>
                    <tr>
                        <th style="width: 40%;"><?=$provider->sort->link('keyword')?></th>
                        <th style="width: 50%;"><?=$provider->sort->link('query')?></th>
                        <th class="text-center" style="width: 9%;"><?=$provider->sort->link('type')?></th>
                        <th style="width: 1%;"></th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($provider->models as $alias) { ?>
                        <tr class="alias" data-id="<?=(int)$alias->url_alias_id?>">
                            <td>
                                <?=Html::activeHiddenInput($alias, '[' . $alias->url_alias_id . ']url_alias_id')?>
                                <?=Html::activeTextInput($alias, '[' . $alias->url_alias_id . ']keyword',
                                    ['class' => 'form-control'])?>
                            </td>
                            <td><?=Html::activeTextInput($alias, '[' . $alias->url_alias_id . ']query',
                                    ['class' => 'form-control'])?></td>
                            <td class="text-center"><?=Html::esc(UrlAlias::TYPES[$alias->type])?></td>
                            <td><?=Html::button('<i class="fa fa-times text-danger"></i>',
                                    ['class' => 'del btn btn-link btn-xs', 'title' => 'Удалить'])?></td>
                        </tr>
                    <?php } ?>
                    </tbody>

                    <tfoot>
                    <tr>
                        <td style="padding-top: 1rem;" colspan="4" class="text-center">
                            <button type="button" class="btn btn-success add pull-left">Добавить</button>
                            <!-- Pager -->
                            <nav class="text-center" style="margin-bottom: 2rem;"><?=new Pagination($provider)?></nav>
                        </td>
                    </tr>
                    </tfoot>
                </table>
                <?=Html::endForm()?>
            </div>
        </div>
    </div>
</div>
<style type="text/css">
    .aliases tbody > tr > td {
        padding: 0 5px;
    }

    .aliases tbody tr td:first-of-type {
        border-right: 1px solid #ccc;
    }

    .aliases tbody tr td:last-of-type {
        width: 1%;
    }

    .aliases tbody input {
        border-width: 0 !important;
        outline: none !important;
        background-color: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
        line-height: 2 !important;
        height: auto !important;
    }
</style>

<script>
    $(function () {
        const $formAliases = $('.form-aliases');
        const $aliases = $('.aliases', $formAliases);

        $formAliases.on('click', '.add', function () {
            const index = Date.now();

            $('tbody', $aliases).append(
                $('<tr class="alias"></tr>').append(
                    $('<td></td>').append(
                        $('<input/>', {'class': 'form-control', type: 'text', name: 'UrlAlias[' + index + '][keyword]'})
                    ),
                    $('<td></td>').append(
                        $('<input/>', {'class': 'form-control', type: 'text', name: 'UrlAlias[' + index + '][query]'})
                    ),
                    $('<td>&nbsp;</td>'),
                    $('<td><button type="button" class="del btn btn-link btn-xs" title="Удалить"><i class="fa fa-times text-danger"></i></button></td>')
                )
            );
        });

        $formAliases.on('click', '.del', function () {
            const $alias = $(this).closest('.alias');
            const id = parseInt($alias.data('id'));

            $alias.remove();

            if (id > 0) {
                $.ajax({
                    url: '<?=$this->url->link('seo/url_alias/delete', ['token' => $this->session->data['token']])?>',
                    method: 'POST',
                    data: {url_alias_id: id}
                }).done(function (ret) {
                    if (ret.error) {
                        console.error(ret.error);
                    }
                });
            }
        });
    });
</script>

<?=$this->load->controller('common/footer')?>
