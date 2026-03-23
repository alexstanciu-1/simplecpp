<?php

namespace A;

function f() {
	echo "A\\f\n";
}

class X {
	public static function make(): string {
		return "A\\X";
	}
}

f();
echo X::make(), "\n";
echo \strlen("abc"), "\n";
