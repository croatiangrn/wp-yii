<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\i18n;

use NumberFormatter;
use Rnd;
use rnd\base\Component;
use rnd\base\InvalidConfigException;

/**
 * Locale provides various locale information via convenient methods.
 *
 * The class requires [PHP intl extension](http://php.net/manual/en/book.intl.php) to be installed.
 * @since 2.0.14
 *
 * @property string $currencySymbol This property is read-only.
 *
 */
class Locale extends Component
{
    /**
     * @var string the locale ID.
     * If not set, [[\yii\base\Application::language]] will be used.
     */
    public $locale;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if (!extension_loaded('intl')) {
            throw new InvalidConfigException('Locale component requires PHP intl extension to be installed.');
        }

        if ($this->locale === null) {
            $this->locale = Rnd::$app->language;
        }
    }

    /**
     * Returns a currency symbol
     *
     * @param string $currencyCode the 3-letter ISO 4217 currency code to get symbol for. If null,
     * method will attempt using currency code from [[locale]].
     * @return string
     */
    public function getCurrencySymbol($currencyCode = null)
    {
        $locale = $this->locale;

        if ($currencyCode !== null) {
            $locale .= '@currency=' . $currencyCode;
        }

        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }
}