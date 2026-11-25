<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$map = require __DIR__ . '/../../tools/php_portability/function_map.php';
foreach (['v0.1', 'v0.2'] as $target) {
	$converter = new scpp\portability\Converter($map, $target);
	foreach ([
		'class Box { public ?array $items /** ?vector<int> */ = null; }',
		'class Box { public static ?array $items /** ?hash<vector<int>> */ = null; }',
		'class Box { public function __construct(public ?array $items /** ?vector<int> */ = null) {} }',
		'class Box { public function __construct(?array $items /** ?vector<int> */) {} }',
		'class Box { public function accept(?array $items /** ?vector<int> */ = null): void {} }',
		'class Box { public function accept(?array $items /** ?vector<int> */): void {} }',
		'class Box { public function items(): ?array /** ?vector<int> */ { return null; } }',
		'trait Lists { public function items(): ?array /** ?vector<int> */ { return null; } } class Box { use Lists; }',
		'$items /** ?vector<int> */ = null;',
		'$items /** nullable< vector<int> > */ = null;',
		'$items /** ?hash<vector<int>, string> */ = null;',
		'$items /** nullable<hash<vector<int>, string>> */ = null;',
	] as $source) {
		$output = $converter->convert('<?php ' . $source, 'valid.php');
		if (!str_contains($output, 'nullable<') || str_contains($output, 'nullable<nullable<')) {
			throw new RuntimeException('Expected exactly one outer nullable wrapper: ' . $output);
		}
	}
	foreach ([
		'public ?array $items /** vector<int> */ = null;',
		'public array $items /** ?vector<int> */ = [];',
		'public function __construct(?array $items /** vector<int> */ = null) {}',
		'public function __construct(array $items /** ?vector<int> */ = []) {}',
		'public function accept(?array $items /** vector<int> */): void {}',
		'public function accept(array $items /** ?vector<int> */): void {}',
		'public function items(): ?array /** vector<int> */ { return null; }',
		'public function items(): array /** ?vector<int> */ { return null; }',
	] as $member) {
		try { $converter->convert('<?php class Box { ' . $member . ' }', 'mismatch.php'); }
		catch (RuntimeException $error) {
			if (!str_contains($error->getMessage(), 'nullability must match')) { throw $error; }
			continue;
		}
		throw new RuntimeException('Accepted mismatched array nullability: ' . $member);
	}
	foreach ([
		'class Box { public ?array $items = null; }',
		'class Box { public ?array $items /** ?vector<int> */; }',
		'class Box { public ?array $items /** ??vector<int> */ = null; }',
		'class Box { public ?array $items /** ?vector< */ = null; }',
		'interface Lists { public function items(): ?array /** ?vector<int> */; }',
		'interface Lists { public function accept(?array $items /** ?vector<int> */): void; }',
		'$items /** ??vector<int> */ = null;',
		'$items /** nullable<nullable<vector<int>>> */ = null;',
		'$items /** ?vector<int, string> */ = null;',
		'$items /** ?vector<int> trailing */ = null;',
		'$items /** nullable<vector<int> */ = null;',
		'$items /** ?vector<void> */ = null;',
	] as $source) {
		try { $converter->convert('<?php ' . $source, 'invalid.php'); }
		catch (RuntimeException $error) { continue; }
		throw new RuntimeException('Accepted unsupported annotation or interface: ' . $source);
	}
}
echo "Complete nullable array annotations passed for both converter targets\n";
