<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

/**
 * Справка
 */
class ControllerSeoHelp extends Controller
{
    /**
     * Переменные в тексте.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function vars()
    {
        $this->response->setOutput($this->load->view('seo/help/vars'));
    }
}
