<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function qualified_static_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/qualified_static', '/qualified_static', '/qualified_static');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	foreach ([
		'local' => '$c = new C(); return $c::f();',
		'absent_shared' => '$c shared<C>; return $c::f();',
		'absent_unique' => '$c unique<C>; return $c::f();',
		'absent_weak' => '$c weak<C>; return $c::f();',
		'inline' => '$c value<C>; return $c::f();',
		'readonly' => 'function read(const C &$c): int { return $c::f(); } $c = new C(); return read($c);',
		'order' => 'function produce(int &$n): C { $n = $n * 10 + 4; return new C(); } function arg(int &$n): int { $n += 5; return $n; } $n = 0; return produce($n)::f(arg($n));',
		'projected' => 'struct Box { C $c; } $box = new Box(); return $box->c::f();',
		'reference' => '$c = new C(); $n = 1; $a =& $c::ref($n); $copy = $c::ref($n); $a = 8; return $copy + $n;',
		'pack' => '$c = new C(); return $c::sum(4, 5);',
		'this' => '$c = new C(); return $c->go();',
	] as $name => $program) {
		// Avoid redundant explicit storage modifiers for each declaration's default.
		if ($kind === 'class') { $program = str_replace('shared<C>', 'C', $program); }
		else { $program = str_replace('value<C>', 'C', $program); }
		$output = qualified_static_source($kind . ' C { static function f(int $n = 9): int { return $n; } '
			. 'static function &ref(int &$n): int { return $n; } '
			. 'static function sum(int ...$v): int { $n = 0; foreach ($v as $x) { $n += $x; } return $n; } '
			. 'private static function secret(): int { return 9; } function go(): int { return $this::secret(); } } ' . $program);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => 9];
		}
	}
}
foreach ([
	'noncopyable' => 'struct C { unique<int> $owned; static function f(): int { return 9; } } $c C; return $c::f();',
	'temporary_release' => 'class C { static function f(int $n): int { return $n; } } function produce(weak<C> &$w): C { $c = new C(); $w = weak($c); return $c; } function check(weak<C> &$w): int { if (lock($w)) { return 1; } return 9; } $w weak<C>; return produce($w)::f(check($w));',
] as $name => $source) {
	$output = qualified_static_source($source);
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
}
foreach ([
	'class C { function f(): int { return 1; } } $c = new C(); return $c::f();',
	'class C { private static function f(): int { return 1; } } $c = new C(); return $c::f();',
	'$c dynamic = {}; return $c::f();',
	'$c mixed = []; return $c::f();',
	'$c = 1; return $c::f();',
	'interface I { static function f(): int; } function produce(): I { } return produce()::f();',
	'class C { static int $n; } $c = new C(); return $c::$n;',
] as $source) {
	$failed = false;
	try { qualified_static_source($source); }
	catch (\RuntimeException $expected) { $failed = true; }
	if (!$failed) { throw new \LogicException('Invalid qualified call accepted: ' . $source); }
}

$directory = sys_get_temp_dir() . '/qualified-static-' . bin2hex(random_bytes(6));
mkdir($directory);
$type_path = $directory . '/types.phs';
$entry_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; class C { static function f(): int { return 9; } }';
	file_put_contents($type_path, $declaration);
	file_put_contents($entry_path, 'use Types\\C; $c C; return $c::f();');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	foreach ([str_replace('static ', '', $declaration), str_replace('static ', 'private static ', $declaration)] as $invalid) {
		file_put_contents($type_path, $invalid);
		foreach ([[$type_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid qualified-call edit accepted'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($type_path, $declaration);
		$compiler->update_cpp([$type_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Qualified-call repair changed output'); }
	}
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Qualified-call fresh mismatch'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Qualified-call reset mismatch'); }
	if (isset($argv[1])) {
		$path = $argv[1] . '/incremental.cpp';
		file_put_contents($path, CPP_Probe::text(Model::$cpp_files));
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
}
finally {
	unlink($type_path);
	unlink($entry_path);
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "qualified static calls: PASS\n";
