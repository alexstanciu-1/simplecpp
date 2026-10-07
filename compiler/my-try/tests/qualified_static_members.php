<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function qualified_member_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/qualified_members', '/qualified_members', '/qualified_members');
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
		'read_write' => '$c = new C(); $c::$n = 4; return $c::$n + $c::LIMIT;',
		'absent' => '$c shared<C>; $c::$n = 4; return $c::$n + $c::LIMIT;',
		'unique' => '$c unique<C>; $c::$n = 4; return $c::$n + $c::LIMIT;',
		'weak' => '$c weak<C>; $c::$n = 4; return $c::$n + $c::LIMIT;',
		'inline' => '$c value<C>; $c::$n = 4; return $c::$n + $c::LIMIT;',
		'readonly' => 'function change(const C &$c): int { $c::$n = 4; return $c::$n + $c::LIMIT; } $c = new C(); return change($c);',
		'constant_effect' => 'function produce(int &$n): C { $n += 1; return new C(); } $n = 3; $v = produce($n)::LIMIT; return $v + $n;',
		'read_effect' => 'function produce(): C { C::$n = 9; return new C(); } return produce()::$n;',
		'write_order' => 'function produce(int &$n): C { $n = $n * 10 + 4; return new C(); } function rhs(int &$n): int { $n += 5; return $n; } $n = 0; produce($n)::$n = rhs($n); return C::$n;',
		'write_chain' => 'function produce(int &$n): C { $n = 4; return new C(); } $n = 0; produce($n)::$n = $n = $n + 5; return C::$n;',
		'private_this' => '$c = new C(); return $c->go();',
		'lazy' => 'function produce(int &$n): C { $n += 1; return new C(); } $n = 9; $v = false ? produce($n)::LIMIT : 0; return $n;',
	] as $name => $program) {
		$program = str_replace($kind === 'class' ? 'shared<C>' : 'value<C>', 'C', $program);
		$output = qualified_member_source($kind . ' C { static int $n = 1; const LIMIT = 5; private static int $hidden = 4; private const SECRET = 5; function go(): int { return $this::$hidden + $this::SECRET; } } ' . $program);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => 9];
		}
	}
}
// Checked runtime failures must preserve storage and the specified earlier effects.
foreach (['qualifier_failure', 'rhs_failure'] as $name) {
	$failure = '$v vector<int> = []; $missing = $v[0]; ';
	$source = 'class C { static int $n = 7; static int $stage = 0; } '
		. 'function produce(): C { C::$stage = 1; ' . ($name === 'qualifier_failure' ? $failure : '') . 'return new C(); } '
		. 'function rhs(): int { C::$stage = 2; ' . ($name === 'rhs_failure' ? $failure : '') . 'return 9; } produce()::$n = rhs(); return 3;';
	$output = qualified_member_source($source);
	$stage = $name === 'qualifier_failure' ? 1 : 2;
	foreach (Model::$cpp_files as $artifact) {
		if ($artifact->identity === 'entry') {
			$instrumented = str_replace('int main()', 'int main() try', $artifact->text)
				. 'catch (const scpp::runtime_error&) { return record_C::field_n.native_value() == 7 && record_C::field_stage.native_value() == ' . $stage . ' ? 0 : 2; }' . "\n";
			$output = str_replace($artifact->text, $instrumented, $output);
		}
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 0];
	}
}
foreach ([
	'class C { const LIMIT = 5; } function produce(): C { return new C(); } const BAD = produce()::LIMIT;',
	'class C { const LIMIT = 5; } function produce(): C { return new C(); } class D { int $n = produce()::LIMIT; }',
	'class C { const LIMIT = 5; } $c C; switch (5) { case $c::LIMIT: break; }',
	'class C { const LIMIT = 5; } $c C; $v = match (5) { $c::LIMIT => 1, default => 2 };',
	'class C { const LIMIT = 5; } $c C; $c::LIMIT = 1;',
	'class C { int $n; } $c C; $c::$n = 1;',
	'class C { private static int $n; } $c C; $c::$n = 1;',
	'class C { private const LIMIT = 5; } $c C; return $c::LIMIT;',
	'class C { static int $n; } $c C; $a =& $c::$n;',
	'$c dynamic = {}; return $c::LIMIT;',
	'$c mixed = []; return $c::$n;',
] as $source) {
	$failed = false;
	try { qualified_member_source($source); }
	catch (\RuntimeException $expected) { $failed = true; }
	if (!$failed) { throw new \LogicException('Invalid static member accepted: ' . $source); }
}

$directory = sys_get_temp_dir() . '/qualified-members-' . bin2hex(random_bytes(6));
mkdir($directory);
$type_path = $directory . '/types.phs';
$entry_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; class C { const LIMIT = 5; static int $n = 4; }';
	file_put_contents($type_path, $declaration);
	file_put_contents($entry_path, 'use Types\\C; $c C; return $c::$n + $c::LIMIT;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	file_put_contents($type_path, str_replace('LIMIT = 5', 'LIMIT = 6', $declaration));
	$compiler->update_cpp([$type_path]);
	if (CPP_Output::encode(Model::$cpp_files) === $original) { throw new \LogicException('Constant change did not invalidate qualified consumer'); }
	foreach ([str_replace('static ', '', $declaration), str_replace('const ', 'private const ', $declaration)] as $invalid) {
		file_put_contents($type_path, $invalid);
		foreach ([[$type_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid member edit accepted'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($type_path, $declaration);
		$compiler->update_cpp([$type_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Member repair changed output'); }
	}
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Member fresh mismatch'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Member reset mismatch'); }
	if (isset($argv[1])) {
		$path = $argv[1] . '/incremental.cpp';
		file_put_contents($path, CPP_Probe::text(Model::$cpp_files));
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
}
finally { unlink($type_path); unlink($entry_path); rmdir($directory); }
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "qualified static members: PASS\n";
