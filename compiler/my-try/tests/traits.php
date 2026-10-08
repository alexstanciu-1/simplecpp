<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function traits_check(bool $value, string $message): void { if (!$value) { throw new \LogicException($message); } }
function traits_compile(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/traits', '/traits', '/traits');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}
$results = [];
function traits_export(string $name, string $cpp, int $expected): void
{
	global $argv, $results;
	if (!isset($argv[1])) { return; }
	$path = $argv[1] . '/' . $name . '.cpp'; file_put_contents($path, $cpp);
	$results[] = ['path' => $path, 'exit_code' => $expected];
}
$base = 'trait Counter { private int $count = 4; public function next(): int { $this->count += 1; return $this->count; } } ';
foreach ([
	'class' => [$base . 'class A { use Counter; } $a = new A(); return $a->next();', 5],
	'struct' => [$base . 'struct A { use Counter; } $a A; return $a->next();', 5],
	'two_consumers' => [$base . 'class A { use Counter; } struct B { use Counter; } $a = new A(); $b B; $x = $a->next(); $y = $b->next(); return $x + $y;', 10],
	'interface' => [$base . 'interface I { function next(): int; } class A implements I { use Counter; } $i I = new A(); return $i->next();', 5],
	'inherited' => [$base . 'class A { use Counter; } class B extends A {} $b = new B(); return $b->next();', 5],
	'reference' => ['trait T { function &pick(int &$n): int { return $n; } } class A { use T; } $a = new A(); $n = 1; $r =& $a->pick($n); $r = 9; return $n;', 9],
	'data' => ['trait T { public int $number = 4; } data struct A { use T; } $a A = {number: 9}; return $a->number;', 9],
] as $name => [$source, $expected]) { traits_export($name, traits_compile($source), $expected); }
foreach (['class', 'struct'] as $kind) {
	$counter = 'trait T { public static int $count = 0; public static function next(): int { self::$count += 1; return self::$count; } } ';
	foreach ([
		'static_consumers' => [$counter . $kind . ' A { use T; } ' . $kind . ' B { use T; } A::next(); A::next(); B::next(); $a = A::$count; $b = B::$count; return $a * 10 + $b;', 21],
		'static_inherited' => [$counter . $kind . ' A { use T; } ' . $kind . ' B extends A {} B::next(); A::next(); return B::$count;', 2],
		'static_dispatch' => ['trait T { static function run(): int { return static::pick(); } static function pick(): int { return 2; } } ' . $kind . ' A { use T; } ' . $kind . ' B extends A { static function pick(): int { return 9; } } return B::run();', 9],
		'static_private' => ['trait T { private const N = 7; private static int $n = self::N; public static function read(): int { return self::$n; } } ' . $kind . ' A { use T; } return A::read();', 7],
		'constants' => ['trait T { const N = 9; const M = self::N; } ' . $kind . ' A { use T; } ' . $kind . ' B extends A {} return B::M;', 9],
		'constant_categories' => ['trait T { const n = 7; static int $n = 2; static function n(): int { return self::n; } } ' . $kind . ' A { use T; } $a = A::n(); $b = A::$n; return $a + $b;', 9],
		'static_reference' => ['trait T { static function &pick(int &$n): int { return $n; } } ' . $kind . ' A { use T; } $n = 1; $a =& A::pick($n); $a = 9; return $n;', 9],
		'static_variadic' => ['trait T { static function sum(int ...$v): int { $n = 0; foreach ($v as $x) { $n += $x; } return $n; } } ' . $kind . ' A { use T; } return A::sum(4,5);', 9],
		'lexical_constant' => ['namespace origin; const N = 9; trait T { const VALUE = N; static function read(): int { return self::VALUE; } } namespace app; const N = 1; ' . $kind . ' A { use \\origin\\T; } return A::read();', 9],
	] as $name => [$source, $expected]) { traits_export($kind . '_' . $name, traits_compile($source), $expected); }
}
foreach ([
	[$base . 'class A { use Counter, Counter; }', 'Duplicate trait use'],
	[$base . 'class A { use Counter; int $count; }', 'Duplicate property'],
	[$base . 'class A { use Counter; function next(): int { return 0; } }', 'Duplicate method'],
	['class Other {} class A { use Other; }', 'trait target'],
	['class A { use int; }', 'trait target'],
	['class A { use Unknown; }', 'unambiguous target'],
	['trait T { use U; } trait U {} class A { use T; }', 'direct properties'],
	['trait T { const N = 1; } class A { use T; const N = 2; }', 'duplicate constant'],
	['trait T { const N = 1; } trait U { const N = 2; } class A { use T,U; }', 'duplicate constant'],
	['trait T { const N = 1; } class A { const N = 2; } class B extends A { use T; }', 'redeclaration'],
	['trait T { static int $n; } class A { use T; } class B extends A { use T; }', 'redeclaration'],
	['trait T { private const N = 1; } class A { use T; } return A::N;', 'Private'],
	['trait T { private static int $n; } class A { use T; } return A::$n;', 'Private'],
	['trait T { static function f(): int { return $this->n; } } class A { use T; } return A::f();', '$this requires'],
	['trait T { const N = unknown(); } class A { use T; }', 'constant'],
] as [$source, $expected]) {
	try { traits_compile($source); throw new \LogicException('Invalid trait composition accepted'); }
	catch (\RuntimeException $error) { traits_check(str_contains($error->getMessage(), $expected), $error->getMessage()); }
}
$directory = sys_get_temp_dir() . '/traits-lifecycle-' . bin2hex(random_bytes(6)); mkdir($directory);
$origin_path = $directory . '/trait.phs'; $consumer_path = $directory . '/consumer.phs'; $main_path = $directory . '/main.phs';
try {
	$origin = 'namespace feature; trait T { private int $number = 4; public function read(): int { return $this->number; } }';
	$consumer = 'namespace app; use feature\\T as Shared; class A { use Shared; }';
	file_put_contents($origin_path, $origin); file_put_contents($consumer_path, $consumer);
	file_put_contents($main_path, '$a = new \\app\\A(); return $a->read();');
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$record = null;
	foreach (Model::collected_files() as $source) { foreach ($source->entries as $entry) { if ($entry instanceof collected_record && $entry->qualified_name() === 'app\\A') { $record = $entry; } } }
	traits_check($record !== null, 'Missing consumer');
	$instance = $record->trait_instances[0]; $initial = CPP_Output::encode(Model::$cpp_files);
	$compiler->update_cpp([$origin_path, $consumer_path]);
	traits_check($record->trait_instances[0] === $instance && CPP_Output::encode(Model::$cpp_files) === $initial, 'No-op replaced instance/output');
	file_put_contents($origin_path, 'namespace feature; trait T { function broken(');
	try { $compiler->update_cpp([$origin_path]); throw new \LogicException('Malformed trait accepted'); }
	catch (\RuntimeException $expected) {}
	traits_check(!$instance->collection->deleted, 'Failed parse join retired a live instance');
	file_put_contents($origin_path, $origin); $compiler->update_cpp([$origin_path]);
	traits_check(CPP_Output::encode(Model::$cpp_files) === $initial, 'Parse repair differs');
	Compiler_Lifecycle::reset_preparation(); $compiler->prepare(true); $compiler->cpp();
	traits_check(CPP_Output::encode(Model::$cpp_files) === $initial, 'Preparation reset differs');
	file_put_contents($origin_path, str_replace('= 4', '= 8', $origin)); $compiler->update_cpp([$origin_path]);
	traits_check($instance->collection->deleted && $instance->collection->entries->is_empty(), 'Old private collection survived origin edit');
	traits_check(count($instance->collection->preparation_changes) === 0, 'Retired private source retained work handoff links');
	foreach (Model::$cpp_output_program->fragments as $owner) {
		traits_check($owner->source !== $instance->collection, 'Retired instance retained a backend fragment');
	}
	traits_export('edited_default', native_record_export(), 8); $edited = CPP_Output::encode(Model::$cpp_files);
	foreach ([[$consumer_path, 'namespace app; class A {}', $consumer], [$origin_path, '', str_replace('= 4', '= 8', $origin)]] as [$path, $invalid, $valid]) {
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Broken use/member survived retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($path, $valid); $compiler->update_cpp([$path]);
		traits_check(CPP_Output::encode(Model::$cpp_files) === $edited, 'Repair differs from prior valid output');
	}
	Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	traits_check(CPP_Output::encode(Model::$cpp_files) === $edited, 'Fresh composition differs');
	traits_export('fresh_repair', native_record_export(), 8);
	// Static working members and demanded implementations retire together on trait edits.
	$origin = 'namespace feature; trait T { private const N = 4; private static int $number = self::N; public static function read(): int { self::$number += 1; return self::$number; } }';
	file_put_contents($origin_path, $origin);
	file_put_contents($main_path, 'return \\app\\A::read();');
	$compiler->update_cpp([$origin_path, $main_path]);
	$initial = CPP_Output::encode(Model::$cpp_files); $compiler->update_cpp([]);
	traits_check(CPP_Output::encode(Model::$cpp_files) === $initial, 'Static trait no-op changed output');
	foreach ([str_replace('N = 4', 'N = "wrong"', $origin), str_replace('static function', 'function', $origin)] as $invalid) {
		file_put_contents($origin_path, $invalid);
		foreach ([[$origin_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid static trait survived retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($origin_path, $origin); $compiler->update_cpp([$origin_path]);
		traits_check(CPP_Output::encode(Model::$cpp_files) === $initial, 'Static trait repair changed output');
	}
	file_put_contents($origin_path, str_replace('N = 4', 'N = 8', $origin)); $compiler->update_cpp([$origin_path]);
	$edited = CPP_Output::encode(Model::$cpp_files);
	traits_check($edited !== $initial, 'Contributed constant edit was lost');
	traits_export('static_edited_constant', native_record_export(), 9);
	Compiler_Lifecycle::reset_preparation(); $compiler->prepare(); $compiler->cpp();
	traits_check(CPP_Output::encode(Model::$cpp_files) === $edited, 'Static trait reset differs');
	Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	traits_check(CPP_Output::encode(Model::$cpp_files) === $edited, 'Static trait fresh/incremental mismatch');
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$origin_path, $consumer_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Trait composition, lifecycle, recovery and output checks passed\n";
