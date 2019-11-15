<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types = 1);

/**
 * Редактирование ЧПУ алиаса.
 *
 * @var \Template $this
 * @var \app\models\UrlAlias $urlAlias
 */

$breadcrumbs = [
    ['text' => 'Главная', 'href' => $this->url->link('common/dashboard', ['token' => $this->session->data['token']])],
    ['text' => 'SEO', 'href' => 'javascript:'],
    [
        'text' => 'ЧПУ алиасы',
        'href' => $this->url->link('tool/seo/url_alias', ['token' => $this->session->data['token']])
    ],
    [
        'text' => $urlAlias->isNewRecord ? 'Новый алиас' : $urlAlias->keyword,
        'href' => $urlAlias->isNewRecord ? '' : $this->url->link('tool/seo/url_alias/edit', [
            'url_alias_id' => $urlAlias->url_alias_id
        ])
    ]
];
?>
<?=$this->load->controller('common/header')?>
<?=$this->load->controller('common/column_left')?>

<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-alias-edit" data-toggle="tooltip" title="Сохранить"
                        class="btn btn-primary">
                    <i class="fa fa-save"></i> Сохранить
                </button>
                <a class="btn btn-default" href="<?=$this->url->link('seo/url_alias')?>"><i class="fa fa-undo"></i></a>
            </div>

            <h1><?=$urlAlias->isNewRecord ? 'Создание алиаса' : 'Редакирование алиаса'?></h1>

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
                <?=Html::beginForm($this->url->link('seo/url_alias/edit', [
                    'token' => $this->session->data['token'],
                    'url_alias_id' => $urlAlias->isNewRecord ? null : $urlAlias->url_alias_id
                ]), 'POST', [
                    'id' => 'form-alias-edit',
                    'class' => 'form-filter form-horizontal',
                    'style' => 'margin-bottom: 1.5rem'
                ])?>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Маршрут или параметр:</label>
                    <div class="col-sm-10">
                        <?=Html::activeTextInput($urlAlias, 'query', ['class' => 'form-control'])?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Алиас ЧПУ:</label>
                    <div class="col-sm-10">
                        <?=Html::activeTextInput($urlAlias, 'keyword', ['class' => 'form-control'])?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Meta Title:</label>
                    <div class="col-sm-10">
                        <?=Html::activeTextInput($urlAlias, 'meta_title', ['class' => 'form-control'])?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Meta Description:</label>
                    <div class="col-sm-10">
                        <?=Html::activeTextInput($urlAlias, 'meta_desc', ['class' => 'form-control'])?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Meta H1:</label>
                    <div class="col-sm-10">
                        <?=Html::activeTextInput($urlAlias, 'meta_h1', ['class' => 'form-control'])?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Текст 1:</label>
                    <div class="col-sm-10">
                        <?=Html::activeTextarea($urlAlias, 'text1', ['id' => 'alias-text1'])?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Текст 2:</label>
                    <div class="col-sm-10">
                        <?=Html::activeTextarea($urlAlias, 'text2', ['id' => 'alias-text2'])?>
                    </div>
                </div>
                <?=Html::endForm()?>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('#alias-text1, #alias-text2').summernote({height: 150, lang: 'ru-RU'});
    });
</script>

<?=$this->load->controller('common/footer')?>
