Your intended syntax is:

$make = fn(int $x) /** function<function<int(int)>(int)> */ =>
	fn(int $y): int => $x + $y + $a;

But php-ast is not attaching that doc comment to the outer arrow.

What the AST shows

Outer arrow at line 6:

kind: 72
docComment: null
returnType: null

Inner arrow at line 7:

kind: 72
docComment: "/** function<function<int(int)>(int)> */"

So the annotation written after the outer arrow signature is being attached by php-ast to the inner returned arrow expression.



