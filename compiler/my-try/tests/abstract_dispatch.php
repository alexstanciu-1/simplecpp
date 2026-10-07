<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function abstract_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/abstract-dispatch', '/abstract-dispatch', '/abstract-dispatch');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $text;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}

$results = [];
function abstract_export(string $name, string $cpp, int $expected): void
{
	global $argv, $results;
	if (!isset($argv[1])) { return; }
	if ($name === 'noncopyable_layout') {
		$cpp .= "\n#include <type_traits>\nstatic_assert(std::is_move_constructible_v<record_C>);\nstatic_assert(std::is_move_assignable_v<record_C>);\nstatic_assert(!std::is_copy_constructible_v<record_C>);\nstatic_assert(std::has_virtual_destructor_v<record_C>);\n";
	}
	$path = $argv[1] . '/' . $name . '.cpp';
	file_put_contents($path, $cpp);
	$results[] = ['path' => $path, 'exit_code' => $expected];
}

foreach (['class', 'struct'] as $kind) {
	$cases = [
		'dispatch' => ['abstract ' . $kind . ' B { abstract function score(): int; function read(): int { return $this->score(); } } ' . $kind . ' C extends B { function score(): int { return 9; } } $c = new C(); return $c->read();', 9],
		'assertion' => ['abstract ' . $kind . ' B { abstract protected function score(): int; function read(): int { return $this->score(); } } ' . $kind . ' C extends B { #[\Override] public function score(): int { return 9; } } $c = new C(); return $c->read();', 9],
		'intermediate' => ['abstract ' . $kind . ' B { abstract function score(): int; function read(): int { return $this->score(); } } abstract ' . $kind . ' M extends B {} final ' . $kind . ' C extends M { final function score(): int { return 9; } } $c = new C(); return $c->read();', 9],
		'parent' => ['abstract ' . $kind . ' B { abstract function score(): int; function read(): int { return $this->score(); } } ' . $kind . ' M extends B { function score(): int { return 4; } } ' . $kind . ' C extends M { function score(): int { $n = parent::score(); return $n + 5; } } $c = new C(); return $c->read();', 9],
		'constructor' => ['abstract ' . $kind . ' B { int $n; function __construct(set:$n) {} abstract function score(): int; } ' . $kind . ' C extends B { function __construct() { parent::__construct(9); } function score(): int { return $this->n; } } $c = new C(); return $c->score();', 9],
		'inherited_constructor' => ['abstract ' . $kind . ' B { int $n; function __construct(set:$n = 9) {} abstract function score(): int; } ' . $kind . ' C extends B { function score(): int { return $this->n; } } $c = new C(); return $c->score();', 9],
		'nonvirtual_abstract_type' => ['abstract ' . $kind . ' B { int $n = 9; } ' . $kind . ' C extends B {} $c = new C(); return $c->n;', 9],
		'final_inherited' => ['abstract ' . $kind . ' B { abstract function score(): int; } ' . $kind . ' M extends B { final function score(): int { return 9; } } ' . $kind . ' C extends M {} $c = new C(); return $c->score();', 9],
		'variadic' => ['abstract ' . $kind . ' B { abstract function score(int ...$ns): int; function read(): int { return $this->score(4, 5); } } ' . $kind . ' C extends B { function score(int ...$ns): int { $n = 0; foreach ($ns as $v) { $n += $v; } return $n; } } $c = new C(); return $c->read();', 9],
		'reference' => ['abstract ' . $kind . ' B { abstract function &select(int &$n): int; function &read(int &$n): int { return $this->select($n); } } ' . $kind . ' C extends B { function &select(int &$n): int { return $n; } } $c = new C(); $n = 1; $v =& $c->read($n); $v = 9; return $n;', 9],
		'parent_reference' => ['abstract ' . $kind . ' B { abstract function &select(int &$n): int; } ' . $kind . ' M extends B { function &select(int &$n): int { return $n; } } ' . $kind . ' C extends M { function &select(int &$n): int { return parent::select($n); } } $c = new C(); $n = 1; $v =& $c->select($n); $v = 9; return $n;', 9],
	];
	foreach ($cases as $name => [$text, $expected]) { abstract_export($kind . '_' . $name, abstract_source($text), $expected); }
}
foreach ([
	'inline_copy' => ['abstract struct B { int $n = 4; abstract function score(): int; } struct C extends B { function score(): int { return $this->n; } } $c = new C(); $d = $c; $d->n = 9; return $c->score();', 4],
	'inline_class_copy' => ['abstract class B { int $n = 4; abstract function score(): int; } class C extends B { function score(): int { return $this->n; } } $c value<C> = new C(); $d = $c; $d->n = 9; return $c->score();', 4],
	'inherited_implicit_default' => ['abstract struct B { int $n; function __construct() { $this->n = 9; } abstract function score(): int; } struct C extends B { function score(): int { return $this->n; } } $c C; return $c->score();', 9],
	'absent_handle' => ['abstract class B { abstract function score(): int; } $b B; return isset($b) ? 1 : 9;', 9],
	'destruction_order' => ['class Log { static int $n = 0; } abstract class B { abstract function score(): int; function __destruct() { Log::$n = Log::$n * 10 + 1; } } class C extends B { function score(): int { return 0; } function __destruct() { Log::$n = Log::$n * 10 + 2; } } $c = new C(); $c = null; return Log::$n;', 21],
	'noncopyable_layout' => ['abstract struct B { unique<int> $p; abstract function score(): int; } struct C extends B { function score(): int { return 9; } } $d C; return $d->score();', 9],
	'static_defaults' => ['abstract class B { abstract function score(int $n = 4): int; function read(): int { return $this->score(); } } class C extends B { function score(int $renamed = 5): int { return $renamed; } } $c = new C(); $a = $c->read(); $b = $c->score(); return $a + $b;', 9],
	'constructor_dispatch' => ['abstract class A { abstract function score(): int; } class B extends A { int $n; function score(): int { return 4; } function __construct() { $this->n = $this->score(); } } class C extends B { function score(): int { return 9; } } $c = new C(); return $c->n;', 4],
] as $name => [$text, $expected]) { abstract_export($name, abstract_source($text), $expected); }

foreach ([
	['abstract class B {} $b = new B();', 'abstract'],
	['abstract struct B {} $b B;', 'initializer'],
	['abstract data class B { int $n; } $b B = {n: 1};', 'abstract'],
	['abstract class B { abstract function score(): int; } class C extends B {}', 'unimplemented'],
	['class B { abstract function score(): int; }', 'abstract declaring'],
	['abstract final class B {}', 'abstract'],
	['abstract class B { abstract function score(): int { return 1; } }', 'body'],
	['class B { function score(): int; }', 'body'],
	['abstract class B { abstract static function score(): int; }', 'instance'],
	['abstract class B { abstract private function score(): int; }', 'public/protected'],
	['abstract class B { abstract final function score(): int; }', 'non-final'],
	['class B { function __construct(); }', 'concrete body'],
	['class B { #[\Override] function score(): int { return 1; } }', 'assertion'],
	['class B { function score(): int { return 1; } } class C extends B { function score(): int { return 2; } }', 'Nonvirtual'],
	['abstract class B { abstract function score(): int; } class M extends B { final function score(): int { return 1; } } class C extends M { function score(): int { return 2; } }', 'final method'],
	['final class B {} class C extends B {}', 'final'],
	['abstract class B { abstract function score(): int; } class C extends B { protected function score(): int { return 1; } }', 'visibility'],
	['abstract class B { abstract function score(): int; } class C extends B { function score(): int { return parent::score(); } }', 'abstract'],
	['abstract struct B {} function f(B $b): int { return 1; }', 'inline value'],
	['abstract struct B {} function f(): B {}', 'inline value'],
	['abstract struct B {} struct C { B $b; }', 'value_storable'],
	['abstract struct B {} $bs vector<B>;', 'value_storable'],
	['abstract class B {} function f(value<B> $b): int { return 1; }', 'inline value'],
	['abstract class B { abstract function score(): int; } class C extends B { function score(): float { return 1.0; } }', 'compatible'],
	['abstract class B { abstract function score(): int; } class C extends B { static function score(): int { return 1; } }', 'compatible'],
	['abstract class B { abstract function &select(int &$n): int; function &read(int &$n): int { return $this->select($n); } } class C extends B { function &select(int &$n): int { return $this->read($n); } }', 'recursive'],
] as [$text, $message]) {
	try { abstract_source($text); throw new \LogicException('Expected rejection: ' . $message . ': ' . $text); }
	catch (\RuntimeException $error) { if (!str_contains(strtolower($error->getMessage()), strtolower($message))) { throw $error; } }
}
foreach ([['int $n', 'float $n'], ['int &$n', 'int $n'], ['const int &$n', 'int &$n'], ['int ...$n', 'vector<int> $n'], ['int $n = 1', 'int $n']] as [$base, $child]) {
	try {
		abstract_source('abstract class B { abstract function score(' . $base . '): int; } class C extends B { function score(' . $child . '): int { return 1; } }');
		throw new \LogicException('Incompatible override admitted');
	}
	catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), 'compatible')) { throw $error; } }
}

$directory = sys_get_temp_dir() . '/abstract-contracts-' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
$main_path = $directory . '/main.phs';
try {
	$base_text = 'abstract class B { abstract function score(int $n): int; function read(): int { return $this->score(9); } }';
	$child_text = 'class C extends B { function score(int $n): int { return $n; } }';
	file_put_contents($base_path, $base_text);
	file_put_contents($child_path, $child_text);
	file_put_contents($main_path, '$c = new C(); return $c->read();');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	$child = object_cast(Model::$global_scope->source_types_named('C')[0], collected_record::class);
	$contract = $child->contract_work;
	foreach ([str_replace('int $n', 'float $n', $base_text),
		str_replace('abstract function score(int $n): int;', 'function score(int $n): int { return $n; }', $base_text)] as $invalid) {
		file_put_contents($base_path, $invalid);
		foreach ([[$base_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid override survived update/retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($base_path, $base_text);
		$compiler->update_cpp([$base_path]);
		if ($child->contract_work !== $contract) { throw new \LogicException('Contract identity was replaced'); }
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Contract repair differs'); }
	}
	// Concrete -> abstract -> concrete retires the old body without discarding signature identity.
	$method = $child->syntax()->methods[0];
	$signature = $method->occurrence()->preparation_work_owner();
	$old_body = $method->require_body();
	$old_work = $old_body->work();
	file_put_contents($child_path, 'abstract class C extends B { abstract function score(int $n): int; }');
	file_put_contents($main_path, '$c C; return isset($c) ? 1 : 9;');
	$compiler->update_cpp([$child_path, $main_path]);
	if ($method->has_parsed_body() || ($method->occurrence()->preparation_work_owner() !== $signature)
		|| ($old_work->change_status !== change_state::deleted) || (count($old_work->dependencies) !== 0)
		|| ($old_body->work() !== null) || !$method->retired_bodies->is_empty()) {
		throw new \LogicException('Bodyless transition lost lifecycle separation');
	}
	abstract_export('incremental_bodyless', native_record_export(), 9);
	file_put_contents($child_path, $child_text);
	file_put_contents($main_path, '$c = new C(); return $c->read();');
	$compiler->update_cpp([$child_path, $main_path]);
	if ($method->require_body()->work() === $old_work) { throw new \LogicException('Retired body work was resurrected'); }
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Restored concrete output differs'); }
	// Finality must invalidate child contracts even without changing the callable signature.
	file_put_contents($base_path, 'abstract class A { abstract function score(int $n): int; } class B extends A { function score(int $n): int { return $n; } function read(): int { return $this->score(9); } }');
	$compiler->update_cpp([$base_path]);
	$valid_base = file_get_contents($base_path);
	file_put_contents($base_path, str_replace('class B extends A { function', 'class B extends A { final function', $valid_base));
	foreach ([[$base_path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Final override survived edit/retry'); }
		catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), 'final method')) { throw $error; } }
	}
	file_put_contents($base_path, $valid_base);
	$compiler->update_cpp([$base_path]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	abstract_export('cross_file_repair', native_record_export(), 9);
	Compiler_Lifecycle::reset();
	if ((count($contract->dependencies) !== 0) || ($contract->facts !== null)) { throw new \LogicException('Reset retained type contracts'); }
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Fresh/incremental contracts differ'); }
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}

if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Abstract class/struct contracts, overrides and dispatch passed\n";
