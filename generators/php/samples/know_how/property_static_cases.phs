<?php

class A {
	public int $x = 5;
	public static int $s = 9;

	public static function make(): A {
		return new A();
	}
}

$a = new A();
echo $a->x, "\n";
echo A::$s, "\n";
echo A::make()->x, "\n";
