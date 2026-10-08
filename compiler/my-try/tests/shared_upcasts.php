<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function upcast_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/upcasts', '/upcasts', '/upcasts');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}
$results = [];
function upcast_export(string $name, string $cpp, int $expected): void
{
	global $argv, $results;
	if (!isset($argv[1])) { return; }
	$path = $argv[1] . '/' . $name . '.cpp'; file_put_contents($path, $cpp);
	$results[] = ['path' => $path, 'exit_code' => $expected];
}
$classes = 'class B { int $n = 1; } class M extends B {} class C extends M {} ';
foreach ([
	'local_identity' => [$classes . '$c = new C(); $b B = $c; $b->n = 9; return $c->n;', 9],
	'assignment' => [$classes . '$c = new C(); $b B; $b = $c; $b->n = 9; return $c->n;', 9],
	'argument' => [$classes . 'function set_value(B $b): void { $b->n = 9; } $c = new C(); set_value($c); return $c->n;', 9],
	'return' => [$classes . 'function produce(): B { return new C(); } $b = produce(); return $b->n;', 1],
	'empty' => [$classes . '$c C; $b B = $c; return isset($b) ? 1 : 9;', 9],
	'property' => [$classes . 'class Holder { B $b; } $h = new Holder(); $c = new C(); $h->b = $c; $h->b->n = 9; return $c->n;', 9],
	'method' => [$classes . 'class Ops { function pass(C $c): B { return $c; } static function read(B $b): int { return $b->n; } } $o = new Ops(); $c = new C(); $b = $o->pass($c); $b->n = 9; return Ops::read($c);', 9],
	'shared_struct' => ['struct B { int $n = 1; } struct C extends B {} $c shared<C> = new C(); $b shared<B> = $c; $b->n = 9; return $c->n;', 9],
	'data_input' => [$classes . 'data class Holder { B $b; } $c = new C(); $h Holder = {b: $c}; $h->b->n = 9; return $c->n;', 9],
	'element_inputs' => [$classes . '$c = new C(); $bs vector<B> = [$c]; $bs[] = $c; $bs[0] = $c; $b = $bs[1]; $b->n = 9; return $c->n;', 9],
	'iteration' => [$classes . '$c = new C(); $cs vector<C> = [$c]; $b B; foreach ($cs as $b) { $b->n = 9; } return $c->n;', 9],
	'virtual' => ['abstract class B { abstract function read(): int; } class C extends B { function read(): int { return 9; } } $c = new C(); $b B = $c; return $b->read();', 9],
	'cleanup' => ['class Log { static int $n = 0; } class B { function __destruct() { Log::$n += 1; } } class C extends B { function __destruct() { Log::$n += 8; } } $c = new C(); $b B = $c; $c = null; if (Log::$n !== 0) { return 101; } $b = null; return Log::$n;', 9],
] as $name => [$source, $expected]) { upcast_export($name, upcast_source($source), $expected); }
// A pure conversion caller must not read settled ancestry without registering dependencies.
$registry = Model::$type_catalog->registry();
$source_type = $registry->canonical(Source_Types::definition(Model::$global_scope->source_types_named('C')[0]));
$target_type = $registry->canonical(Source_Types::definition(Model::$global_scope->source_types_named('B')[0]));
try {
	Conversion_Preparation::decide($registry->use($source_type->type_id()), $registry->use($target_type->type_id()), conversion_context::argument);
	throw new \LogicException('Upcast bypassed dependency-aware query');
}
catch (\RuntimeException $expected) {}
// Weak boundaries reuse the ancestry decision without acquiring a strong owner.
foreach (['class', 'struct'] as $kind) {
	$records = $kind . ' B { int $n = 1; } ' . $kind . ' M extends B {} ' . $kind . ' C extends M {} ';
	$owner = $kind === 'class' ? '$c = new C(); ' : '$c shared<C> = new C(); ';
	foreach ([
		'identity' => [$owner . '$w = weak($c); $b weak<B> = $w; $held = lock($b); $held->n = 9; return $c->n;', 9],
		'assignment' => [$owner . '$b weak<B>; $b = weak($c); $held = lock($b); return $held->n;', 1],
		'boundaries' => ['function convert(weak<C> $w): weak<B> { return $w; } function read(weak<B> $w): int { $b = lock($w); return $b->n; } ' . $owner . '$first = read(convert(weak($c))); $second = read(weak($c)); return $first + $second;', 2],
		'empty' => ['$w weak<C>; $b weak<B> = $w; return isset(lock($b)) ? 1 : 9;', 9],
		'expired' => [$owner . '$w = weak($c); $c = null; $b weak<B> = $w; return isset(lock($b)) ? 1 : 9;', 9],
		'no_retention' => [$owner . '$w = weak($c); $b weak<B> = $w; $c = null; return isset(lock($b)) ? 1 : 9;', 9],
		'lock_retains' => [$owner . '$w = weak($c); $b weak<B> = $w; $held = lock($b); $c = null; if (!isset(lock($w))) { return 101; } $held = null; return isset(lock($b)) ? 1 : 9;', 9],
		'property_data_elements' => ['data class Holder { weak<B> $b; } ' . $owner . '$w = weak($c); $h Holder = {b: $w}; $h->b = $w; $bs vector<weak<B>> = [$w]; $bs[] = $w; $bs[0] = $w; $held = lock($h->b); $held->n = 9; $other = lock($bs[1]); return $other->n;', 9],
		'iteration' => [$owner . '$ws vector<weak<C>> = [weak($c)]; $b weak<B>; foreach ($ws as $b) { $held = lock($b); $held->n = 9; } return $c->n;', 9],
	] as $name => [$body, $expected]) {
		upcast_export('weak_' . $kind . '_' . $name, upcast_source($records . $body), $expected);
	}
	foreach ([
		'$b weak<B>; $c weak<C> = $b;',
		$kind . ' Other {} $c weak<C>; $o weak<Other> = $c;',
		'function replace(weak<B> &$b): void {} $c weak<C>; replace($c);',
		'function inspect(const weak<B> &$b): void {} $c weak<C>; inspect($c);',
		'$c weak<C>; $b weak<B> =& $c;',
		$owner . '$b weak<B> = $c;',
		'$cs vector<weak<C>> = []; $bs vector<weak<B>> = $cs;',
		'interface I {} ' . $kind . ' Impl implements I {} $c weak<Impl>; $b weak<I> = $c;',
	] as $body) {
		try { upcast_source($records . $body); throw new \LogicException('Expected rejected weak boundary'); }
		catch (\RuntimeException $expected) {}
	}
}
foreach (['class', 'struct'] as $kind) {
	$records = 'abstract ' . $kind . ' B { int $n = 9; abstract function read(): int; } '
		. $kind . ' C extends B { function read(): int { return $this->n; } } ';
	foreach ([
		'local' => '$c unique<C> = new C(); $b unique<B> = move($c); if (isset($c)) { return 101; } return $b->n;',
		'assignment' => '$c unique<C> = new C(); $b unique<B>; $b = move($c); if (isset($c)) { return 101; } return $b->n;',
		'argument' => 'function read(unique<B> $b): int { return $b->n; } $c unique<C> = new C(); $n = read(move($c)); if (isset($c)) { return 101; } return $n;',
		'return' => 'function produce(): unique<B> { $c unique<C> = new C(); return move($c); } $b = produce(); return $b->n;',
		'call_result' => 'function produce(): unique<C> { $c unique<C> = new C(); return move($c); } $b unique<B> = produce(); return $b->n;',
		'empty' => '$c unique<C>; $b unique<B> = move($c); return isset($b) ? 101 : 9;',
		'alias' => '$c unique<C> = new C(); $alias =& $c; $b unique<B> = move($alias); if (isset($c)) { return 101; } return $b->n;',
	] as $name => $body) { upcast_export('unique_' . $kind . '_' . $name, upcast_source($records . $body), 9); }
	foreach ([
		'$c unique<C>; $b unique<B> = $c;',
		'function read(unique<B> &$b): void {} $c unique<C>; read($c);',
		'function read(const unique<B> &$b): void {} $c unique<C>; read($c);',
		'$b unique<B>; $c unique<C> = move($b);',
	] as $body) {
		try { upcast_source($records . $body); throw new \LogicException('Invalid unique upcast accepted'); }
		catch (\RuntimeException $expected) {}
	}
}
upcast_export('unique_runtime_guards', <<<'CPP'
#include "scpp/memory.hpp"
#include <type_traits>
struct UnsafeBase {};
struct UnsafeChild : UnsafeBase {};
static_assert(!std::is_constructible_v<scpp::unique_p<UnsafeBase>, scpp::unique_p<UnsafeChild>&&>);
static_assert(!std::is_assignable_v<scpp::unique_p<UnsafeBase>&, scpp::unique_p<UnsafeChild>&&>);
static_assert(std::is_constructible_v<scpp::unique_p<const UnsafeChild>, scpp::unique_p<UnsafeChild>&&>);
static int cleanup = 0;
struct SafeBase { virtual ~SafeBase() { cleanup += 1; } };
struct SafeChild : SafeBase { ~SafeChild() { cleanup += 8; } };
int main() {
    auto child = scpp::unique<SafeChild>();
    auto identity = child.get();
    scpp::unique_p<SafeBase> base{std::move(child)};
    if (child || base.get() != identity) return 101;
    base = scpp::unique<SafeChild>();
    if (cleanup != 9) return 102;
    base.reset();
    return cleanup == 18 ? 9 : 103;
}
CPP, 9);
upcast_export('unique_cleanup', upcast_source('class Log { static int $n = 0; } abstract class B { abstract function read(): int; function __destruct() { Log::$n += 1; } } class C extends B { function read(): int { return 9; } function __destruct() { Log::$n += 8; } } $c unique<C> = new C(); $b unique<B> = move($c); if (Log::$n !== 0) { return 101; } $b = null; return Log::$n;'), 9);
foreach ([
	$classes . '$b = new B(); $c C = $b;',
	$classes . 'class Other {} $c = new C(); $o Other = $c;',
	$classes . 'function replace(B &$b): void {} $c = new C(); replace($c);',
	$classes . 'function inspect(const B &$b): void {} $c = new C(); inspect($c);',
	$classes . '$c = new C(); $b B =& $c;',
	'struct B {} struct C extends B {} $c C; $b B = $c;',
	'struct B {} struct C extends B {} $c C; $b shared<B> = $c;',
	'struct B {} struct C extends B {} $c unique<C>; $b unique<B> = move($c);',
	'abstract class B {} class C extends B {} $c unique<C>; $b unique<B> = move($c);',
	$classes . '$cs vector<C> = []; $bs vector<B> = $cs;',
] as $source) {
	try { upcast_source($source); throw new \LogicException('Expected rejected non-shared-upcast boundary'); }
	catch (\RuntimeException $expected) {}
}

// Only the relationship changes; unchanged consumer bodies must lose their accepted conversion.
$directory = sys_get_temp_dir() . '/shared-upcasts-' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
$main_path = $directory . '/main.phs';
try {
	file_put_contents($base_path, 'class B {}');
	$child_text = 'class C extends B {}';
	file_put_contents($child_path, $child_text);
	foreach ([
		'assignment' => '$c C; $b B = $c; return isset($b) ? 1 : 9;',
		'argument' => 'function accept(B $b): int { return isset($b) ? 1 : 9; } $c C; return accept($c);',
		'return' => 'function convert(C $c): B { return $c; } $c C; $b = convert($c); return isset($b) ? 1 : 9;',
		'weak_assignment' => '$c weak<C>; $b weak<B> = $c; return isset(lock($b)) ? 1 : 9;',
		'weak_argument' => 'function accept(weak<B> $b): int { return isset(lock($b)) ? 1 : 9; } $c weak<C>; return accept($c);',
		'weak_return' => 'function convert(weak<C> $c): weak<B> { return $c; } $c weak<C>; $b = convert($c); return isset(lock($b)) ? 1 : 9;',
	] as $boundary => $main) {
		file_put_contents($main_path, $main);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
		$original = CPP_Output::encode(Model::$cpp_files);
		file_put_contents($child_path, 'class C {}');
		foreach ([[$child_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Stale upcast survived relationship edit/retry: ' . $boundary); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($child_path, $child_text); $compiler->update_cpp([$child_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Upcast repair changed output'); }
		upcast_export('cross_file_' . $boundary, native_record_export(), 9);
		Compiler_Lifecycle::reset();
		$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Fresh upcast output differs'); }
	}
	// Removing the target's virtual contract must invalidate an otherwise unchanged transfer.
	$virtual_base = 'interface I { function read(): int; } class B implements I { int $n = 9; function read(): int { return 9; } }';
	file_put_contents($base_path, $virtual_base);
	file_put_contents($child_path, 'class C extends B {}');
	file_put_contents($main_path, '$c unique<C> = new C(); $b unique<B> = move($c); return $b->n;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	file_put_contents($base_path, str_replace(' implements I', '', $virtual_base));
	foreach ([[$base_path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Unsafe unique upcast survived virtual-contract edit'); }
		catch (\RuntimeException $error) {
			if (!str_contains($error->getMessage(), 'virtual destruction')) { throw $error; }
		}
	}
	file_put_contents($base_path, $virtual_base); $compiler->update_cpp([$base_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Unique upcast repair changed output'); }
	upcast_export('unique_contract_repair', native_record_export(), 9);
	Compiler_Lifecycle::reset_preparation(); $compiler->prepare(); $compiler->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Unique upcast reset changed output'); }
	Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Unique upcast fresh output differs'); }
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Shared/weak/unique upcasts, invariant references and contract invalidation passed\n";
