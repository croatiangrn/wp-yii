<?php

namespace rnd\widgets;

use Rnd;
use rnd\base\InvalidConfigException;
use rnd\helpers\Html;


/**
 * Yii2 Google reCAPTCHA widget.
 *
 * For example:
 *
 * ```php
 * <?= $form->field($model, 'reCaptcha')->widget(
 *  ReCaptcha::className(),
 *  ['siteKey' => 'your siteKey']
 * ) ?>
 * ```
 *
 * or
 *
 * ```php
 * <?= ReCaptcha::widget([
 *  'name' => 'reCaptcha',
 *  'siteKey' => 'your siteKey',
 *  'widgetOptions' => ['class' => 'col-sm-offset-3']
 * ]) ?>
 * ```
 *
 * @see https://developers.google.com/recaptcha
 * @author HimikLab
 * @package himiklab\yii2\recaptcha
 */
class ReCaptcha extends InputWidget
{
    const JS_API_URL = '//www.google.com/recaptcha/api.js';

    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';

    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';

    const SIZE_NORMAL = 'normal';
    const SIZE_COMPACT = 'compact';
    const SIZE_INVISIBLE = 'invisible';

    /** @var string Your sitekey. */
    public $siteKey;

    /** @var string Your secret. */
    public $secret;

    /** @var string The color theme of the widget. [[THEME_LIGHT]] (default) or [[THEME_DARK]] */
    public $theme;

    /** @var string The type of CAPTCHA to serve. [[TYPE_IMAGE]] (default) or [[TYPE_AUDIO]] */
    public $type;

    /** @var string The size of the widget. [[SIZE_NORMAL]] (default) or [[SIZE_COMPACT]] or [[SIZE_INVISIBLE]] */
    public $size;

    /** @var integer The tabindex of the widget */
    public $tabIndex;

    /** @var string Your JS callback function that's executed when the user submits a successful CAPTCHA response. */
    public $jsCallback;

    /**
     * @var string Your JS callback function that's executed when the recaptcha response expires and the user
     * needs to solve a new CAPTCHA.
     */
    public $jsExpiredCallback;

    /** @var string Your JS callback function that's executed when reCAPTCHA encounters an error (usually network
     * connectivity) and cannot continue until connectivity is restored. If you specify a function here, you are
     * responsible for informing the user that they should retry.
     */
    public $jsErrorCallback;

    /** @var array Additional html widget options, such as `class`. */
    public $widgetOptions = [];

    /**
     * @return string|void
     * @throws InvalidConfigException
     */
    public function run()
    {
        if (empty($this->siteKey)) {
            /** @var ReCaptcha $reCaptcha */
            $reCaptcha = Rnd::$app->reCaptcha;
            if ($reCaptcha && !empty($reCaptcha->siteKey)) {
                $this->siteKey = $reCaptcha->siteKey;
            } else {
                throw new InvalidConfigException('Required `siteKey` param isn\'t set.');
            }
        }

        $arguments = http_build_query([
            'hl' => $this->getLanguageSuffix(),
            'render' => 'explicit',
            'onload' => 'recaptchaOnloadCallback',
        ]);
        wp_enqueue_script('recaptcha-api', self::JS_API_URL . '?' . $arguments, [], null, true);
        wp_enqueue_script('recaptcha-onload', Rnd::getAlias('@themeurl/js/recaptcha-js.js'), [], null, true);

        /*if (Rnd::$app->getRequest()->getIsAjax()) {
            $view->registerJs(<<<JS
if (typeof grecaptcha !== "undefined") {
    recaptchaOnloadCallback();
}
JS
                , $view::POS_END
            );
        }*/

        $this->customFieldPrepare();
        echo Html::tag('div', '', $this->buildDivOptions());
    }

    protected function getReCaptchaId()
    {
        if (isset($this->widgetOptions['id'])) {
            return $this->widgetOptions['id'];
        }

        if ($this->hasModel()) {
            return Html::getInputId($this->model, $this->attribute);
        }

        return $this->id . '-' . $this->name;
    }

    protected function getLanguageSuffix()
    {
        $currentAppLanguage = Rnd::$app->language;
        $langsExceptions = ['zh-CN', 'zh-TW', 'zh-TW'];

        if (strpos($currentAppLanguage, '-') === false) {
            return $currentAppLanguage;
        }

        if (in_array($currentAppLanguage, $langsExceptions)) {
            return $currentAppLanguage;
        }

        return substr($currentAppLanguage, 0, strpos($currentAppLanguage, '-'));
    }

    protected function customFieldPrepare()
    {
        $inputId = $this->getReCaptchaId();

        if ($this->hasModel()) {
            $inputName = Html::getInputName($this->model, $this->attribute);
        } else {
            $inputName = $this->name;
        }

        echo Html::input('hidden', $inputName, null, ['id' => $inputId]);
    }

    protected function buildDivOptions()
    {
        $divOptions = [
            'class' => 'g-recaptcha',
            'data-sitekey' => $this->siteKey
        ];
        $divOptions += $this->widgetOptions;

        if (!empty($this->jsCallback)) {
            $divOptions['data-callback'] = $this->jsCallback;
        }
        if (!empty($this->jsExpiredCallback)) {
            $divOptions['data-expired-callback'] = $this->jsExpiredCallback;
        }
        if (!empty($this->jsErrorCallback)) {
            $divOptions['data-error-callback'] = $this->jsErrorCallback;
        }
        if (!empty($this->theme)) {
            $divOptions['data-theme'] = $this->theme;
        }
        if (!empty($this->type)) {
            $divOptions['data-type'] = $this->type;
        }
        if (!empty($this->size)) {
            $divOptions['data-size'] = $this->size;
        }
        if (!empty($this->tabIndex)) {
            $divOptions['data-tabindex'] = $this->tabIndex;
        }

        if (isset($this->widgetOptions['class'])) {
            $divOptions['class'] = "{$divOptions['class']} {$this->widgetOptions['class']}";
        }
        $divOptions['input-id'] = $this->getReCaptchaId();

        if ($this->field !== null && $this->field->form !== null) {
            if (!empty($this->field->form->options['id'])) {
                $divOptions['form-id'] = $this->field->form->options['id'];
            } else {
                $divOptions['form-id'] = $this->field->form->id;
            }
        } else {
            $divOptions['form-id'] = '';
        }

        $divOptions['id'] = $this->getReCaptchaId() . '-recaptcha' .
            ($divOptions['form-id'] ? ('-' . $divOptions['form-id']) : '');

        return $divOptions;
    }
}