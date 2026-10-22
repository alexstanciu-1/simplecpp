<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function method_parameters_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/method_parameters', '/method_parameters', '/method_parameters');
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
	foreach (['instance', 'static'] as $mode) {
		$modifier = $mode === 'static' ? 'static ' : '';
		$setup = $mode === 'static' ? '' : '$c = new C(); ';
		$call = $mode === 'static' ? 'C::f' : '$c->f';
		foreach ([
			'writable_default' => ['int &$n = 7', 'int', '++$n; return $n;', '$n = 3; $first = CALL($n); return $first + CALL() + $n;', 16],
			'write' => ['int &$n', 'void', '$n += 2;', '$n = 7; CALL($n); return $n;', 9],
			'alias' => ['int &$n', 'void', '$a =& $n; $a = 9;', '$n = 7; $a =& $n; CALL($a); return $n;', 9],
			'observation' => ['const int &$read, int &$write', 'int', '$write = 9; return $read;', '$n = 7; return CALL($n, $n);', 9],
			'snapshot' => ['int $copy, int &$write', 'int', '$write = 9; return $copy;', '$n = 7; $copy = CALL($n, $n); return $copy + $n;', 16],
			'forward_read' => ['const int &$n', 'int', '$a =& $n; return read($a);', 'function read(const int &$n): int { return $n; } $n = 9; return CALL($n);', 9],
			'readonly_pack' => ['int &...$values', 'int', '$sum = 0; foreach ($values as $v) { $sum += $v; } return $sum;', 'return CALL(2, 3, 4);', 9],
			'pack' => ['int ...$values', 'int', '$sum = 0; foreach ($values as $v) { $sum += $v; } return $sum;', 'return CALL(2, 3, 4);', 9],
			'empty_pack' => ['int ...$values', 'int', '$sum = 0; foreach ($values as $v) { $sum += $v; } return $sum;', 'return CALL();', 0],
			'default_pack' => ['int $start = 9, int ...$values', 'int', 'foreach ($values as $v) { $start += $v; } return $start;', 'return CALL();', 9],
			'ref_pack' => ['int &$sum, int ...$values', 'void', 'foreach ($values as $v) { $sum += $v; }', '$n = 1; CALL($n, 3, 5); return $n;', 9],
			'order' => ['int &$n, int ...$values', 'int', '$n = 9; return $values[0] * 10 + $values[1];', 'function bump(int &$n): int { $n += 1; return $n; } $n = 0; return CALL($n, bump($n), bump($n));', 12],
			'container_ref' => ['vector<int> &$values', 'void', '$values[] = 9;', '$v vector<int> = []; CALL($v); return $v[0];', 9],
			'inline_field' => ['int &$n', 'void', '$n = 9;', 'struct Box { int $n; } $b = new Box(); CALL($b->n); return $b->n;', 9],
		] as $name => [$parameters, $return_type, $body, $program, $expected]) {
			$source = $kind . ' C { public ' . $modifier . 'function f(' . $parameters . '): ' . $return_type . ' { ' . $body . ' } } '
				. $setup . str_replace('CALL', $call, $program);
			$output = method_parameters_source($source);
			if (isset($argv[1])) {
				$path = $argv[1] . '/' . $kind . '_' . $mode . '_' . $name . '.cpp';
				file_put_contents($path, $output);
				$results[] = ['path' => $path, 'exit_code' => $expected];
			}
		}
		foreach ([
			['int &$n', 'void', '', 'CALL(1);'],
			['const int &$n', 'void', '', 'CALL(1);'],
			['int &$n', 'void', '', '$n = 1.5; CALL($n);'],
			['int &$n', 'void', '', '$v vector<int> = [1]; CALL($v[0]);'],
			['const int &$n', 'void', '$n = 9;', '$n = 1; CALL($n);'],
			['const int &$n', 'void', '$a =& $n; $a += 1;', '$n = 1; CALL($n);'],
			['const int &$n', 'void', 'write($n);', 'function write(int &$n): void { $n = 9; } $n = 1; CALL($n);'],
			['const vector<int> &$v', 'void', '$v[] = 9;', '$v vector<int> = []; CALL($v);'],
			['int &...$values', 'void', '$values[]=1;', 'CALL();'],
			['int ...$values = 1', 'void', '', ''],
			['int ...$values, int $n', 'void', '', ''],
		] as [$parameters, $return_type, $body, $program]) {
			$failed = false;
			try {
				method_parameters_source($kind . ' C { public ' . $modifier . 'function f(' . $parameters . '): '
					. $return_type . ' { ' . $body . ' } } ' . $setup . str_replace('CALL', $call, $program));
			}
			catch (\RuntimeException $expected) { $failed = true; }
			if (!$failed) { throw new \LogicException('Invalid method parameter boundary accepted: ' . $parameters . ' / ' . $program); }
		}
	}
}

// Retention must also survive mutation through a reference parameter in the method body.
$output = method_parameters_source('class C { int $n = 9; function f(C &$owner): int { $owner = null; return $this->n; } } '
	. '$c = new C(); $w = weak($c); $n = $c->f($c); if (lock($w)) { return 1; } return $n;');
if (isset($argv[1])) {
	$path = $argv[1] . '/retained_receiver.cpp';
	file_put_contents($path, $output);
	$results[] = ['path' => $path, 'exit_code' => 9];
}
$directory = sys_get_temp_dir() . '/method-parameters-' . bin2hex(random_bytes(6));
mkdir($directory);
$type_path = $directory . '/types.phs';
$entry_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; class C { static function f(const int &$n): int { return $n; } }';
	$entry = 'use Types\\C; function read(const int &$n): int { return C::f($n); } $n = 9; return read($n);';
	file_put_contents($type_path, $declaration);
	file_put_contents($entry_path, $entry);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	// Removing only const changes argument admission, even though the body remains read-only.
	file_put_contents($type_path, str_replace('const int', 'int', $declaration));
	foreach ([[$type_path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Mutable-reference edit did not invalidate caller'); }
		catch (\RuntimeException $error) {
			if (!str_contains($error->getMessage(), 'read-only')) { throw $error; }
		}
	}
	file_put_contents($type_path, $declaration);
	$compiler->update_cpp([$type_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Reference signature repair changed output'); }
	file_put_contents($type_path, 'namespace Types; class C { static function f(vector<int> $values): int { return $values[0]; } }');
	file_put_contents($entry_path, 'use Types\\C; $v vector<int> = [9]; return C::f($v);');
	$compiler->update_cpp([$type_path, $entry_path]);
	// Same parameter storage type, but a different call contract: callers must repack.
	file_put_contents($type_path, 'namespace Types; class C { static function f(int ...$values): int { return $values[0]; } }');
	try { $compiler->update_cpp([$type_path]); throw new \LogicException('Variadic edit did not invalidate caller'); }
	catch (\RuntimeException $expected) {}
	file_put_contents($entry_path, 'use Types\\C; return C::f(9);');
	$compiler->update_cpp([$entry_path]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$fresh = new Compiler(new cpp_lowering_options(false));
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Method-parameter fresh/incremental mismatch'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) { throw new \LogicException('Method-parameter reset mismatch'); }
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
echo "method parameters: PASS\n";
