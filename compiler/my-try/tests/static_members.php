<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function static_members_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/static_members', '/static_members', '/static_members');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
	return native_record_export();
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	foreach ([
		'call' => ['public static function f(int $n = 9): int { return $n; }', 'return C::f();', 9],
		'private_call' => ['private static function f(): int { return 9; } static public function g(): int { return self::f(); }', 'return C::g();', 9],
		'constant' => ['public const N = 9; function f(): int { return self::N; }', '$c = new C(); return $c->f();', 9],
		'const_dependency' => ['const A = self::B; private const B = 9;', 'return C::A;', 9],
		'property' => ['public static int $n = 7; static function f(int $n): void { self::$n = $n; }', 'C::f(9); return C::$n;', 9],
		'default' => ['static int $n; static function f(): int { return self::$n; }', 'return C::f();', 0],
		'const_default' => ['private const N = 9; public static int $n = self::N;', 'return C::$n;', 9],
		'instance_default' => ['const N = 9; int $n = self::N;', '$c = new C(); return $c->n;', 9],
		'data_exclusion' => ['static int $n = 7; int $v; static function f(): int { return self::$n; }', '$c C = {v:9}; $n = C::f(); if ($n !== 7) { return 1; } return $c->v;', 9],
		'three_names' => ['const same = 9; static int $same = 7; static function same(): int { return self::same; }', '$a = C::same(); $b = C::$same; return $a - $b;', 2],
		'string' => ['static string $name = "abc"; static function f(string $s): int { return 3; }', 'return C::f(C::$name);', 3],
		'nullable' => ['static ?int $n = null;', 'C::$n = 9; $n = C::$n; return $n ?? 1;', 9],
		'container_default' => ['static vector<int> $values;', '$v = C::$values; $v[] = 9; C::$values = $v; $copy = C::$values; return $copy[0];', 9],
		'constant_sign' => ['const N = -9; static int $n = -self::N;', 'return C::$n;', 9],
		'namespace_constant' => ['const N = 1; static function f(): int { return N; }', 'const N = 9; return C::f();', 9],
		'installed_constant' => ['static function f(): int { $e = error::$no_error; if ($e) { return 1; } return 9; }', 'return C::f();', 9],
		'order' => ['static function f(int $a, int $b): int { return $a * 10 + $b; }', 'function bump(int &$n): int { $n += 1; return $n; } $n = 0; return C::f(bump($n), bump($n));', 12],
	] as $name => [$members, $program, $expected]) {
		$output = static_members_source('data ' . $kind . ' C { ' . $members . ' } ' . $program);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
}
foreach ([
	['class C { int $n; static function f(): int { return $this->n; } } return C::f();', '$this requires'],
	['class C { function f(): int { return 1; } } return C::f();', 'Static methods require'],
	['class C { static function f(): int { return 1; } } $c = new C(); return $c->f();', 'Static methods require'],
	['class C { private const N = 9; } return C::N;', 'Private member'],
	['class C { private static int $n; } return C::$n;', 'Private member'],
	['class C { const A = self::B; const B = self::A; }', 'cycl'],
	['class C { const N = 1; const N = 2; }', 'duplicate constant'],
	['class C { public private static int $n; }', 'visibility'],
	['class C { static static function f(): void {} }', 'Duplicate static'],
	['class C { public public function __construct() {} }', 'visibility'],
	['class C { static int $n; } $a =& C::$n;', 'borrow'],
	['class C { static vector<int> $n = [1]; }', 'Static properties require'],
	['class C { const N = 9; } C::N = 1;', 'not a constant'],
	['class C { int $n; } return C::$n;', 'static property'],
	['data class C { static int $n; } $c C = {n:1};', 'unknown record'],
	['class C { static int $n; function __construct(set:$n) {} }', 'existing instance property'],
] as [$source, $message]) {
	$failed = false;
	try { static_members_source($source); }
	catch (\RuntimeException $error) {
		if (!str_contains(strtolower($error->getMessage()), strtolower($message))) { throw $error; }
		$failed = true;
	}
	if (!$failed) { throw new \LogicException('Expected static-member rejection: ' . $message); }
}
$directory = sys_get_temp_dir() . '/static-members-' . bin2hex(random_bytes(6));
mkdir($directory);
$type_path = $directory . '/types.phs';
$entry_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; class C { public const N = 7; public static int $n = self::N; public static function get(): int { return self::$n; } }';
	$entry = 'use Types\\C; C::$n = C::N; return C::get();';
	file_put_contents($type_path, $declaration);
	file_put_contents($entry_path, $entry);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	foreach ([
		str_replace('public const', 'private const', $declaration),
		str_replace('public static int', 'private static int', $declaration),
		str_replace('public static function', 'private static function', $declaration),
		str_replace('static int', 'int', $declaration),
		str_replace('static function', 'function', $declaration),
		str_replace('const N', 'const Missing', $declaration),
	] as $invalid) {
		file_put_contents($type_path, $invalid);
		foreach ([[$type_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid static member survived update/retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($type_path, $declaration);
		$compiler->update_cpp([$type_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Static-member repair changed output'); }
	}
	file_put_contents($type_path, str_replace('N = 7', 'N = 9', $declaration));
	$compiler->update_cpp([$type_path]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	if ($incremental === $original) { throw new \LogicException('Constant edit did not invalidate consumers'); }
	Compiler_Lifecycle::reset();
	$fresh = new Compiler(new cpp_lowering_options(false));
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Static-member fresh/incremental mismatch'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Static-member reset changed output'); }
	if (isset($argv[1])) {
		$path = $argv[1] . '/cross_file.cpp';
		file_put_contents($path, CPP_Probe::text(Model::$cpp_files));
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
	file_put_contents($type_path, 'namespace Types; class C {}');
	file_put_contents($entry_path, 'return 0;');
	$fresh->update_cpp([$type_path, $entry_path]);
	file_put_contents($type_path, str_replace('N = 7', 'N = 9', $declaration));
	file_put_contents($entry_path, $entry);
	$fresh->update_cpp([$type_path, $entry_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Static-member retirement/readdition changed output'); }
}
finally {
	unlink($type_path);
	unlink($entry_path);
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "static members: PASS\n";
