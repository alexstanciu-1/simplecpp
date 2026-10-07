<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function nullable_handles_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/nullable_handles', '/nullable_handles', '/nullable_handles');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$results = [];
foreach (['C', 'shared<S>', 'unique<C>', 'unique<S>', 'weak<C>', 'weak<S>'] as $index => $handle) {
	foreach (['?' . $handle, 'nullable<' . $handle . '>'] as $spelling => $type) {
		$observe = str_starts_with($handle, 'weak') ? 'if (lock($a)) { return 1; }' : 'if (isset($a)) { return 1; }';
		$source = 'class C {} struct S {} function accept(' . $type . ' &$v): void {} '
			. '$a ' . $handle . ' = null; accept($a); $b ' . $type . ' = null; accept($b); ' . $observe . ' return 0;';
		$output = nullable_handles_source($source);
		if (str_contains($output, 'scpp::nullable<')) { throw new \LogicException('Nullable handle created an extra runtime layer'); }
		if (isset($argv[1])) {
			$path = $argv[1] . '/handle_' . $index . '_' . $spelling . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => 0];
		}
	}
}
foreach ([
	'present' => 'class C { int $n = 9; } function make(): ?C { return new C(); } function id(nullable<C> $c): C { return $c; } $c ?C = id(make()); return $c->n;',
	'weak_lock' => 'class C { int $n = 9; } $c = new C(); $w ?weak<C> = weak($c); $held = lock($w); $c = null; return $held->n;',
	'unique_move' => 'class C { int $n = 9; } $c ?unique<C> = new C(); $owned nullable<unique<C>> = move($c); return $owned->n;',
	'fields' => 'class C { int $n = 9; } class Holder { ?C $a = null; nullable<C> $b = null; } $h = new Holder(); $h->a = new C(); $c = $h->a; return $c->n;',
	'nested_spelling' => 'class C { int $n = 9; } $c nullable<nullable<C>> = new C(); return $c->n;',
] as $name => $source) {
	$output = nullable_handles_source($source);
	if (str_contains($output, 'scpp::nullable<')) { throw new \LogicException('Normalized handle leaked a wrapper'); }
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
}
// Inline payloads retain nullable identity; this checkpoint does not enable their operations.
$output = nullable_handles_source('class C { int $n; } struct S { int $n; } function inspect(const ?S &$s, const ?value<C> &$c): void {} return 0;');
if (substr_count($output, 'scpp::nullable<') < 2 || str_contains($output, 'shared_p<record_C>')) { throw new \LogicException('Inline nullable wrapper identity was lost'); }
if (isset($argv[1])) {
	$path = $argv[1] . '/inline_model.cpp';
	file_put_contents($path, $output);
	$results[] = ['path' => $path, 'exit_code' => 0];
}
foreach ([
	'class C {} $a ?unique<C>; $b = $a;',
	'class C {} $a ?shared<C>;',
	'class C {} $a ?weak<C>; if ($a) {}',
	'class C {} $a nullable<C, int>;',
] as $source) {
	try { nullable_handles_source($source); throw new \LogicException('Normalization bypassed existing boundary'); }
	catch (\RuntimeException $expected) {}
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "nullable handles: PASS\n";
