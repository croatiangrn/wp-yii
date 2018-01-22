<?php

namespace rnd\helpers;


use Rnd;
use rnd\base\BaseModel;
use rnd\base\InvalidParamException;
use rnd\validators\StringValidator;

class BaseHtml {
	/**
	 * @var string Regular expression used for attribute name validation.
	 * @since 2.0.12
	 */
	public static $attributeRegex = '/(^|.*\])([\w\.\+]+)(\[.*|$)/u';

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
	 * The [[\rnd\base\Application::charset|application charset]] will be used for encoding.
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
	public static function tag($name, $content = '', array $options = [])
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
	public static function a($text, $url = "", array $options = [])
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
	 * @param array|$src the image URL. This parameter will be processed by [[Url::to()]].
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated image tag
	 */
	public static function img($src, array $options = [])
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
	public static function mailto($text, $email = "", array $options = [])
	{
		$options['href'] = 'mailto:' . (empty($email) === true ? $text : $email);
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
	public static function input($type, $name = "", $value = "", array $options = [])
	{
		if (!isset($options['type'])) {
			$options['type'] = $type;
		}
		$options['name'] = $name;
		$options['value'] = empty($value) === true ? null : $value;

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
	public static function button($content = 'Button', array $options = [])
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
	public static function submitButton($content = 'Submit', array $options = [])
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
	 * Generates a radio button input.
	 * @param string $name the name attribute.
	 * @param bool $checked whether the radio button should be checked.
	 * @param array $options the tag options in terms of name-value pairs.
	 * See [[booleanInput()]] for details about accepted attributes.
	 *
	 * @return string the generated radio button tag
	 */
	public static function radio($name, $checked = false, $options = [])
	{
		return static::booleanInput('radio', $name, $checked, $options);
	}

	/**
	 * Generates a boolean input.
	 * @param string $type the input type. This can be either `radio` or `checkbox`.
	 * @param string $name the name attribute.
	 * @param bool $checked whether the checkbox should be checked.
	 * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
	 *
	 * - uncheck: string, the value associated with the uncheck state of the checkbox. When this attribute
	 *   is present, a hidden input will be generated so that if the checkbox is not checked and is submitted,
	 *   the value of this attribute will still be submitted to the server via the hidden input.
	 * - label: string, a label displayed next to the checkbox.  It will NOT be HTML-encoded. Therefore you can pass
	 *   in HTML code such as an image tag. If this is is coming from end users, you should [[encode()]] it to prevent XSS attacks.
	 *   When this option is specified, the checkbox will be enclosed by a label tag.
	 * - labelOptions: array, the HTML attributes for the label tag. Do not set this option unless you set the "label" option.
	 *
	 * The rest of the options will be rendered as the attributes of the resulting checkbox tag. The values will
	 * be HTML-encoded using [[encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated checkbox tag
	 * @since 2.0.9
	 */
	protected static function booleanInput($type, $name, $checked = false, $options = [])
	{
		$options['checked'] = (bool) $checked;
		$value = array_key_exists('value', $options) ? $options['value'] : '1';
		if (isset($options['uncheck'])) {
			// add a hidden field so that if the checkbox is not selected, it still submits a value
			$hiddenOptions = [];
			if (isset($options['form'])) {
				$hiddenOptions['form'] = $options['form'];
			}
			$hidden = static::hiddenInput($name, $options['uncheck'], $hiddenOptions);
			unset($options['uncheck']);
		} else {
			$hidden = '';
		}
		if (isset($options['label'])) {
			$label = $options['label'];
			$labelOptions = isset($options['labelOptions']) ? $options['labelOptions'] : [];
			unset($options['label'], $options['labelOptions']);
			$content = static::label(static::input($type, $name, $value, $options) . ' ' . $label, null, $labelOptions);
			return $hidden . $content;
		}
		return $hidden . static::input($type, $name, $value, $options);
	}

	/**
	 * Renders the option tags that can be used by [[dropDownList()]] and [[listBox()]].
	 * @param string|array|null $selection the selected value(s). String for single or array for multiple selection(s).
	 * @param array $items the option data items. The array keys are option values, and the array values
	 * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
	 * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
	 * If you have a list of data models, you may convert them into the format described above using
	 * [[\rnd\helpers\ArrayHelper::map()]].
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
	 * [[\rnd\helpers\ArrayHelper::map()]].
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

	/**
	 * Generates a list of radio buttons.
	 * A radio button list is like a checkbox list, except that it only allows single selection.
	 * @param string $name the name attribute of each radio button.
	 * @param string|array|null $selection the selected value(s). String for single or array for multiple selection(s).
	 * @param array $items the data item used to generate the radio buttons.
	 * The array keys are the radio button values, while the array values are the corresponding labels.
	 * @param array $options options (name => config) for the radio button list container tag.
	 * The following options are specially handled:
	 *
	 * - tag: string|false, the tag name of the container element. False to render radio buttons without container.
	 *   See also [[tag()]].
	 * - unselect: string, the value that should be submitted when none of the radio buttons is selected.
	 *   By setting this option, a hidden input will be generated.
	 * - encode: boolean, whether to HTML-encode the checkbox labels. Defaults to true.
	 *   This option is ignored if `item` option is set.
	 * - separator: string, the HTML code that separates items.
	 * - itemOptions: array, the options for generating the radio button tag using [[radio()]].
	 * - item: callable, a callback that can be used to customize the generation of the HTML code
	 *   corresponding to a single item in $items. The signature of this callback must be:
	 *
	 *   ```php
	 *   function ($index, $label, $name, $checked, $value)
	 *   ```
	 *
	 *   where $index is the zero-based index of the radio button in the whole list; $label
	 *   is the label for the radio button; and $name, $value and $checked represent the name,
	 *   value and the checked status of the radio button input, respectively.
	 *
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated radio button list
	 */
	public static function radioList($name, $selection = null, $items = [], $options = [])
	{
		$formatter = ArrayHelper::remove($options, 'item');
		$itemOptions = ArrayHelper::remove($options, 'itemOptions', []);
		$encode = ArrayHelper::remove($options, 'encode', true);
		$separator = ArrayHelper::remove($options, 'separator', "\n");
		$tag = ArrayHelper::remove($options, 'tag', 'div');
		// add a hidden field so that if the list box has no option being selected, it still submits a value
		$hidden = isset($options['unselect']) ? static::hiddenInput($name, $options['unselect']) : '';
		unset($options['unselect']);
		$lines = [];
		$index = 0;
		foreach ($items as $value => $label) {
			$checked = $selection !== null &&
			           (!ArrayHelper::isTraversable($selection) && !strcmp($value, $selection)
			            || ArrayHelper::isTraversable($selection) && ArrayHelper::isIn($value, $selection));
			if ($formatter !== null) {
				$lines[] = call_user_func($formatter, $index, $label, $name, $checked, $value);
			} else {
				$lines[] = static::radio($name, $checked, array_merge($itemOptions, [
					'value' => $value,
					'label' => $encode ? static::encode($label) : $label,
				]));
			}
			$index++;
		}
		$visibleContent = implode($separator, $lines);
		if ($tag === false) {
			return $hidden . $visibleContent;
		}
		return $hidden . static::tag($tag, $visibleContent, $options);
	}

	/**
	 * Generates a list box.
	 * @param string $name the input name
	 * @param string|array|null $selection the selected value(s). String for single or array for multiple selection(s).
	 * @param array $items the option data items. The array keys are option values, and the array values
	 * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
	 * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
	 * If you have a list of data models, you may convert them into the format described above using
	 * [[\rnd\helpers\ArrayHelper::map()]].
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
	 * - unselect: string, the value that will be submitted when no option is selected.
	 *   When this attribute is set, a hidden field will be generated so that if no option is selected in multiple
	 *   mode, we can still obtain the posted unselect value.
	 * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
	 *   Defaults to false.
	 * - encode: bool, whether to encode option prompt and option value characters.
	 *   Defaults to `true`. This option is available since 2.0.3.
	 *
	 * The rest of the options will be rendered as the attributes of the resulting tag. The values will
	 * be HTML-encoded using [[encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated list box tag
	 */
	public static function listBox($name, $selection = null, $items = [], $options = [])
	{
		if (!array_key_exists('size', $options)) {
			$options['size'] = 4;
		}
		if (!empty($options['multiple']) && !empty($name) && substr_compare($name, '[]', -2, 2)) {
			$name .= '[]';
		}
		$options['name'] = $name;
		if (isset($options['unselect'])) {
			// add a hidden field so that if the list box has no option being selected, it still submits a value
			if (!empty($name) && substr_compare($name, '[]', -2, 2) === 0) {
				$name = substr($name, 0, -2);
			}
			$hidden = static::hiddenInput($name, $options['unselect']);
			unset($options['unselect']);
		} else {
			$hidden = '';
		}
		$selectOptions = static::renderSelectOptions($selection, $items, $options);
		return $hidden . static::tag('select', "\n" . $selectOptions . "\n", $options);
	}

	/**
	 * Adds a CSS class (or several classes) to the specified options.
	 *
	 * If the CSS class is already in the options, it will not be added again.
	 * If class specification at given options is an array, and some class placed there with the named (string) key,
	 * overriding of such key will have no effect. For example:
	 *
	 * ```php
	 * $options = ['class' => ['persistent' => 'initial']];
	 * Html::addCssClass($options, ['persistent' => 'override']);
	 * var_dump($options['class']); // outputs: array('persistent' => 'initial');
	 * ```
	 *
	 * @param array $options the options to be modified.
	 * @param string|array $class the CSS class(es) to be added
	 */
	public static function addCssClass(&$options, $class)
	{
		if (isset($options['class'])) {
			if (is_array($options['class'])) {
				$options['class'] = self::mergeCssClasses($options['class'], (array) $class);
			} else {
				$classes = preg_split('/\s+/', $options['class'], -1, PREG_SPLIT_NO_EMPTY);
				$options['class'] = implode(' ', self::mergeCssClasses($classes, (array) $class));
			}
		} else {
			$options['class'] = $class;
		}
	}

	/**
	 * Merges already existing CSS classes with new one.
	 * This method provides the priority for named existing classes over additional.
	 * @param array $existingClasses already existing CSS classes.
	 * @param array $additionalClasses CSS classes to be added.
	 * @return array merge result.
	 */
	private static function mergeCssClasses(array $existingClasses, array $additionalClasses)
	{
		foreach ($additionalClasses as $key => $class) {
			if (is_int($key) && !in_array($class, $existingClasses)) {
				$existingClasses[] = $class;
			} elseif (!isset($existingClasses[$key])) {
				$existingClasses[$key] = $class;
			}
		}

		return array_unique($existingClasses);
	}

	/**
	 * Returns the real attribute name from the given attribute expression.
	 *
	 * An attribute expression is an attribute name prefixed and/or suffixed with array indexes.
	 * It is mainly used in tabular data input and/or input of array type. Below are some examples:
	 *
	 * - `[0]content` is used in tabular data input to represent the "content" attribute
	 *   for the first model in tabular input;
	 * - `dates[0]` represents the first array element of the "dates" attribute;
	 * - `[0]dates[0]` represents the first array element of the "dates" attribute
	 *   for the first model in tabular input.
	 *
	 * If `$attribute` has neither prefix nor suffix, it will be returned back without change.
	 * @param string $attribute the attribute name or expression
	 * @return string the attribute name without prefix and suffix.
	 * @throws InvalidParamException if the attribute name contains non-word characters.
	 */
	public static function getAttributeName($attribute)
	{
		if (preg_match(static::$attributeRegex, $attribute, $matches)) {
			return $matches[2];
		}
		throw new InvalidParamException('Attribute name must contain word characters only.');
	}

	/**
	 * Generates a label tag for the given model attribute.
	 * The label text is the label associated with the attribute, obtained via [[Model::getAttributeLabel()]].
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * The following options are specially handled:
	 *
	 * - label: this specifies the label to be displayed. Note that this will NOT be [[encode()|encoded]].
	 *   If this is not set, [[Model::getAttributeLabel()]] will be called to get the label for display
	 *   (after encoding).
	 *
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated label tag
	 */
	public static function activeLabel($model, $attribute, $options = [])
	{
		$for = ArrayHelper::remove($options, 'for', static::getInputId($model, $attribute));
		$attribute = static::getAttributeName($attribute);
		$label = ArrayHelper::remove($options, 'label', static::encode($model->getAttributeLabel($attribute)));
		return static::label($label, $for, $options);
	}

	/**
	 * Generates an appropriate input ID for the specified attribute name or expression.
	 *
	 * This method converts the result [[getInputName()]] into a valid input ID.
	 * For example, if [[getInputName()]] returns `Post[content]`, this method will return `post-content`.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for explanation of attribute expression.
	 * @return string the generated input ID
	 * @throws InvalidParamException if the attribute name contains non-word characters.
	 */
	public static function getInputId($model, $attribute)
	{
		$name = strtolower(static::getInputName($model, $attribute));
		return str_replace(['[]', '][', '[', ']', ' ', '.'], ['', '-', '-', '', '-', '-'], $name);
	}

	/**
	 * Generates an appropriate input name for the specified attribute name or expression.
	 *
	 * This method generates a name that can be used as the input name to collect user input
	 * for the specified attribute. The name is generated according to the [[Model::formName|form name]]
	 * of the model and the given attribute name. For example, if the form name of the `Post` model
	 * is `Post`, then the input name generated for the `content` attribute would be `Post[content]`.
	 *
	 * See [[getAttributeName()]] for explanation of attribute expression.
	 *
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression
	 * @return string the generated input name
	 * @throws InvalidParamException if the attribute name contains non-word characters.
	 */
	public static function getInputName($model, $attribute)
	{
		$formName = $model->formName();
		if (!preg_match(static::$attributeRegex, $attribute, $matches)) {
			throw new InvalidParamException('Attribute name must contain word characters only.');
		}
		$prefix = $matches[1];
		$attribute = $matches[2];
		$suffix = $matches[3];
		if ($formName === '' && $prefix === '') {
			return $attribute . $suffix;
		} elseif ($formName !== '') {
			return $formName . $prefix . "[$attribute]" . $suffix;
		}
		throw new InvalidParamException(get_class($model) . '::formName() cannot be empty for tabular inputs.');
	}

	/**
	 * Generates a tag that contains the first validation error of the specified model attribute.
	 * Note that even if there is no validation error, this method will still return an empty error tag.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs. The values will be HTML-encoded
	 * using [[encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 *
	 * The following options are specially handled:
	 *
	 * - tag: this specifies the tag name. If not set, "div" will be used.
	 *   See also [[tag()]].
	 * - encode: boolean, if set to false then the error message won't be encoded.
	 * - errorSource (since 2.0.14): \Closure|callable, callback that will be called to obtain an error message.
	 *   The signature of the callback must be: `function ($model, $attribute)` and return a string.
	 *   When not set, the `$model->getFirstError()` method will be called.
	 *
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated label tag
	 */
	public static function error($model, $attribute, $options = [])
	{
		$attribute = static::getAttributeName($attribute);
		$errorSource = ArrayHelper::remove($options, 'errorSource');
		if ($errorSource !== null) {
			$error = call_user_func($errorSource, $model, $attribute);
		} else {
			$error = $model->getFirstError($attribute);
		}
		$tag = ArrayHelper::remove($options, 'tag', 'div');
		$encode = ArrayHelper::remove($options, 'encode', true);
		return Html::tag($tag, $encode ? Html::encode($error) : $error, $options);
	}

	/**
	 * Generates a hint tag for the given model attribute.
	 * The hint text is the hint associated with the attribute, obtained via [[Model::getAttributeHint()]].
	 * If no hint content can be obtained, method will return an empty string.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * The following options are specially handled:
	 *
	 * - hint: this specifies the hint to be displayed. Note that this will NOT be [[encode()|encoded]].
	 *   If this is not set, [[Model::getAttributeHint()]] will be called to get the hint for display
	 *   (without encoding).
	 *
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated hint tag
	 * @since 2.0.4
	 */
	public static function activeHint($model, $attribute, $options = [])
	{
		$attribute = static::getAttributeName($attribute);
		$hint = isset($options['hint']) ? $options['hint'] : $model->getAttributeHint($attribute);
		if (empty($hint)) {
			return '';
		}
		$tag = ArrayHelper::remove($options, 'tag', 'div');
		unset($options['hint']);
		return static::tag($tag, $hint, $options);
	}

	/**
	 * Generates a text area input.
	 * @param string $name the input name
	 * @param string $value the input value. Note that it will be encoded using [[encode()]].
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * The following special options are recognized:
	 *
	 * - `doubleEncode`: whether to double encode HTML entities in `$value`. If `false`, HTML entities in `$value` will not
	 *   be further encoded. This option is available since version 2.0.11.
	 *
	 * @return string the generated text area tag
	 */
	public static function textarea($name, $value = '', $options = [])
	{
		$options['name'] = $name;
		$doubleEncode = ArrayHelper::remove($options, 'doubleEncode', true);
		return static::tag('textarea', static::encode($value, $doubleEncode), $options);
	}

	/**
	 * Generates an input tag for the given model attribute.
	 * This method will generate the "name" and "value" tag attributes automatically for the model attribute
	 * unless they are explicitly specified in `$options`.
	 * @param string $type the input type (e.g. 'text', 'password')
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated input tag
	 */
	public static function activeInput($type, $model, $attribute, $options = [])
	{
		$name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : static::getAttributeValue($model, $attribute);
		if (!array_key_exists('id', $options)) {
			$options['id'] = static::getInputId($model, $attribute);
		}
		self::setActivePlaceholder($model, $attribute, $options);
		return static::input($type, $name, $value, $options);
	}

	/**
	 * Returns the value of the specified attribute name or expression.
	 *
	 * For an attribute expression like `[0]dates[0]`, this method will return the value of `$model->dates[0]`.
	 * See [[getAttributeName()]] for more details about attribute expression.
	 *
	 * If an attribute value is an instance of [[ActiveRecordInterface]] or an array of such instances,
	 * the primary value(s) of the AR instance(s) will be returned instead.
	 *
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression
	 * @return string|array the corresponding attribute value
	 * @throws InvalidParamException if the attribute name contains non-word characters.
	 */
	public static function getAttributeValue($model, $attribute)
	{
		if (!preg_match(static::$attributeRegex, $attribute, $matches)) {
			throw new InvalidParamException('Attribute name must contain word characters only.');
		}
		$attribute = $matches[2];
		$value = $model->$attribute;
		if ($matches[3] !== '') {
			foreach (explode('][', trim($matches[3], '[]')) as $id) {
				if ((is_array($value) || $value instanceof \ArrayAccess) && isset($value[$id])) {
					$value = $value[$id];
				} else {
					return null;
				}
			}
		}

		return $value;
	}

	/**
	 * If `maxlength` option is set true and the model attribute is validated by a string validator,
	 * the `maxlength` option will take the value of [[\rnd\validators\StringValidator::max]].
	 *
	 * @param BaseModel $model     the model object
	 * @param string    $attribute the attribute name or expression.
	 * @param array     $options   the tag options in terms of name-value pairs.
	 *
	 * @throws \rnd\base\InvalidConfigException
	 */
	private static function normalizeMaxLength($model, $attribute, &$options)
	{
		if (isset($options['maxlength']) && $options['maxlength'] === true) {
			unset($options['maxlength']);
			$attrName = static::getAttributeName($attribute);
			foreach ($model->getActiveValidators($attrName) as $validator) {
				if ($validator instanceof StringValidator && $validator->max !== null) {
					$options['maxlength'] = $validator->max;
					break;
				}
			}
		}
	}

	/**
	 * Generates a text input tag for the given model attribute.
	 * This method will generate the "name" and "value" tag attributes automatically for the model attribute
	 * unless they are explicitly specified in `$options`.
	 *
	 * @param BaseModel $model     the model object
	 * @param string    $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 *                             about attribute expression.
	 * @param array     $options   the tag options in terms of name-value pairs. These will be rendered as
	 *                             the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 *                             See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *                             The following special options are recognized:
	 *
	 * - maxlength: integer|boolean, when `maxlength` is set true and the model attribute is validated
	 *   by a string validator, the `maxlength` option will take the value of [[\rnd\validators\StringValidator::max]].
	 *   This is available since version 2.0.3.
	 * - placeholder: string|boolean, when `placeholder` equals `true`, the attribute label from the $model will be used
	 *   as a placeholder (this behavior is available since version 2.0.14).
	 *
	 * @return string the generated input tag
	 * @throws \rnd\base\InvalidConfigException
	 */
	public static function activeTextInput($model, $attribute, $options = [])
	{
		self::normalizeMaxLength($model, $attribute, $options);
		return static::activeInput('text', $model, $attribute, $options);
	}

	/**
	 * Generate placeholder from model attribute label.
	 *
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * @since 2.0.14
	 */
	protected static function setActivePlaceholder($model, $attribute, &$options = [])
	{
		if (isset($options['placeholder']) && $options['placeholder'] === true) {
			$options['placeholder'] = $model->getAttributeLabel($attribute);
		}
	}

	/**
	 * Generates a hidden input tag for the given model attribute.
	 * This method will generate the "name" and "value" tag attributes automatically for the model attribute
	 * unless they are explicitly specified in `$options`.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated input tag
	 */
	public static function activeHiddenInput($model, $attribute, $options = [])
	{
		return static::activeInput('hidden', $model, $attribute, $options);
	}

	/**
	 * Generates a password input tag for the given model attribute.
	 * This method will generate the "name" and "value" tag attributes automatically for the model attribute
	 * unless they are explicitly specified in `$options`.
	 *
	 * @param BaseModel $model     the model object
	 * @param string    $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 *                             about attribute expression.
	 * @param array     $options   the tag options in terms of name-value pairs. These will be rendered as
	 *                             the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 *                             See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *                             The following special options are recognized:
	 *
	 * - maxlength: integer|boolean, when `maxlength` is set true and the model attribute is validated
	 *   by a string validator, the `maxlength` option will take the value of [[\rnd\validators\StringValidator::max]].
	 *   This option is available since version 2.0.6.
	 * - placeholder: string|boolean, when `placeholder` equals `true`, the attribute label from the $model will be used
	 *   as a placeholder (this behavior is available since version 2.0.14).
	 *
	 * @return string the generated input tag
	 * @throws \rnd\base\InvalidConfigException
	 */
	public static function activePasswordInput($model, $attribute, $options = [])
	{
		self::normalizeMaxLength($model, $attribute, $options);
		return static::activeInput('password', $model, $attribute, $options);
	}

	/**
	 * Generates a file input tag for the given model attribute.
	 * This method will generate the "name" and "value" tag attributes automatically for the model attribute
	 * unless they are explicitly specified in `$options`.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 * @return string the generated input tag
	 */
	public static function activeFileInput($model, $attribute, $options = [])
	{
		// add a hidden field so that if a model only has a file field, we can
		// still use isset($_POST[$modelClass]) to detect if the input is submitted
		$hiddenOptions = ['id' => null, 'value' => ''];
		if (isset($options['name'])) {
			$hiddenOptions['name'] = $options['name'];
		}
		return static::activeHiddenInput($model, $attribute, $hiddenOptions)
		       . static::activeInput('file', $model, $attribute, $options);
	}

	/**
	 * Generates a textarea tag for the given model attribute.
	 * The model attribute value will be used as the content in the textarea.
	 *
	 * @param BaseModel  $model     the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 *                          about attribute expression.
	 * @param array  $options   the tag options in terms of name-value pairs. These will be rendered as
	 *                          the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 *                          See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *                          The following special options are recognized:
	 *
	 * - maxlength: integer|boolean, when `maxlength` is set true and the model attribute is validated
	 *   by a string validator, the `maxlength` option will take the value of [[\rnd\validators\StringValidator::max]].
	 *   This option is available since version 2.0.6.
	 * - placeholder: string|boolean, when `placeholder` equals `true`, the attribute label from the $model will be used
	 *   as a placeholder (this behavior is available since version 2.0.14).
	 *
	 * @return string the generated textarea tag
	 * @throws \rnd\base\InvalidConfigException
	 */
	public static function activeTextarea($model, $attribute, $options = [])
	{
		$name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
		if (isset($options['value'])) {
			$value = $options['value'];
			unset($options['value']);
		} else {
			$value = static::getAttributeValue($model, $attribute);
		}
		if (!array_key_exists('id', $options)) {
			$options['id'] = static::getInputId($model, $attribute);
		}
		self::normalizeMaxLength($model, $attribute, $options);
		self::setActivePlaceholder($model, $attribute, $options);
		return static::textarea($name, $value, $options);
	}
	/**
	 * Generates a radio button tag together with a label for the given model attribute.
	 * This method will generate the "checked" tag attribute according to the model attribute value.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs.
	 * See [[booleanInput()]] for details about accepted attributes.
	 *
	 * @return string the generated radio button tag
	 */
	public static function activeRadio($model, $attribute, $options = [])
	{
		return static::activeBooleanInput('radio', $model, $attribute, $options);
	}
	/**
	 * Generates a checkbox tag together with a label for the given model attribute.
	 * This method will generate the "checked" tag attribute according to the model attribute value.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs.
	 * See [[booleanInput()]] for details about accepted attributes.
	 *
	 * @return string the generated checkbox tag
	 */
	public static function activeCheckbox($model, $attribute, $options = [])
	{
		return static::activeBooleanInput('checkbox', $model, $attribute, $options);
	}
	/**
	 * Generates a boolean input
	 * This method is mainly called by [[activeCheckbox()]] and [[activeRadio()]].
	 * @param string $type the input type. This can be either `radio` or `checkbox`.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $options the tag options in terms of name-value pairs.
	 * See [[booleanInput()]] for details about accepted attributes.
	 * @return string the generated input element
	 * @since 2.0.9
	 */
	protected static function activeBooleanInput($type, $model, $attribute, $options = [])
	{
		$name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
		$value = static::getAttributeValue($model, $attribute);
		if (!array_key_exists('value', $options)) {
			$options['value'] = '1';
		}
		if (!array_key_exists('uncheck', $options)) {
			$options['uncheck'] = '0';
		} elseif ($options['uncheck'] === false) {
			unset($options['uncheck']);
		}
		if (!array_key_exists('label', $options)) {
			$options['label'] = static::encode($model->getAttributeLabel(static::getAttributeName($attribute)));
		} elseif ($options['label'] === false) {
			unset($options['label']);
		}
		$checked = "$value" === "{$options['value']}";
		if (!array_key_exists('id', $options)) {
			$options['id'] = static::getInputId($model, $attribute);
		}
		return static::$type($name, $checked, $options);
	}
	/**
	 * Generates a drop-down list for the given model attribute.
	 * The selection of the drop-down list is taken from the value of the model attribute.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $items the option data items. The array keys are option values, and the array values
	 * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
	 * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
	 * If you have a list of data models, you may convert them into the format described above using
	 * [[\rnd\helpers\ArrayHelper::map()]].
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
	public static function activeDropDownList($model, $attribute, $items, $options = [])
	{
		if (empty($options['multiple'])) {
			return static::activeListInput('dropDownList', $model, $attribute, $items, $options);
		}
		return static::activeListBox($model, $attribute, $items, $options);
	}
	/**
	 * Generates a list box.
	 * The selection of the list box is taken from the value of the model attribute.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $items the option data items. The array keys are option values, and the array values
	 * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
	 * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
	 * If you have a list of data models, you may convert them into the format described above using
	 * [[\rnd\helpers\ArrayHelper::map()]].
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
	 * - unselect: string, the value that will be submitted when no option is selected.
	 *   When this attribute is set, a hidden field will be generated so that if no option is selected in multiple
	 *   mode, we can still obtain the posted unselect value.
	 * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
	 *   Defaults to false.
	 * - encode: bool, whether to encode option prompt and option value characters.
	 *   Defaults to `true`. This option is available since 2.0.3.
	 *
	 * The rest of the options will be rendered as the attributes of the resulting tag. The values will
	 * be HTML-encoded using [[encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated list box tag
	 */
	public static function activeListBox($model, $attribute, $items, $options = [])
	{
		return static::activeListInput('listBox', $model, $attribute, $items, $options);
	}
	/**
	 * Generates a list of checkboxes.
	 * A checkbox list allows multiple selection, like [[listBox()]].
	 * As a result, the corresponding submitted value is an array.
	 * The selection of the checkbox list is taken from the value of the model attribute.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $items the data item used to generate the checkboxes.
	 * The array keys are the checkbox values, and the array values are the corresponding labels.
	 * Note that the labels will NOT be HTML-encoded, while the values will.
	 * @param array $options options (name => config) for the checkbox list container tag.
	 * The following options are specially handled:
	 *
	 * - tag: string|false, the tag name of the container element. False to render checkbox without container.
	 *   See also [[tag()]].
	 * - unselect: string, the value that should be submitted when none of the checkboxes is selected.
	 *   You may set this option to be null to prevent default value submission.
	 *   If this option is not set, an empty string will be submitted.
	 * - encode: boolean, whether to HTML-encode the checkbox labels. Defaults to true.
	 *   This option is ignored if `item` option is set.
	 * - separator: string, the HTML code that separates items.
	 * - itemOptions: array, the options for generating the checkbox tag using [[checkbox()]].
	 * - item: callable, a callback that can be used to customize the generation of the HTML code
	 *   corresponding to a single item in $items. The signature of this callback must be:
	 *
	 *   ```php
	 *   function ($index, $label, $name, $checked, $value)
	 *   ```
	 *
	 *   where $index is the zero-based index of the checkbox in the whole list; $label
	 *   is the label for the checkbox; and $name, $value and $checked represent the name,
	 *   value and the checked status of the checkbox input.
	 *
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated checkbox list
	 */
	public static function activeCheckboxList($model, $attribute, $items, $options = [])
	{
		return static::activeListInput('checkboxList', $model, $attribute, $items, $options);
	}
	/**
	 * Generates a list of radio buttons.
	 * A radio button list is like a checkbox list, except that it only allows single selection.
	 * The selection of the radio buttons is taken from the value of the model attribute.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $items the data item used to generate the radio buttons.
	 * The array keys are the radio values, and the array values are the corresponding labels.
	 * Note that the labels will NOT be HTML-encoded, while the values will.
	 * @param array $options options (name => config) for the radio button list container tag.
	 * The following options are specially handled:
	 *
	 * - tag: string|false, the tag name of the container element. False to render radio button without container.
	 *   See also [[tag()]].
	 * - unselect: string, the value that should be submitted when none of the radio buttons is selected.
	 *   You may set this option to be null to prevent default value submission.
	 *   If this option is not set, an empty string will be submitted.
	 * - encode: boolean, whether to HTML-encode the checkbox labels. Defaults to true.
	 *   This option is ignored if `item` option is set.
	 * - separator: string, the HTML code that separates items.
	 * - itemOptions: array, the options for generating the radio button tag using [[radio()]].
	 * - item: callable, a callback that can be used to customize the generation of the HTML code
	 *   corresponding to a single item in $items. The signature of this callback must be:
	 *
	 *   ```php
	 *   function ($index, $label, $name, $checked, $value)
	 *   ```
	 *
	 *   where $index is the zero-based index of the radio button in the whole list; $label
	 *   is the label for the radio button; and $name, $value and $checked represent the name,
	 *   value and the checked status of the radio button input.
	 *
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated radio button list
	 */
	public static function activeRadioList($model, $attribute, $items, $options = [])
	{
		return static::activeListInput('radioList', $model, $attribute, $items, $options);
	}
	/**
	 * Generates a list of input fields.
	 * This method is mainly called by [[activeListBox()]], [[activeRadioList()]] and [[activeCheckboxList()]].
	 * @param string $type the input type. This can be 'listBox', 'radioList', or 'checkBoxList'.
	 * @param BaseModel $model the model object
	 * @param string $attribute the attribute name or expression. See [[getAttributeName()]] for the format
	 * about attribute expression.
	 * @param array $items the data item used to generate the input fields.
	 * The array keys are the input values, and the array values are the corresponding labels.
	 * Note that the labels will NOT be HTML-encoded, while the values will.
	 * @param array $options options (name => config) for the input list. The supported special options
	 * depend on the input type specified by `$type`.
	 * @return string the generated input list
	 */
	protected static function activeListInput($type, $model, $attribute, $items, $options = [])
	{
		$name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
		$selection = isset($options['value']) ? $options['value'] : static::getAttributeValue($model, $attribute);
		if (!array_key_exists('unselect', $options)) {
			$options['unselect'] = '';
		}
		if (!array_key_exists('id', $options)) {
			$options['id'] = static::getInputId($model, $attribute);
		}
		return static::$type($name, $selection, $items, $options);
	}

	/**
	 * Generates a summary of the validation errors.
	 * If there is no validation error, an empty error summary markup will still be generated, but it will be hidden.
	 * @param BaseModel|BaseModel[] $models the model(s) whose validation errors are to be displayed.
	 * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
	 *
	 * - header: string, the header HTML for the error summary. If not set, a default prompt string will be used.
	 * - footer: string, the footer HTML for the error summary. Defaults to empty string.
	 * - encode: boolean, if set to false then the error messages won't be encoded. Defaults to `true`.
	 * - showAllErrors: boolean, if set to true every error message for each attribute will be shown otherwise
	 *   only the first error message for each attribute will be shown. Defaults to `false`.
	 *   Option is available since 2.0.10.
	 *
	 * The rest of the options will be rendered as the attributes of the container tag.
	 *
	 * @return string the generated error summary
	 */
	public static function errorSummary($models, $options = [])
	{
		$header = isset($options['header']) ? $options['header'] : '<p>' . Rnd::t('Please fix the following errors:', 'rnd') . '</p>';
		$footer = ArrayHelper::remove($options, 'footer', '');
		$encode = ArrayHelper::remove($options, 'encode', true);
		$showAllErrors = ArrayHelper::remove($options, 'showAllErrors', false);
		unset($options['header']);
		$lines = self::collectErrors($models, $encode, $showAllErrors);
		if (empty($lines)) {
			// still render the placeholder for client-side validation use
			$content = '<ul></ul>';
			$options['style'] = isset($options['style']) ? rtrim($options['style'], ';') . '; display:none' : 'display:none';
		} else {
			$content = '<ul><li>' . implode("</li>\n<li>", $lines) . '</li></ul>';
		}
		return Html::tag('div', $header . $content . $footer, $options);
	}

	/**
	 * Return array of the validation errors
	 * @param BaseModel|BaseModel[] $models the model(s) whose validation errors are to be displayed.
	 * @param $encode boolean, if set to false then the error messages won't be encoded.
	 * @param $showAllErrors boolean, if set to true every error message for each attribute will be shown otherwise
	 * only the first error message for each attribute will be shown.
	 * @return array of the validation errors
	 * @since 2.0.14
	 */
	private static function collectErrors($models, $encode, $showAllErrors)
	{
		$lines = [];
		if (!is_array($models)) {
			$models = [$models];
		}
		foreach ($models as $model) {
			$lines = array_unique(array_merge($lines, $model->getErrorSummary($showAllErrors)));
		}
		if ($encode) {
			for ($i = 0, $linesCount = count($lines); $i < $linesCount; $i++) {
				$lines[$i] = Html::encode($lines[$i]);
			}
		}
		return $lines;
	}
}