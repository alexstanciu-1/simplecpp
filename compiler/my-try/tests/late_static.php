<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function late_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/late-static', '/late-static', '/late-static');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
	return native_record_export();
}
$results = [];
foreach ([
	'dispatch' => ['class B { static function pick(): int { return 2; } static function run(): int { return static::pick(); } } class C extends B { static function pick(): int { return 9; } } return C::run();', 9],
	'forward' => ['class B { static function pick(): int { return 2; } static function run(): int { return self::relay(); } static function relay(): int { return static::pick(); } } class C extends B { static function pick(): int { return 9; } static function parent_run(): int { return parent::run(); } } return C::parent_run();', 9],
	'explicit_reset' => ['class B { static function pick(): int { return 2; } static function run(): int { return static::pick(); } } class C extends B { static function pick(): int { return 9; } static function reset(): int { return B::run(); } } return C::reset();', 2],
	'storage' => ['class B { static int $n; const N = 2; static function bump(): int { static::$n += static::N; return static::$n; } } class C extends B {} class D extends B {} $a = C::bump(); $b = D::bump(); return B::$n;', 4],
	'abstract_factory' => ['abstract class B { static function make(): B { return new static(); } abstract function score(): int; } class C extends B { function score(): int { return 9; } } $c = C::make(); return $c->score();', 9],
	'constructor' => ['abstract class B { static function make(int $n): B { return new static($n); } abstract function score(): int; } class C extends B { private int $n; function __construct(set:$n) {} function score(): int { return $this->n; } } $c = C::make(9); return $c->score();', 9],
	'struct_dispatch' => ['struct B { static function pick(): int { return 2; } static function run(): int { return static::pick(); } } struct C extends B { static function pick(): int { return 9; } } return C::run();', 9],
	'unused_body' => ['class C { static function unused(): int { return unknown_function(); } } return 9;', 9],
	'declared_qualifier' => ['class B { static function pick(): int { return 2; } static function run(): int { return static::pick(); } } class C extends B { static function pick(): int { return 9; } } $b B = new C(); return $b::run();', 2],
	'two_contexts' => ['class B { static function pick(): int { return 2; } static function run(): int { return static::pick(); } } class C extends B { static function pick(): int { return 7; } } $a = B::run(); $b = C::run(); return $a + $b;', 9],
	'recursion' => ['class B { static function pick(): int { return 2; } static function run(int $n): int { if ($n === 0) { return static::pick(); } return self::run($n - 1); } } class C extends B { static function pick(): int { return 9; } } return C::run(3);', 9],
] as $name => [$source, $expected]) {
	$cpp = late_source($source);
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp'; file_put_contents($path, $cpp);
		$results[] = ['path' => $path, 'exit_code' => $expected];
	}
}
foreach ([
	['class C { static function unused(): UnknownType { return 1; } }', 'Unknown'],
	['class C { static function bad(): int { return unknown_function(); } } return C::bad();', 'unknown_function'],
	['abstract class B { static function make(): B { return new static(); } } $b = B::make();', 'abstract'],
	['struct B { static function make(): B { return new static(); } } $b = B::make();', 'shared class'],
	['class C { function run(): int { return static::pick(); } static function pick(): int { return 1; } }', 'called-type'],
] as [$source, $diagnostic]) {
	try { late_source($source); throw new \LogicException('Expected late-static rejection'); }
	catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), $diagnostic)) { throw $error; } }
}
$directory = sys_get_temp_dir() . '/late-static-' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs'; $child_path = $directory . '/child.phs'; $main_path = $directory . '/main.phs';
try {
	file_put_contents($base_path, 'class B { static function pick(): int { return 2; } static function run(): int { return static::pick(); } }');
	file_put_contents($child_path, 'class C extends B { static function pick(): int { return 9; } }');
	file_put_contents($main_path, 'return C::run();');
	Compiler_Lifecycle::reset(); $compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$initial = CPP_Output::encode(Model::$cpp_files);
	$compiler->update_cpp([]);
	if ($initial !== CPP_Output::encode(Model::$cpp_files)) { throw new \LogicException('No-op late-static output changed'); }
	// Removing the last demand must also remove obsolete implementations and their diagnostics.
	file_put_contents($child_path, 'class C extends B { static function pick(): int { return unknown_function(); } }');
	file_put_contents($main_path, 'return 9;');
	$compiler->update_cpp([$child_path, $main_path]);
	if (str_contains(CPP_Probe::text(Model::$cpp_files), 'implementation_')) { throw new \LogicException('Unused implementations survived demand removal'); }
	file_put_contents($main_path, 'return C::run();');
	foreach ([[$main_path], []] as $changed) {
		try { $compiler->update_cpp($changed); throw new \LogicException('Demanded invalid body accepted'); }
		catch (\RuntimeException $expected) { if (!str_contains($expected->getMessage(), 'unknown_function')) { throw $expected; } }
	}
	file_put_contents($child_path, 'class C extends B { static function pick(): int { return 8; } }');
	$compiler->update_cpp([$child_path]);
	$repaired = CPP_Output::encode(Model::$cpp_files);
	$cpp = native_record_export();
	if (isset($argv[1])) {
		$path = $argv[1] . '/cross_file_repair.cpp'; file_put_contents($path, $cpp);
		$results[] = ['path' => $path, 'exit_code' => 8];
	}
	Compiler_Lifecycle::reset_preparation(); $compiler->prepare(); $compiler->cpp();
	if ($repaired !== CPP_Output::encode(Model::$cpp_files)) { throw new \LogicException('Preparation reset changed late-static output'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if ($repaired !== CPP_Output::encode(Model::$cpp_files)) { throw new \LogicException('Fresh and incremental late-static output differ'); }
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "Late static: selection, forwarding, shared factories, storage and deferred bodies passed\n";
