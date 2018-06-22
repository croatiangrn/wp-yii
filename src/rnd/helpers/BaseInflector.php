<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\helpers;


use Rnd;

class BaseInflector
{
	/**
	 * @var array fallback map for transliteration used by [[transliterate()]] when intl isn't available.
	 */
	public static $transliteration = [
		'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
		'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
		'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
		'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
		'ß' => 'ss',
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
		'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
		'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
		'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
		'ÿ' => 'y',
	];

	/**
	 * Shortcut for `Any-Latin; NFKD` transliteration rule. The rule is strict, letters will be transliterated with
	 * the closest sound-representation chars. The result may contain any UTF-8 chars. For example:
	 * `获取到 どちら Українська: ґ,є, Српска: ђ, њ, џ! ¿Español?` will be transliterated to
	 * `huò qǔ dào dochira Ukraí̈nsʹka: g̀,ê, Srpska: đ, n̂, d̂! ¿Español?`
	 *
	 * Used in [[transliterate()]].
	 * For detailed information see [unicode normalization forms](http://unicode.org/reports/tr15/#Normalization_Forms_Table)
	 * @see http://unicode.org/reports/tr15/#Normalization_Forms_Table
	 * @see transliterate()
	 * @since 2.0.7
	 */
	const TRANSLITERATE_STRICT = 'Any-Latin; NFKD';
	/**
	 * Shortcut for `Any-Latin; Latin-ASCII` transliteration rule. The rule is medium, letters will be
	 * transliterated to characters of Latin-1 (ISO 8859-1) ASCII table. For example:
	 * `获取到 どちら Українська: ґ,є, Српска: ђ, њ, џ! ¿Español?` will be transliterated to
	 * `huo qu dao dochira Ukrainsʹka: g,e, Srpska: d, n, d! ¿Espanol?`
	 *
	 * Used in [[transliterate()]].
	 * For detailed information see [unicode normalization forms](http://unicode.org/reports/tr15/#Normalization_Forms_Table)
	 * @see http://unicode.org/reports/tr15/#Normalization_Forms_Table
	 * @see transliterate()
	 * @since 2.0.7
	 */
	const TRANSLITERATE_MEDIUM = 'Any-Latin; Latin-ASCII';
	/**
	 * Shortcut for `Any-Latin; Latin-ASCII; [\u0080-\uffff] remove` transliteration rule. The rule is loose,
	 * letters will be transliterated with the characters of Basic Latin Unicode Block.
	 * For example:
	 * `获取到 どちら Українська: ґ,є, Српска: ђ, њ, џ! ¿Español?` will be transliterated to
	 * `huo qu dao dochira Ukrainska: g,e, Srpska: d, n, d! Espanol?`
	 *
	 * Used in [[transliterate()]].
	 * For detailed information see [unicode normalization forms](http://unicode.org/reports/tr15/#Normalization_Forms_Table)
	 * @see http://unicode.org/reports/tr15/#Normalization_Forms_Table
	 * @see transliterate()
	 * @since 2.0.7
	 */
	const TRANSLITERATE_LOOSE = 'Any-Latin; Latin-ASCII; [\u0080-\uffff] remove';

	/**
	 * @var mixed Either a [[\Transliterator]], or a string from which a [[\Transliterator]] can be built
	 * for transliteration. Used by [[transliterate()]] when intl is available. Defaults to [[TRANSLITERATE_LOOSE]]
	 * @see http://php.net/manual/en/transliterator.transliterate.php
	 */
	public static $transliterator = self::TRANSLITERATE_LOOSE;

	/**
	 * Returns a string with all spaces converted to given replacement,
	 * non word characters removed and the rest of characters transliterated.
	 *
	 * If intl extension isn't available uses fallback that converts latin characters only
	 * and removes the rest. You may customize characters map via $transliteration property
	 * of the helper.
	 *
	 * @param string $string An arbitrary string to convert
	 * @param string $replacement The replacement to use for spaces
	 * @param bool $lowercase whether to return the string in lowercase or not. Defaults to `true`.
	 * @return string The converted string.
	 */
	public static function slug($string, $replacement = '-', $lowercase = true)
	{
		$string = static::transliterate($string);
		$string = preg_replace('/[^a-zA-Z0-9=\s—–-]+/u', '', $string);
		$string = preg_replace('/[=\s—–-]+/u', $replacement, $string);
		$string = trim($string, $replacement);
		return $lowercase ? strtolower($string) : $string;
	}

	/**
	 * Returns transliterated version of a string.
	 *
	 * If intl extension isn't available uses fallback that converts latin characters only
	 * and removes the rest. You may customize characters map via $transliteration property
	 * of the helper.
	 *
	 * @param string $string input string
	 * @param string|\Transliterator $transliterator either a [[\Transliterator]] or a string
	 * from which a [[\Transliterator]] can be built.
	 * @return string
	 * @since 2.0.7 this method is public.
	 */
	public static function transliterate($string, $transliterator = null)
	{
		if (static::hasIntl()) {
			if ($transliterator === null) {
				$transliterator = static::$transliterator;
			}
			return transliterator_transliterate($transliterator, $string);
		}
		return strtr($string, static::$transliteration);
	}

	/**
	 * @return bool if intl extension is loaded
	 */
	protected static function hasIntl()
	{
		return extension_loaded('intl');
	}

	/**
	 * Returns given word as CamelCased
	 * Converts a word like "send_email" to "SendEmail". It
	 * will remove non alphanumeric character from the word, so
	 * "who's online" will be converted to "WhoSOnline"
	 * @see variablize()
	 *
	 * @param string $word the word to CamelCase
	 *
	 * @return string
	 */
	public static function camelize( $word )
	{
		return str_replace( ' ', '', ucwords( preg_replace( '/[^A-Za-z0-9]+/', ' ', $word ) ) );

	}

	/**
	 * Converts a CamelCase name into space-separated words.
	 * For example, 'PostTag' will be converted to 'Post Tag'.
	 * @param string $name the string to be converted
	 * @param bool $ucwords whether to capitalize the first letter in each word
	 * @return string the resulting words
	 */
	public static function camel2words($name, $ucwords = true)
	{
		$label = strtolower(trim(str_replace([
			'-',
			'_',
			'.',
		], ' ', preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name))));
		return $ucwords ? ucwords($label) : $label;
	}

	/**
	 * Converts a list of words into a sentence.
	 *
	 * Special treatment is done for the last few words. For example,
	 *
	 * ```php
	 * $words = ['Spain', 'France'];
	 * echo Inflector::sentence($words);
	 * // output: Spain and France
	 *
	 * $words = ['Spain', 'France', 'Italy'];
	 * echo Inflector::sentence($words);
	 * // output: Spain, France and Italy
	 *
	 * $words = ['Spain', 'France', 'Italy'];
	 * echo Inflector::sentence($words, ' & ');
	 * // output: Spain, France & Italy
	 * ```
	 *
	 * @param array $words the words to be converted into an string
	 * @param string $twoWordsConnector the string connecting words when there are only two
	 * @param string $lastWordConnector the string connecting the last two words. If this is null, it will
	 * take the value of `$twoWordsConnector`.
	 * @param string $connector the string connecting words other than those connected by
	 * $lastWordConnector and $twoWordsConnector
	 * @return string the generated sentence
	 * @since 2.0.1
	 */
	public static function sentence(array $words, $twoWordsConnector = null, $lastWordConnector = null, $connector = ', ')
	{
		if ($twoWordsConnector === null) {
			$twoWordsConnector = ' and ';
		}
		if ($lastWordConnector === null) {
			$lastWordConnector = $twoWordsConnector;
		}
		
		switch (count($words)) {
			case 0:
				return '';
			case 1:
				return reset($words);
			case 2:
				return implode($twoWordsConnector, $words);
			default:
				return implode($connector, array_slice($words, 0, -1)) . $lastWordConnector . end($words);
		}
	}

    /**
     * Converts any "CamelCased" into an "underscored_word".
     * @param string $words the word(s) to underscore
     * @return string
     */
    public static function underscore($words)
    {
        return mb_strtolower(preg_replace('/(?<=\\pL)(\\p{Lu})/u', '_\\1', $words), self::encoding());
    }

    /**
     * @return string
     */
    private static function encoding()
    {
        return isset(Rnd::$app) ? Rnd::$app->charset : 'UTF-8';
    }
}