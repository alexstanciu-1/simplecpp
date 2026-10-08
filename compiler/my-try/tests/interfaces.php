<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function interface_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/interfaces', '/interfaces', '/interfaces');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}
$results = [];
function interface_export(string $name, string $cpp, int $expected, bool $dispatch = false): void
{
	global $argv, $results;
	if (!isset($argv[1])) { return; }
	if ($dispatch) {
		// Native interface use proves the lowering without enabling source upcasts in this slice.
		$cpp = str_replace("int main()\n{", "int main()\n{\n{ record_C value; record_I* view = &value; if (view->method_read().native_value() != 9) return 101; }", $cpp);
	}
	if ($name === 'interface_cleanup') {
		$cpp = str_replace("int main()\n{", "int main()\n{\n{ record_I* view = new record_C(); delete view; }", $cpp);
	}
	$path = $argv[1] . '/' . $name . '.cpp'; file_put_contents($path, $cpp);
	$results[] = ['path' => $path, 'exit_code' => $expected];
}
foreach (['class', 'struct'] as $kind) {
	foreach ([
		'local' => 'interface I { function read(): int; } ' . $kind . ' C implements I { function read(): int { return 9; } }',
		'override' => 'interface I { function read(): int; } ' . $kind . ' C implements I { #[\Override] function read(): int { return 9; } }',
		'inherited' => 'interface I { function read(): int; } ' . $kind . ' B { function read(): int { return 9; } } ' . $kind . ' C extends B implements I {}',
		'inherited_final_nonvirtual' => 'interface I { function read(): int; } ' . $kind . ' B { final function read(): int { return 9; } } ' . $kind . ' C extends B implements I {}',
		'inherited_final_virtual' => 'interface I { function read(): int; } abstract ' . $kind . ' A { abstract function read(): int; } ' . $kind . ' B extends A { final function read(): int { return 9; } } ' . $kind . ' C extends B implements I {}',
		'final_multiple_views' => 'interface I { function read(): int; } interface J { function read(): int; } abstract ' . $kind . ' A { abstract function read(): int; } ' . $kind . ' B extends A { final function read(): int { return 9; } } ' . $kind . ' M extends B implements J {} ' . $kind . ' C extends M implements I {}',
		'bridge_descendant' => 'interface I { function read(): int; } ' . $kind . ' B { function read(): int { return 1; } } ' . $kind . ' M extends B implements I {} ' . $kind . ' C extends M { #[\Override] function read(): int { return 9; } }',
		'abstract_intermediate' => 'interface I { function read(): int; } abstract ' . $kind . ' B implements I {} ' . $kind . ' C extends B { function read(): int { return 9; } }',
		'diamond' => 'interface I { function read(): int; } interface L extends I {} interface R extends I {} interface D extends L, R {} ' . $kind . ' C implements D { function read(): int { return 9; } }',
		'multiple_contracts' => 'interface I { function read(): int; } interface J { function read(): int; } ' . $kind . ' C implements I, J { function read(): int { return 9; } }',
		'inherited_interface' => 'interface I { function read(): int; } ' . $kind . ' B implements I { final function read(): int { return 9; } } ' . $kind . ' C extends B implements I {}',
		'virtual_forwarder' => 'interface I { function read(): int; } abstract ' . $kind . ' A { abstract function read(): int; } ' . $kind . ' B extends A { function read(): int { return 9; } } ' . $kind . ' C extends B implements I {}',
	] as $name => $declarations) {
		$cpp = interface_source($declarations . ' $c = new C(); return $c->read();');
		interface_export($kind . '_' . $name, $cpp, 9, true);
	}
}
foreach ([
	'reference_forwarder' => ['interface I { function &select(int &$n): int; } class B { function &select(int &$n): int { return $n; } } class C extends B implements I {} $c = new C(); $n = 1; $a =& $c->select($n); $a = 9; return $n;', 9],
	'variadic_forwarder' => ['interface I { function sum(int ...$ns): int; } class B { function sum(int ...$ns): int { $n = 0; foreach ($ns as $v) { $n += $v; } return $n; } } class C extends B implements I {} $c = new C(); return $c->sum(4, 5);', 9],
	'void_forwarder' => ['interface I { function put(int &$n): void; } struct B { function put(int &$n): void { $n = 9; } } struct C extends B implements I {} $c = new C(); $n = 0; $c->put($n); return $n;', 9],
	'empty' => ['interface I {} class C implements I {} $c = new C(); return 9;', 9],
	'contract_redeclaration' => ['interface I { function read(): int; } interface J extends I { function read(): int; } class C implements J { function read(): int { return 9; } } $c = new C(); return $c->read();', 9],
	'unique_forwarder' => ['interface I { function use_it(unique<int> $p): int; } class B { function use_it(unique<int> $p): int { return isset($p) ? 1 : 9; } } class C extends B implements I {} $c = new C(); $p unique<int>; return $c->use_it(move($p));', 9],
	'readonly_forwarder' => ['interface I { function use_it(const int &$n): int; } class B { function use_it(const int &$n): int { return $n; } } class C extends B implements I {} $c = new C(); $n = 9; return $c->use_it($n);', 9],
	'payload_headers' => ['struct Payload { int $n = 9; } interface I { function copy(Payload $p): Payload; } class B { function copy(Payload $p): Payload { return $p; } } class C extends B implements I {} $c = new C(); $p Payload; $q = $c->copy($p); return $q->n;', 9],
	'parent_forwarder' => ['interface I { function read(): int; } class B { function read(): int { return 9; } } class M extends B implements I {} class C extends M { function read(): int { return parent::read(); } } $c = new C(); return $c->read();', 9],
	'interface_cleanup' => ['interface I { function read(): int; } class Log { static int $n = 0; } class B { function read(): int { return 9; } function __destruct() { Log::$n = Log::$n * 10 + 1; } } class C extends B implements I { function __destruct() { Log::$n = Log::$n * 10 + 2; } } $c C; return Log::$n;', 21],
] as $name => [$text, $expected]) { interface_export($name, interface_source($text), $expected); }
foreach ([
	['interface I { function read(): int; } class C implements I {}', 'unimplemented'],
	['interface I { function read(): int; } class C implements I { protected function read(): int { return 1; } }', 'public instance'],
	['interface I { function read(): int; } class C implements I { function read(): float { return 1.0; } }', 'compatible'],
	['interface I { function read(): int; } class C implements I { static function read(): int { return 1; } }', 'public instance'],
	['interface I { function read(): int; } interface J { function read(): float; } abstract class C implements I, J {}', 'Incompatible'],
	['interface I { function read(): int; } interface J extends I { function read(): float; }', 'Incompatible'],
	['interface I { function read(): int { return 1; } }', 'bodyless'],
	['interface I { static function read(): int; }', 'bodyless'],
	['interface I { protected function read(): int; }', 'public'],
	['interface I { function __construct(); }', 'constructor'],
	['interface I { function read(): int; function read(): int; }', 'Duplicate'],
	['interface I { function use_it(const int &$n): int; } class C implements I { function use_it(int &$n): int { return $n; } }', 'compatible'],
	['interface I { function f(const string &$s): int; } class C implements I { function f(string $s): int { return 1; } }', 'compatible'],
	['interface I { function read(): int; } class B { final function read(): int { return 1; } } class M extends B implements I {} class C extends M { function read(): int { return 2; } }', 'final'],
	['interface I { int $n; }', 'method contracts only'],
	['interface I { const N = 1; }', 'method contracts only'],
	['interface I {} $x = new I();', 'new requires'],
	['interface I { function read(): int; } abstract class A { abstract function read(): int; } class B extends A { final function read(): int { return 1; } } class M extends B implements I {} class C extends M { function read(): int { return 2; } }', 'final method'],
	['interface I { function read(): int; } class B { private function read(): int { return 1; } } class C extends B implements I {}', 'public instance'],
] as [$text, $message]) {
	try { interface_source($text); throw new \LogicException('Expected rejection: ' . $message); }
	catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), $message)) { throw $error; } }
}
$directory = sys_get_temp_dir() . '/interface-contracts-' . bin2hex(random_bytes(6));
mkdir($directory);
$interface_path = $directory . '/interface.phs';
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
$main_path = $directory . '/main.phs';
try {
	$interface_text = 'interface I { function read(): int; } interface Empty {}';
	$base_text = 'class B { function read(): int { return 9; } }';
	$child_text = 'class C extends B implements I {}';
	file_put_contents($interface_path, $interface_text);
	file_put_contents($base_path, $base_text);
	file_put_contents($child_path, $child_text);
	file_put_contents($main_path, '$c = new C(); return $c->read();');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	$child = Model::$global_scope->source_types_named('C')[0];
	$interface = Model::$global_scope->source_types_named('I')[0];
	$contract = $child->contract_work;
	if (!$child->syntax()->methods->is_empty() || ($contract->require_facts()->forwarders->count() !== 1)
		|| ($interface->preparation_work_owner() !== null) || ($interface->syntax()->preparation() !== null)) {
		throw new \LogicException('Interface execution manufactured source members or interface layout');
	}
	foreach ([str_replace(': int', ': float', $interface_text),
		str_replace('function read(): int;', 'function read(): int; function missing(): int;', $interface_text)] as $invalid) {
		file_put_contents($interface_path, $invalid);
		foreach ([[$interface_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Interface change survived update/retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($interface_path, $interface_text); $compiler->update_cpp([$interface_path]);
		if (($child->contract_work !== $contract) || (CPP_Output::encode(Model::$cpp_files) !== $original)) {
			throw new \LogicException('Interface repair lost work identity or output equivalence');
		}
	}
	// Finality changes select a new interface view, never an override of the final slot.
	$virtual_base = 'abstract class A { abstract function read(): int; } class B extends A { function read(): int { return 9; } }';
	foreach ([false, true, false, true] as $final) {
		$current_base = $final ? str_replace('class B extends A { function', 'class B extends A { final function', $virtual_base) : $virtual_base;
		file_put_contents($base_path, $current_base); $compiler->update_cpp([$base_path]);
		$base = Model::$global_scope->source_types_named('B')[0];
		$bindings = $child->contract_work->require_facts()->bindings;
		$ancestor_overrides = 0;
		foreach ($bindings as $binding) {
			if (weakref_get($binding->required_owner) === $base) { $ancestor_overrides++; }
			if ($final && weakref_get($binding->required_owner) === $interface) {
				if (weakref_get($binding->implementation_owner) !== $base) { throw new \LogicException('Final interface view missed inherited slot'); }
			}
		}
		if ($ancestor_overrides !== ($final ? 0 : 1)) { throw new \LogicException('Incorrect final-slot override selection'); }
	}
	$valid_final = CPP_Output::encode(Model::$cpp_files);
	file_put_contents($base_path, str_replace('final function', 'final protected function', $current_base));
	foreach ([[$base_path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Nonpublic final interface implementation accepted'); }
		catch (\RuntimeException $expected) {}
	}
	file_put_contents($base_path, $current_base); $compiler->update_cpp([$base_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $valid_final) { throw new \LogicException('Final interface repair differs'); }
	interface_export('final_cross_file_repair', native_record_export(), 9, true);
	// Unchanged method contracts must not hide relationship-only header edits.
	file_put_contents($interface_path, str_replace('interface I {', 'interface I extends Empty {', $interface_text));
	$compiler->update_cpp([$interface_path]);
	if (!str_contains(CPP_Probe::content(Model::$cpp_files), 'record_I : public virtual record_Empty')) {
		throw new \LogicException('Cached interface fragment hid ancestry change');
	}
	file_put_contents($child_path, 'class C extends B implements I, Empty {}');
	$compiler->update_cpp([$child_path]);
	if (!str_contains(CPP_Probe::content(Model::$cpp_files), 'record_C : public record_B, public virtual record_I, public virtual record_Empty')) {
		throw new \LogicException('Cached record fragment hid implements change');
	}
	interface_export('cross_file_edges', native_record_export(), 9, true);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	if ($contract->facts !== null || count($contract->dependencies) !== 0) { throw new \LogicException('Reset retained interface dependencies'); }
	$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Fresh interface output differs'); }
	file_put_contents($child_path, 'class C extends B {}'); $compiler->update_cpp([$child_path]);
	$child = Model::$global_scope->source_types_named('C')[0];
	if (!$child->contract_work->require_facts()->forwarders->is_empty()) { throw new \LogicException('Removed implements retained forwarding'); }
	file_put_contents($interface_path, ''); $compiler->update_cpp([$interface_path]);
	interface_export('removed_interface', native_record_export(), 9);
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$interface_path, $base_path, $child_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Interface contracts, inherited forwarding, diamonds and bounded rejections passed\n";
