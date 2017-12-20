<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\models;


use rnd\base\BaseModel;

class ExampleModel extends BaseModel
{
	public $name;
	public $email;
	public $price;
	public $password;
	public $password_repeat;

	public function rules()
	{
		return [
			[ [ 'name', 'email', 'price', 'password_repeat' ], 'required' ],
			[ 'password', 'compare', 'compareAttribute' => 'password_repeat' ],
			[ 'email', 'email' ],
			[ 'price', 'number' ]
		];
	}

	public function attributeLabels()
	{
		return [
			'name' => 'Ime',
			'price' => 'Cijena',
			'password_repeat' => 'Ponovljena lozinka'
		];
	}
}