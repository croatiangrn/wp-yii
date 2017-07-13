<?php


namespace App\helpers;


class Html {
	/**
	 * @var array list of void elements (element name => 1)
	 * @see http://www.w3.org/TR/html-markup/syntax.html#void-element
	 */
	public static $voidElements = [
		'area' => 1,
		'base' => 1,
		'br' => 1,
		'col' => 1,
		'command' => 1,
		'embed' => 1,
		'hr' => 1,
		'img' => 1,
		'input' => 1,
		'keygen' => 1,
		'link' => 1,
		'meta' => 1,
		'param' => 1,
		'source' => 1,
		'track' => 1,
		'wbr' => 1,
	];
	/**
	 * @var array the preferred order of attributes in a tag. This mainly affects the order of the attributes
	 * that are rendered by [[renderTagAttributes()]].
	 */
	public static $attributeOrder = [
		'type',
		'id',
		'class',
		'name',
		'value',

		'href',
		'src',
		'action',
		'method',

		'selected',
		'checked',
		'readonly',
		'disabled',
		'multiple',

		'size',
		'maxlength',
		'width',
		'height',
		'rows',
		'cols',

		'alt',
		'title',
		'rel',
		'media',
	];

	/**
	 * @var array list of tag attributes that should be specially handled when their values are of array type.
	 * In particular, if the value of the `data` attribute is `['name' => 'xyz', 'age' => 13]`, two attributes
	 * will be generated instead of one: `data-name="xyz" data-age="13"`.
	 * @since 2.0.3
	 */
	public static $dataAttributes = ['data', 'data-ng', 'ng'];

	/**
	 * Encodes special characters into HTML entities.
	 * The [[\yii\base\Application::charset|application charset]] will be used for encoding.
	 * @param string $content the content to be encoded
	 * @param bool $doubleEncode whether to encode HTML entities in `$content`. If false,
	 * HTML entities in `$content` will not be further encoded.
	 * @return string the encoded content
	 * @see decode()
	 * @see http://www.php.net/manual/en/function.htmlspecialchars.php
	 */
	public static function encode($content, $doubleEncode = true)
	{
		return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
	}

	/**
	 * Renders the HTML tag attributes.
	 *
	 * Attributes whose values are of boolean type will be treated as
	 * [boolean attributes](http://www.w3.org/TR/html5/infrastructure.html#boolean-attributes).
	 *
	 * Attributes whose values are null will not be rendered.
	 *
	 * The values of attributes will be HTML-encoded using [[encode()]].
	 *
	 * The "data" attribute is specially handled when it is receiving an array value. In this case,
	 * the array will be "expanded" and a list data attributes will be rendered. For example,
	 * if `'data' => ['id' => 1, 'name' => 'yii']`, then this will be rendered:
	 * `data-id="1" data-name="yii"`.
	 * Additionally `'data' => ['params' => ['id' => 1, 'name' => 'yii'], 'status' => 'ok']` will be rendered as:
	 * `data-params='{"id":1,"name":"yii"}' data-status="ok"`.
	 *
	 * @param array $attributes attributes to be rendered. The attribute values will be HTML-encoded using [[encode()]].
	 * @return string the rendering result. If the attributes are not empty, they will be rendered
	 * into a string with a leading white space (so that it can be directly appended to the tag name
	 * in a tag. If there is no attribute, an empty string will be returned.
	 */
	public static function renderTagAttributes($attributes)
	{
		if (count($attributes) > 1) {
			$sorted = [];
			foreach (static::$attributeOrder as $name) {
				if (isset($attributes[$name])) {
					$sorted[$name] = $attributes[$name];
				}
			}
			$attributes = array_merge($sorted, $attributes);
		}

		$html = '';
		foreach ($attributes as $name => $value) {
			if (is_bool($value)) {
				if ($value) {
					$html .= " $name";
				}
			} elseif (is_array($value)) {
				if ($name === 'class') {
					if (empty($value)) {
						continue;
					}
					$html .= " $name=\"" . static::encode(implode(' ', $value)) . '"';
				} elseif ($name === 'style') {
					if (empty($value)) {
						continue;
					}
					$html .= " $name=\"" . static::encode(static::cssStyleFromArray($value)) . '"';
				} else {
					$html .= " $name='" . static::encode($value) . "'";
				}
			} elseif ($value !== null) {
				$html .= " $name=\"" . static::encode($value) . '"';
			}
		}

		return $html;
	}




	/**
	 * Generates a complete HTML tag.
	 * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
	 * @param string $content the content to be enclosed between the start and end tags. It will not be HTML-encoded.
	 * If this is coming from end users, you should consider [[encode()]] it to prevent XSS attacks.
	 * @param array $options the HTML tag attributes (HTML options) in terms of name-value pairs.
	 * These will be rendered as the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 *
	 * For example when using `['class' => 'my-class', 'target' => '_blank', 'value' => null]` it will result in the
	 * html attributes rendered like this: `class="my-class" target="_blank"`.
	 *
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated HTML tag
	 * @see beginTag()
	 * @see endTag()
	 */
	public static function tag($name, $content = '', $options = [])
	{
		if ($name === null || $name === false) {
			return $content;
		}
		$html = "<$name" . static::renderTagAttributes($options) . '>';
		return isset(static::$voidElements[strtolower($name)]) ? $html : "$html$content</$name>";
	}

	/**
	 * Converts a CSS style array into a string representation.
	 *
	 * For example,
	 *
	 * ```php
	 * print_r(Html::cssStyleFromArray(['width' => '100px', 'height' => '200px']));
	 * // will display: 'width: 100px; height: 200px;'
	 * ```
	 *
	 * @param array $style the CSS style array. The array keys are the CSS property names,
	 * and the array values are the corresponding CSS property values.
	 * @return string the CSS style string. If the CSS style is empty, a null will be returned.
	 */
	public static function cssStyleFromArray(array $style)
	{
		$result = '';
		foreach ($style as $name => $value) {
			$result .= "$name: $value; ";
		}
		// return null if empty to avoid rendering the "style" attribute
		return $result === '' ? null : rtrim($result);
	}

	/**
	 * Generates a hyperlink tag.
	 * @param string $text link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code
	 * such as an image tag. If this is coming from end users, you should consider [[encode()]]
	 * it to prevent XSS attacks.
	 * @param string|null $url the URL for the hyperlink tag. This parameter will be processed by [[Url::to()]]
	 * and will be used for the "href" attribute of the tag. If this parameter is null, the "href" attribute
	 * will not be generated.
	 *
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated hyperlink
	 */
	public static function a($text, $url = null, $options = [])
	{
		if ($url !== null) {
			$options['href'] = $url;
		}

		if (strlen($text) > 0) {
			return static::tag('a', $text, $options);
		}

		return null;
	}

	/**
	 * Generates an image tag.
	 * @param array|string $src the image URL. This parameter will be processed by [[Url::to()]].
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated image tag
	 */
	public static function img($src, $options = [])
	{
		$options['src'] = $src;
		if (!isset($options['alt'])) {
			$options['alt'] = '';
		}
		return static::tag('img', '', $options);
	}

	/**
	 * Generates a mailto hyperlink.
	 * @param string $text link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code
	 * such as an image tag. If this is coming from end users, you should consider [[encode()]]
	 * it to prevent XSS attacks.
	 * @param string $email email address. If this is null, the first parameter (link body) will be treated
	 * as the email address and used.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated mailto link
	 */
	public static function mailto($text, $email = null, $options = [])
	{
		$options['href'] = 'mailto:' . ($email === null ? $text : $email);
		return static::tag('a', $text, $options);
	}

	/**
	 * Generates an input type of the given type.
	 * @param string $type the type attribute.
	 * @param string $name the name attribute. If it is null, the name attribute will not be generated.
	 * @param string $value the value attribute. If it is null, the value attribute will not be generated.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated input tag
	 */
	public static function input($type, $name = null, $value = null, $options = [])
	{
		if (!isset($options['type'])) {
			$options['type'] = $type;
		}
		$options['name'] = $name;
		$options['value'] = $value === null ? null : (string) $value;
		return static::tag('input', '', $options);
	}

	/**
	 * Generates a button tag.
	 * @param string $content the content enclosed within the button tag. It will NOT be HTML-encoded.
	 * Therefore you can pass in HTML code such as an image tag. If this is is coming from end users,
	 * you should consider [[encode()]] it to prevent XSS attacks.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated button tag
	 */
	public static function button($content = 'Button', $options = [])
	{
		if (!isset($options['type'])) {
			$options['type'] = 'button';
		}
		return static::tag('button', $content, $options);
	}

	/**
	 * Generates a submit button tag.
	 *
	 * Be careful when naming form elements such as submit buttons. According to the [jQuery documentation](https://api.jquery.com/submit/) there
	 * are some reserved names that can cause conflicts, e.g. `submit`, `length`, or `method`.
	 *
	 * @param string $content the content enclosed within the button tag. It will NOT be HTML-encoded.
	 * Therefore you can pass in HTML code such as an image tag. If this is is coming from end users,
	 * you should consider [[encode()]] it to prevent XSS attacks.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated submit button tag
	 */
	public static function submitButton($content = 'Submit', $options = [])
	{
		$options['type'] = 'submit';
		return static::button($content, $options);
	}

	/**
	 * Generates a label tag.
	 * @param string $content label text. It will NOT be HTML-encoded. Therefore you can pass in HTML code
	 * such as an image tag. If this is is coming from end users, you should [[encode()]]
	 * it to prevent XSS attacks.
	 * @param string $for the ID of the HTML element that this label is associated with.
	 * If this is null, the "for" attribute will not be generated.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated label tag
	 */
	public static function label($content, $for = null, $options = [])
	{
		$options['for'] = $for;
		return static::tag('label', $content, $options);
	}

	/**
	 * Generates a start tag.
	 * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated start tag
	 * @see endTag()
	 * @see tag()
	 */
	public static function beginTag($name, $options = [])
	{
		if ($name === null || $name === false) {
			return '';
		}
		return "<$name" . static::renderTagAttributes($options) . '>';
	}

	/**
	 * Generates an end tag.
	 * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
	 * @return string the generated end tag
	 * @see beginTag()
	 * @see tag()
	 */
	public static function endTag($name)
	{
		if ($name === null || $name === false) {
			return '';
		}
		return "</$name>";
	}

	/**
	 * Generates a form start tag.
	 * @param string $action the form action URL. This parameter will be processed by [[Url::to()]].
	 * @param string $method the form submission method, such as "post", "get" (case-insensitive).
	 * Since most browsers only support "post" and "get", if other methods are given, they will
	 * be simulated using "post", and a hidden input will be added which contains the actual method type.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated form start tag.
	 * @see endForm()
	 */
	public static function beginForm($action = '', $method = 'post', $options = [])
	{
		$options['action'] = $action;
		$options['method'] = $method;
		$form = static::beginTag('form', $options);

		return $form;
	}

	/**
	 * Generates a form end tag.
	 * @return string the generated tag
	 * @see beginForm()
	 */
	public static function endForm()
	{
		return '</form>';
	}

	/**
	 * Generates a hidden input field.
	 * @param string $name the name attribute.
	 * @param string $value the value attribute. If it is null, the value attribute will not be generated.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated hidden input tag
	 */
	public static function hiddenInput($name, $value = null, $options = [])
	{
		return static::input('hidden', $name, $value, $options);
	}

	/**
	 * Renders the option tags that can be used by [[dropDownList()]] and [[listBox()]].
	 * @param string|array|null $selection the selected value(s). String for single or array for multiple selection(s).
	 * @param array $items the option data items. The array keys are option values, and the array values
	 * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
	 * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
	 * If you have a list of data models, you may convert them into the format described above using
	 * [[\yii\helpers\ArrayHelper::map()]].
	 *
	 * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
	 * the labels will also be HTML-encoded.
	 * @param array $tagOptions the $options parameter that is passed to the [[dropDownList()]] or [[listBox()]] call.
	 * This method will take out these elements, if any: "prompt", "options" and "groups". See more details
	 * in [[dropDownList()]] for the explanation of these elements.
	 *
	 * @return string the generated list options
	 */
	public static function renderSelectOptions($selection, $items, &$tagOptions = [])
	{
		$lines = [];
		$encodeSpaces = ArrayHelper::remove($tagOptions, 'encodeSpaces', false);
		$encode = ArrayHelper::remove($tagOptions, 'encode', true);
		if (isset($tagOptions['prompt'])) {
			$promptOptions = ['value' => ''];
			if (is_string($tagOptions['prompt'])) {
				$promptText = $tagOptions['prompt'];
			} else {
				$promptText = $tagOptions['prompt']['text'];
				$promptOptions = array_merge($promptOptions, $tagOptions['prompt']['options']);
			}
			$promptText = $encode ? static::encode($promptText) : $promptText;
			if ($encodeSpaces) {
				$promptText = str_replace(' ', '&nbsp;', $promptText);
			}
			$lines[] = static::tag('option', $promptText, $promptOptions);
		}

		$options = isset($tagOptions['options']) ? $tagOptions['options'] : [];
		$groups = isset($tagOptions['groups']) ? $tagOptions['groups'] : [];
		unset($tagOptions['prompt'], $tagOptions['options'], $tagOptions['groups']);
		$options['encodeSpaces'] = ArrayHelper::getValue($options, 'encodeSpaces', $encodeSpaces);
		$options['encode'] = ArrayHelper::getValue($options, 'encode', $encode);
		foreach ($items as $key => $value) {
			if (is_array($value)) {
				$groupAttrs = isset($groups[$key]) ? $groups[$key] : [];
				if (!isset($groupAttrs['label'])) {
					$groupAttrs['label'] = $key;
				}
				$attrs = ['options' => $options, 'groups' => $groups, 'encodeSpaces' => $encodeSpaces, 'encode' => $encode];
				$content = static::renderSelectOptions($selection, $value, $attrs);
				$lines[] = static::tag('optgroup', "\n" . $content . "\n", $groupAttrs);
			} else {
				$attrs = isset($options[$key]) ? $options[$key] : [];
				$attrs['value'] = (string) $key;
				if (!array_key_exists('selected', $attrs)) {
					$attrs['selected'] = $selection !== null &&
					                     (!ArrayHelper::isTraversable($selection) && !strcmp($key, $selection)
					                      || ArrayHelper::isTraversable($selection) && ArrayHelper::isIn($key, $selection));
				}
				$text = $encode ? static::encode($value) : $value;
				if ($encodeSpaces) {
					$text = str_replace(' ', '&nbsp;', $text);
				}
				$lines[] = static::tag('option', $text, $attrs);
			}
		}

		return implode("\n", $lines);
	}

	/**
	 * Generates a drop-down list.
	 * @param string $name the input name
	 * @param string|array|null $selection the selected value(s). String for single or array for multiple selection(s).
	 * @param array $items the option data items. The array keys are option values, and the array values
	 * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
	 * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
	 * If you have a list of data models, you may convert them into the format described above using
	 * [[\yii\helpers\ArrayHelper::map()]].
	 *
	 * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
	 * the labels will also be HTML-encoded.
	 * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
	 *
	 * - prompt: string, a prompt text to be displayed as the first option. Since version 2.0.11 you can use an array
	 *   to override the value and to set other tag attributes:
	 *
	 *   ```php
	 *   ['text' => 'Please select', 'options' => ['value' => 'none', 'class' => 'prompt', 'label' => 'Select']],
	 *   ```
	 *
	 * - options: array, the attributes for the select option tags. The array keys must be valid option values,
	 *   and the array values are the extra attributes for the corresponding option tags. For example,
	 *
	 *   ```php
	 *   [
	 *       'value1' => ['disabled' => true],
	 *       'value2' => ['label' => 'value 2'],
	 *   ];
	 *   ```
	 *
	 * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
	 *   except that the array keys represent the optgroup labels specified in $items.
	 * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
	 *   Defaults to false.
	 * - encode: bool, whether to encode option prompt and option value characters.
	 *   Defaults to `true`. This option is available since 2.0.3.
	 *
	 * The rest of the options will be rendered as the attributes of the resulting tag. The values will
	 * be HTML-encoded using [[encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated drop-down list tag
	 */
	public static function dropDownList($name, $selection = null, $items = [], $options = [])
	{
		if (!empty($options['multiple'])) {
			return static::listBox($name, $selection, $items, $options);
		}
		$options['name'] = $name;
		unset($options['unselect']);
		$selectOptions = static::renderSelectOptions($selection, $items, $options);
		return static::tag('select', "\n" . $selectOptions . "\n", $options);
	}
}