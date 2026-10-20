<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function method_returns_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/method_returns', '/method_returns', '/method_returns');
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
			'copy_alias' => ['int &$n', 'int', 'return $n;', '$n = 7; $copy = CALL($n); $a int =& CALL($n); $a = 9; return $copy + $n;', 16],
			'local_alias' => ['int &$n', 'int', '$a =& $n; return $a;', '$n = 7; $a =& CALL($n); $a = 9; return $n;', 9],
			'branches' => ['int &$n, bool $yes', 'int', 'if ($yes) { $a =& $n; return $a; } else { return $n; }', '$n = 1; $a =& CALL($n, true); $a = 8; $b =& CALL($n, false); $b++; return $n;', 9],
			'forward' => ['int &$n', 'int', 'return leaf($n);', 'function &leaf(int &$n): int { return $n; } function &outer(int &$n): int { ' . $setup . 'return CALL($n); } $n = 1; $a =& outer($n); $a = 9; return $n;', 9],
			'vector_copy' => ['vector<int> &$n', 'vector<int>', 'return $n;', '$n vector<int> = [7]; $copy = CALL($n); $a =& CALL($n); $a[0] = 9; return $copy[0] + $n[0];', 16],
			'shared_copy' => ['Box &$n', 'Box', 'return $n;', 'class Box { int $n = 7; } $n = new Box(); $copy = CALL($n); $copy->n = 9; $a =& CALL($n); $a = null; return $copy->n;', 9],
			'unique_alias' => ['unique<int> &$n', 'unique<int>', 'return $n;', '$n unique<int>; $a =& CALL($n); if (isset($a)) { return 1; } return 0;', 0],
			'variadic' => ['int &$n, int ...$values', 'int', 'foreach ($values as $v) { $n += $v; } return $n;', '$n = 1; $a =& CALL($n, 3, 4); $a++; return $n;', 9],
		] as $name => [$parameters, $type, $body, $program, $expected]) {
			$output = method_returns_source($kind . ' C { public ' . $modifier . 'function &f(' . $parameters . '): ' . $type . ' { ' . $body . ' } } '
				. $setup . str_replace('CALL', $call, $program));
			if (isset($argv[1])) {
				$path = $argv[1] . '/' . $kind . '_' . $mode . '_' . $name . '.cpp';
				file_put_contents($path, $output);
				$results[] = ['path' => $path, 'exit_code' => $expected];
			}
		}
		foreach ([
			['', 'int', 'return 1;', ''],
			['int &$n', 'int', '$local = 1; return $local;', '$input = 1; CALL($input);'],
			['int &$n', 'int', 'return 1;', '$input = 1; CALL($input);'],
			['int &$n', 'int', 'return $this->n;', '$input = 1; CALL($input);'],
			['vector<int> &$n', 'int', 'return $n[0];', '$input vector<int> = [1]; CALL($input);'],
			['int &$n, int &$other', 'int', 'return $other;', ''],
			['const int &$n', 'int', 'return $n;', ''],
			['int &$n', 'float', 'return $n;', ''],
			['int &$n', 'int', 'if ($n) { return $n; }', '$input = 1; CALL($input);'],
			['int &$n', 'int', 'return $n;', 'CALL(1);'],
			['int &$n', 'int', 'return $n;', '$v vector<int> = [1]; CALL($v[0]);'],
			['unique<int> &$n', 'unique<int>', 'return $n;', '$n unique<int>; $copy = CALL($n);'],
			['int &$n', 'int', 'return ' . ($mode === 'static' ? 'self::f' : '$this->f') . '($n);', '$input = 1; CALL($input);'],
		] as [$parameters, $type, $body, $program]) {
			$failed = false;
			try {
				method_returns_source($kind . ' C { int $n; public ' . $modifier . 'function &f(' . $parameters . '): ' . $type . ' { ' . $body . ' } } '
					. $setup . str_replace('CALL', $call, $program));
			}
			catch (\RuntimeException $expected) { $failed = true; }
			if (!$failed) { throw new \LogicException('Invalid method reference return accepted: ' . $parameters . ' / ' . $body . ' / ' . $program); }
		}
	}
}

// The returned storage belongs to the caller, not the retained receiver.
$output = method_returns_source('class C { function &f(int &$n): int { return $n; } } $c = new C(); $w = weak($c); '
	. '$n = 1; $a =& $c->f($n); $c = null; if (lock($w)) { return 1; } $a = 9; return $n;');
if (isset($argv[1])) {
	$path = $argv[1] . '/receiver_release.cpp';
	file_put_contents($path, $output);
	$results[] = ['path' => $path, 'exit_code' => 9];
}

$directory = sys_get_temp_dir() . '/method-returns-' . bin2hex(random_bytes(6));
mkdir($directory);
$type_path = $directory . '/types.phs';
$entry_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; class C { static function &f(int &$n): int { return self::leaf($n); } static function &leaf(int &$n): int { return $n; } }';
	file_put_contents($type_path, $declaration);
	file_put_contents($entry_path, 'use Types\\C; $n = 1; $a =& C::f($n); $a = 9; return $n;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	foreach ([
		str_replace('return $n;', 'return self::f($n);', $declaration),
		str_replace('return $n;', '$local = 1; return $local;', $declaration),
		str_replace('function &f', 'function f', $declaration),
	] as $invalid) {
		file_put_contents($type_path, $invalid);
		foreach ([[$type_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid reference edit accepted'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($type_path, $declaration);
		$compiler->update_cpp([$type_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Reference repair changed output'); }
	}
	Compiler_Lifecycle::reset();
	$fresh = new Compiler(new cpp_lowering_options(false));
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Reference fresh/incremental mismatch'); }
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Reference reset mismatch'); }
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
echo "method returns: PASS\n";
