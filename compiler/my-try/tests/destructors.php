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
	'private function __destruct() {}',
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
	file_put_contents($type_path, str_replace('self::$n = 9', 'self::$n = 8', $declaration));
	$compiler->update_cpp([$type_path]);
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
}
finally { unlink($type_path); unlink($entry_path); rmdir($directory); }
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "destructors: PASS\n";
