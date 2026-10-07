<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function nullable_records_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/nullable_records', '/nullable_records', '/nullable_records');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$results = [];
foreach (['struct', 'class'] as $kind) {
	$type = $kind === 'struct' ? 'C' : 'value<C>';
	foreach ([
		'empty' => '$a ?PAYLOAD; $b ?PAYLOAD = null; if ($a !== null) { return 1; } if (null !== $b) { return 2; } $a = null; return 0;',
		'copy' => '$v PAYLOAD = new C(); $a ?PAYLOAD = $v; $v->n = 8; $b ?PAYLOAD = $a; $a = null; $out PAYLOAD = $b; $n = $out->n; if ($n !== 7) { return 1; } $out->n = 9; $again PAYLOAD = $b; $n = $again->n; if ($n !== 7) { return 2; } if ($b === null) { return 3; } return 0;',
		'new' => '$a ?PAYLOAD = new C(); $out PAYLOAD = $a; $n = $out->n; if ($n !== 7) { return 1; } return 0;',
		'joins' => '$v PAYLOAD = new C(); $a ?PAYLOAD; $b ?PAYLOAD = $v; $x = true ? $a : $v; if (isset($x)) { return 1; } $y = false ? $v : $b; $z = true ? $b : $a; $out PAYLOAD = $y; $out->n = 9; $original PAYLOAD = $z; $number = $original->n; if ($number !== 7) { return 2; } $e = $a ?: $v; $f = $b ?: $a; $g PAYLOAD = $e; $h PAYLOAD = $f; $number = $g->n; if ($number !== 7) { return 3; } $number = $h->n; if ($number !== 7) { return 4; } return 0;',
		'coalesce' => '$v PAYLOAD = new C(); $a ?PAYLOAD; $b ?PAYLOAD = $v; $x = $a ?? $v; $y = $b ?? $v; $x->n = 9; $number = $y->n; if ($number !== 7) { return 1; } $out PAYLOAD = $b; $number = $out->n; if ($number !== 7) { return 2; } return 0;',
		'lazy_joins' => 'function source(bool $present, int &$calls): ?PAYLOAD { $calls++; if ($present) { $v PAYLOAD = new C(); return $v; } return null; } function fallback(int &$calls): PAYLOAD { $calls = $calls + 10; $v PAYLOAD = new C(); return $v; } $n = 0; $a = source(true, $n) ?: fallback($n); if ($n !== 1) { return 1; } $b = source(false, $n) ?: fallback($n); if ($n !== 12) { return 2; } $c = source(true, $n) ?? fallback($n); if ($n !== 13) { return 3; } $d = source(false, $n) ?? fallback($n); if ($n !== 24) { return 4; } $e = true ? $a : source(false, $n); if ($n !== 24) { return 5; } return 0;',
		'boundaries' => 'function wrap(PAYLOAD $v): ?PAYLOAD { return $v; } function copy(?PAYLOAD $v): ?PAYLOAD { return $v; } function extract(?PAYLOAD $v): PAYLOAD { return $v; } function read(PAYLOAD $v): int { return $v->n; } $v PAYLOAD = new C(); $a = copy(wrap($v)); $b PAYLOAD = extract($a); $n = read($a); if ($n !== 7) { return 1; } $n = $b->n; if ($n !== 7) { return 2; } return 0;',
		'fields' => 'class Holder { ?PAYLOAD $v = null; } $h = new Holder(); $v PAYLOAD = new C(); $h->v = $v; $a ?PAYLOAD = $h->v; $h->v = null; $out PAYLOAD = $a; $n = $out->n; if ($n !== 7) { return 1; } return 0;',
		'empty_failure' => '$a ?PAYLOAD = null; $out PAYLOAD = $a; return 1;',
	] as $name => $program) {
		$output = nullable_records_source($kind . ' C { int $n = 7; } ' . str_replace('PAYLOAD', $type, $program));
		if ($name === 'joins') {
			foreach (['x', 'y', 'z', 'e', 'f'] as $local) {
				if (!str_contains($output, 'auto local_' . $local . ' = ([&]() -> scpp::nullable<')) {
					throw new \LogicException('Nullable join lost its wrapper result');
				}
			}
		}
		if ($name === 'empty_failure') {
			foreach (Model::$cpp_files as $artifact) {
				if ($artifact->identity === 'entry') {
					$instrumented = str_replace('int main()', 'int main() try', $artifact->text)
						. 'catch (const scpp::runtime_error &e) { return e.code() == "invalid_nullable_unwrap_empty" ? 0 : 2; }' . "\n";
					$output = str_replace($artifact->text, $instrumented, $output);
				}
			}
		}
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => 0];
		}
	}
	$output = nullable_records_source($kind . ' C { static int $count = 0; function __destruct() { self::$count = self::$count + 1; } } '
		. str_replace('PAYLOAD', $type, '$v PAYLOAD = new C(); $a ?PAYLOAD = $v; C::$count = 0; $a = null; if (C::$count !== 1) { return 1; } $a = null; if (C::$count !== 1) { return 2; } return 0;'));
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $kind . '_reset_cleanup.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 0];
	}
	// Empty state is independent of payload copy/default construction; references do not copy.
	$output = nullable_records_source($kind . ' C { unique<int> $owned; function __construct(int $n) {} } '
		. str_replace('PAYLOAD', $type, 'function reset(?PAYLOAD &$v): void { $v = null; } $a ?PAYLOAD; $b ?PAYLOAD = null; reset($a); $b = null; if ($a !== null) { return 1; } return 0;'));
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $kind . '_noncopyable_empty.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 0];
	}
	foreach ([
		'$a ?PAYLOAD; $b = $a;',
		'$v PAYLOAD; $a ?PAYLOAD = $v;',
		'$a ?PAYLOAD; $v PAYLOAD = $a;',
		'struct Holder { ?PAYLOAD $a; } $h Holder; $copy = $h;',
		'$a ?PAYLOAD; $b ?PAYLOAD; $out = true ? $a : $b;',
		'$a ?PAYLOAD; $b ?PAYLOAD; $out = $a ?: $b;',
		'$a ?PAYLOAD; $b PAYLOAD; $out = $a ?? $b;',
	] as $program) {
		try { nullable_records_source($kind . ' C { unique<int> $owned; } ' . str_replace('PAYLOAD', $type, $program)); throw new \LogicException('Noncopyable payload copied'); }
		catch (\RuntimeException $expected) {
			if (!str_contains($expected->getMessage(), 'copyable_value')) { throw $expected; }
		}
	}
}
foreach ([
	'struct C { int $n; } $a ?C; $n = $a->n;',
	'struct C {} struct D {} $v D; $a ?C = $v;',
	'struct C {} $a ?C; $b = empty($a);',
	'struct C {} struct D {} $a ?C; $b D; $out = $a ?? $b;',
	'struct C {} struct D {} $a ?C; $b D; $out = true ? $a : $b;',
	'struct C {} $a ?C; $b ?C; $out = $a ?? $b;',
	'struct C {} $a ?C; $out = true ? $a : null;',
] as $source) {
	try { nullable_records_source($source); throw new \LogicException('Deferred nullable operation accepted'); }
	catch (\RuntimeException $expected) {}
}

// Copy permission observes payload declarations, including changes under an unchanged wrapper identity.
$directory = sys_get_temp_dir() . '/nullable-records-' . bin2hex(random_bytes(6));
mkdir($directory);
$types_path = $directory . '/types.phs';
$main_path = $directory . '/main.phs';
try {
	$declaration = 'struct C { int $n = 7; } struct Holder { ?C $v; }';
	file_put_contents($types_path, $declaration);
	file_put_contents($main_path, '$a ?C; $b = true ? $a : $a; $h Holder; $copy = $h; return 0;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$before = CPP_Output::encode(Model::$cpp_files);
	$compiler->update_cpp([]);
	if (CPP_Output::encode(Model::$cpp_files) !== $before) { throw new \LogicException('No-op changed nullable output'); }
	file_put_contents($types_path, 'struct C { unique<int> $owned; } struct Holder { ?C $v; }');
	foreach ([[$types_path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Nullable retained stale copy permission'); }
		catch (\RuntimeException $error) {
			if (!str_contains($error->getMessage(), 'copyable_value')) { throw $error; }
		}
		if (count(Model::$cpp_files) !== 0) { throw new \LogicException('Failed nullable preparation published output'); }
	}
	file_put_contents($types_path, $declaration);
	$compiler->update_cpp([$types_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $before) { throw new \LogicException('Nullable repair changed output'); }
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $before) { throw new \LogicException('Preparation reset changed nullable output'); }
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $before) { throw new \LogicException('Fresh nullable build differs'); }
}
finally {
	unlink($types_path);
	unlink($main_path);
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "nullable records: PASS\n";
