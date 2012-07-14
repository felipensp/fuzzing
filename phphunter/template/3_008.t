<?php

class foo extends %{classname} {
	public $foo = 'test';
	
	public function __construct() { }
}

$x = new foo;
var_dump($x);