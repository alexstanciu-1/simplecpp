Doc Status: planning

Resolved in current scanner-owned pre-tokenizer path:

The earlier php-ast attachment problem for outer closure/arrow return annotations is no longer handled by trusting raw `docComment` ownership.

The current frontend path:

- scans supported shorthand or annotation sites before `php-ast`
- rewrites source into a PHP-compatible form
- preserves explicit site ownership metadata for function/method/closure return slots

So a form such as:

```php
$make = fn($x int) function<function<int(int)>(int)> =>
	fn($y int): int => $x + $y;
```

is now handled through scanner-owned return-site metadata rather than relying on php-ast to attach the outer annotation to the correct arrow node.

This note remains useful only as background on why the pre-tokenizer/scanner ownership layer exists.


