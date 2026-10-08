<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function destructors_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/destructors', '/destructors', '/destructors');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}

function destructor_lifecycle(): type_lifecycle_work
{
	foreach (Model::collected_files() as $source) {
		foreach ($source->entries as $entry) {
			if ($entry instanceof collected_record && $entry->name === 'C') { return $entry->lifecycle_work; }
		}
	}
	throw new \LogicException('Missing destructor lifecycle work');
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	foreach ([
		'shared_last_owner' => ['$a shared<C> = new C(); $b = $a; $w = weak($a); $a = null; if (C::$count !== 0) { return 1; } $b = null; if (lock($w)) { return 2; } return C::$count;', 1],
		'unique_reset' => '$a unique<C> = new C(); $a = null; return C::$count;',
		'inline_scope' => 'if (true) { $a value<C>; } return C::$count;',
		'inline_copies' => ['if (true) { $a value<C>; $b = $a; } return C::$count;', 2],
		'alias' => 'if (true) { $a value<C>; $alias =& $a; } return C::$count;',
		'early_return' => 'function go(): void { $a value<C>; return; } go(); return C::$count;',
		'loop_break' => 'while (true) { $a value<C>; break; } return C::$count;',
		'loop_continue' => ['$i = 0; while ($i < 2) { $a value<C>; $i++; continue; } return C::$count;', 2],
		'field_access' => 'if (true) { $a value<C>; $a->n = 1; } return C::$count;',
	] as $name => $fixture) {
		$program = is_array($fixture) ? $fixture[0] : $fixture;
		$expected = is_array($fixture) ? $fixture[1] : 1;
		$program = str_replace($kind === 'class' ? 'shared<C>' : 'value<C>', 'C', $program);
		$output = destructors_source($kind . ' C { static int $count = 0; int $n = 1; function __destruct() { $n = $this->n; self::$count = self::$count + $n; } } ' . $program);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
}
foreach ([
	'member_order' => 'class Log { static int $n = 0; } data struct Item { int $id; function __destruct() { $id = $this->id; Log::$n = Log::$n * 10 + $id; } } struct Outer { Item $a = {id: 1}; Item $b = {id: 2}; function __destruct() { Log::$n = Log::$n * 10 + 9; } } if (true) { $o Outer; Log::$n = 0; } if (Log::$n !== 921) { return 1; } return 0;',
	'constructor_data' => 'data class C { static int $n = 0; int $id; function __construct(set:$id) {} function __destruct() { self::$n = $this->id; } } $c C = {id: 9}; $c = null; if (C::$n !== 9) { return 1; } return 0;',
	'noncopyable_move' => 'struct C { unique<int> $owned; function __destruct() {} } $a C; return 0;',
	'destructor_method' => 'class C { static int $n = 0; function finish(): void { self::$n = 9; } function __destruct() { $this->finish(); } } $c = new C(); $c = null; if (C::$n !== 9) { return 1; } return 0;',
] as $name => $program) {
	$output = destructors_source($program);
	if ($name === 'noncopyable_move') {
		// Source admission remains unchanged; independently prove the emitted record's move/copy traits.
		foreach (Model::$cpp_files as $artifact) {
			if ($artifact->identity === 'entry') {
				$proof = "\n#include <type_traits>\nstatic_assert(std::is_move_constructible_v<record_C>);\nstatic_assert(std::is_move_assignable_v<record_C>);\nstatic_assert(!std::is_copy_constructible_v<record_C>);\nstatic_assert(!std::is_copy_assignable_v<record_C>);\n";
				$output = str_replace($artifact->text, $artifact->text . $proof, $output);
			}
		}
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 0];
	}
}
foreach (['terminate', 'unwind'] as $name) {
	$failure = '$v vector<int> = []; $n = $v[0];';
	$source = 'struct C { static int $count = 0; function __destruct() { self::$count = 1; '
		. ($name === 'terminate' ? $failure : '') . ' } } if (true) { $c C; '
		. ($name === 'unwind' ? $failure : '') . ' } return 3;';
	$output = destructors_source($source);
	foreach (Model::$cpp_files as $artifact) {
		if ($artifact->identity === 'entry') {
			$instrumented = '';
			if ($name === 'terminate') {
				$probe = "#include <exception>\n#include <cstdlib>\nstatic const bool termination_probe = []() { std::set_terminate([]() { std::_Exit(record_C::field_count.native_value() == 1 ? 77 : 2); }); return true; }();\nint main()";
				$instrumented = str_replace('int main()', $probe, $artifact->text);
			}
			else {
				$instrumented = str_replace('int main()', 'int main() try', $artifact->text)
					. 'catch (const scpp::runtime_error&) { return record_C::field_count.native_value() == 1 ? 0 : 2; }' . "\n";
			}
			$output = str_replace($artifact->text, $instrumented, $output);
		}
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => $name === 'terminate' ? 77 : 0];
	}
}
foreach ([
	'function __destruct(int $n) {}',
	'function __destruct(): void {}',
	'function &__destruct() {}',
	'static function __destruct() {}',
	'function __destruct() { return 1; }',
	'function __destruct() {} function __destruct() {}',
] as $member) {
	try { destructors_source('class C { ' . $member . ' }'); throw new \LogicException('Invalid destructor accepted'); }
	catch (\RuntimeException $expected) {}
}
foreach (['$c->__destruct();', 'C::__destruct();', '$c::__destruct();'] as $call) {
	try { destructors_source('class C { function __destruct() {} } $c = new C(); ' . $call); throw new \LogicException('Explicit destructor invocation accepted'); }
	catch (\RuntimeException $expected) {}
}
try { destructors_source('struct C { unique<int> $owned; function __destruct() {} } $a C; $copy = $a;'); throw new \LogicException('Noncopyable record became copyable'); }
catch (\RuntimeException $expected) {}

foreach (['class', 'struct'] as $kind) {
	$child = $kind === 'class' ? 'Child' : 'shared<Child>';
	$base = $kind === 'class' ? 'Base' : 'shared<Base>';
	foreach ([
		'protected_base' => '$c CHILD = new Child(); $b BASE = $c; $w = weak($b); $c = null; if (Log::$n !== 0) { return 1; } $b = null; return Log::$n;',
		'unique_child' => '$c unique<Child> = new Child(); $c = null; return Log::$n;',
		'inline_child' => 'if (true) { $c INLINE; } return Log::$n;',
	] as $name => $body) {
		$source = 'class Log { static int $n = 0; } ' . $kind . ' Base { protected function __destruct() { Log::$n = Log::$n * 10 + 2; } } '
			. $kind . ' Child extends Base { public function __destruct() { Log::$n = 1; } } '
			. str_replace(['CHILD', 'BASE', 'INLINE'], [$child, $base, $kind === 'class' ? 'value<Child>' : 'Child'], $body);
		$output = destructors_source($source);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp'; file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => 12];
		}
	}
}
foreach ([
	'implicit_child_cleanup' => 'class Log { static int $n = 0; } class Base { protected function __destruct() { Log::$n = 9; } } class Child extends Base {} $c = new Child(); $b Base = $c; $c = null; $b = null; return Log::$n;',
	'private_declaration' => 'class C { private function __destruct() {} } $c C; $w = weak($c); return 9;',
	'recursive_unique' => 'class C { unique<C> $next; function __destruct() {} static function make(): unique<C> { $c unique<C> = new C(); return move($c); } } $c = C::make(); return 9;',
	'private_inline_owner' => 'struct C { static int $n = 0; private function __destruct() { self::$n = 9; } static function run(): void { $c C; } } C::run(); return C::$n;',
	'protected_interface_cleanup' => 'interface I {} class Log { static int $n = 0; } class B implements I { protected function __destruct() { Log::$n = 9; } } class C extends B {} $c = new C(); $i I = $c; $w = weak($i); $c = null; $i = null; if (lock($w)) { return 1; } return Log::$n;',
] as $name => $source) {
	$output = destructors_source($source);
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp'; file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
}
foreach ([
	'class C { private function __destruct() {} } $c = new C();' => 'public destructor',
	'class C { protected function __destruct() {} static function make(): C { return new C(); } } $c = C::make();' => 'public destructor',
	'data class C { protected function __destruct() {} } $c C = {};' => 'public destructor',
	'class C { protected function __destruct() {} } $c unique<C>;' => 'accessible destructor',
	'class C { protected function __destruct() {} } $c value<C>;' => 'accessible destructor',
	'struct C { private function __destruct() {} } $c C;' => 'accessible destructor',
	'struct C { private function __destruct() {} } $c nullable<C> = null;' => 'accessible destructor',
	'struct C { protected function __destruct() {} } $c vector<C> = [];' => 'accessible destructor',
	'class C { private function __destruct() {} } class Child extends C {}' => 'Private base destructor',
	'class C { protected function __destruct() {} } function consume(unique<C> $v): void {}' => 'accessible destructor',
	'class C { private function __destruct() {} unique<C> $next; }' => 'accessible destructor',
	'struct C { private function __destruct() {} static function make(): C { $c C; return $c; } } C::make();' => 'accessible destructor',
] as $source => $diagnostic) {
	try { destructors_source($source); throw new \LogicException('Inaccessible destruction accepted'); }
	catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), $diagnostic)) { throw $error; } }
}

$directory = sys_get_temp_dir() . '/destructors-' . bin2hex(random_bytes(6));
mkdir($directory);
$type_path = $directory . '/types.phs';
$entry_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; class C { static int $n = 0; function __destruct() { self::$n = 9; } }';
	file_put_contents($type_path, $declaration);
	file_put_contents($entry_path, 'use Types\\C; $c = new C(); $c = null; return C::$n;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	$lifecycle = destructor_lifecycle();
	$version = $lifecycle->version;
	foreach ($lifecycle->dependencies as $dependency) {
		if ($dependency instanceof record_definition_work || $dependency instanceof source_callable_signature_work || $dependency instanceof type_contract_work) {
			throw new \LogicException('Lifecycle contract depends on layout or method preparation');
		}
	}
	$compiler->update_cpp([]);
	if ($lifecycle->version !== $version) { throw new \LogicException('No-op changed lifecycle version'); }
	foreach (['private', 'protected'] as $visibility) {
		file_put_contents($type_path, str_replace('function __destruct()', $visibility . ' function __destruct()', $declaration));
		foreach ([[$type_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Destructor access edit accepted'); }
			catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), 'public destructor')) { throw $error; } }
		}
		file_put_contents($type_path, $declaration); $compiler->update_cpp([$type_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Destructor visibility repair changed output'); }
	}
	file_put_contents($type_path, str_replace('self::$n = 9', 'self::$n = 8', $declaration));
	$before_body_edit = destructor_lifecycle()->version;
	$compiler->update_cpp([$type_path]);
	if (destructor_lifecycle()->version !== $before_body_edit) { throw new \LogicException('Body edit changed lifecycle contract'); }
	if (CPP_Output::encode(Model::$cpp_files) === $original) { throw new \LogicException('Destructor body edit not emitted'); }
	file_put_contents($type_path, str_replace('function __destruct()', 'static function __destruct()', $declaration));
	foreach ([[$type_path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Invalid destructor edit accepted'); }
		catch (\RuntimeException $expected) {}
	}
	file_put_contents($type_path, 'namespace Types; class C { static int $n = 0; }');
	$compiler->update_cpp([$type_path]);
	file_put_contents($type_path, $declaration);
	$compiler->update_cpp([$type_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Destructor repair changed output'); }
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Destructor fresh mismatch'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Destructor reset mismatch'); }
	if (isset($argv[1])) {
		$path = $argv[1] . '/incremental.cpp';
		file_put_contents($path, CPP_Probe::text(Model::$cpp_files));
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
	$retired = destructor_lifecycle();
	file_put_contents($type_path, 'namespace Types;'); file_put_contents($entry_path, 'return 9;');
	$fresh->update_cpp([$type_path, $entry_path]);
	if ($retired->facts !== null || $retired->change_status !== change_state::deleted
		|| count($retired->dependencies) !== 0 || count($retired->dependents) !== 0 || count($retired->lookups) !== 0) {
		throw new \LogicException('Retired lifecycle retained facts or dependencies');
	}
}
finally { unlink($type_path); unlink($entry_path); rmdir($directory); }
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "destructors: PASS\n";
