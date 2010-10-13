<?php

class Foo{
	
	public $bar=null;
	
	function __construct($a){
		$this->bar = $a;
	}
	
	function setBar($a){
		$this->bar = $a;
	}
}

$a = new Foo(imagecreatetruecolor(100, 100));

$b = clone $a;

$b->setBar($a);

$a = null;

var_dump($b->bar);

?>