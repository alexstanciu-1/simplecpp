<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/empty-callables-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs';
$cases = [
	'absent' => ['$f function<int()>; return isset($f) ? 9 : 0;', 0],
	'condition' => ['$f function<int()>; if ($f) { return 9; } return !$f ? 0 : 8;', 0],
	'assign' => ['$f function<int(int)>; if (true) { $f = fn($n) => $n + 2; } return $f(4);', 6],
	'copy_reset' => ['$f function<int()> = fn() => 7; $g = $f; clean($f); if ($f) { return 9; } return $g();', 7],
	'unset_reassign' => ['$f function<int()> = fn() => 3; unset($f); if (isset($f)) { return 9; } $f = fn() => 6; return $f();', 6],
	'empty_copy' => ['$f function<int()>; $g = $f; $f = fn() => 3; return isset($g) ? 9 : $f();', 3],
	'parameter' => ['function check(function<int()> $f): bool { return isset($f); } $f function<int()>; return check($f);', 0],
	'return' => ['function make(): function<int()> { $f function<int()>; return $f; } return isset(make());', 0],
	'concrete' => ['$n = 2; $f = fn(): int => $n; if ($f) { return isset($f) ? $f() : 9; } return 8;', 2],
	'reference_clean' => ['$f function<int()> = fn() => 2; $clear = function () use (&$f): void { clean($f); }; $clear(); return isset($f);', 0],
	'absent_call' => ['$f function<int()>; $f(); return 99;', 0],
	'ordered_absent_call' => ['class Trace { public static int $n = 0; } function observed(): int { return Trace::$n; } function make(): function<int(int)> { Trace::$n = Trace::$n * 10 + 1; $f function<int(int)>; return $f; } function mark(): int { Trace::$n = Trace::$n * 10 + 2; return 1; } $unused = make()(mark()); $seen = observed(); return $seen + 99;', 0],
];
$executions = [];
try {
	foreach ($cases as $name => [$source, $exit]) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
		$text = CPP_Probe::text(Model::$cpp_files);
		$compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Callable state no-op changed output'); }
		if (($name === 'absent_call') || ($name === 'ordered_absent_call')) {
			// Instrument only the fixture entrypoint to prove the runtime exception and effect order.
			$text = str_replace('int main()', 'int main() try', $text);
			$check = $name === 'absent_call' ? '0' : '(function_observed().native_value() == 12 ? 0 : 9)';
			$end = strrpos($text, '// SCPP-END-ARTIFACT');
			if ($end === false) { $end = strlen($text); }
			$text = substr_replace($text, 'catch (const std::bad_function_call &) { return ' . $check . '; }' . "\n", $end, 0);
		}
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $exit];
	}
	foreach ([
		'$f function<int()>; return empty($f);',
		'$f = fn(): int => 1; return empty($f);',
		'$f = fn(): int => 1; clean($f); return 0;',
		'$f = fn(): int => 1; unset($f); return 0;',
		'$f nullable<function<int()>>; return 0;',
		'$n = 1; $f function<int()>; $f = function () use (&$n): int { return $n; }; return 0;',
	] as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]); $rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid callable state operation accepted: ' . $source); }
	}
	Compiler_Lifecycle::reset(); file_put_contents($path, $cases['absent'][0]);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	file_put_contents($path, $cases['unset_reassign'][0]); $compiler->update_cpp([$path]);
	$updated = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, '$f function<int()>; return empty($f);'); $rejected = false;
	try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Callable emptiness edit accepted'); }
	file_put_contents($path, $cases['unset_reassign'][0]); $compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $updated) { throw new \LogicException('Callable recovery changed output'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $updated) { throw new \LogicException('Fresh/incremental callable output differs'); }
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Empty callable storage: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
