<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/callable-results-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs'; $executions = [];
$cases = [
	'factory' => ['function make(): result<function<int(int)>> { $f function<int(int)> = fn($n) => $n + 1; return $f; } $f function<int(int)>; $e error; if (take($f, $e) = make()) { return $f(4); } return 99;', 5],
	'copy' => ['$f function<int()> = fn() => 4; $r result<function<int()>> = $f; $s = $r; clean($f); $g function<int()> = $s; return $g();', 4],
	'absent_success' => ['$f function<int()>; $r result<function<int()>> = $f; $g function<int()> = fn() => 9; $e error; if (!take($g, $e, $r)) { return 1; } if (isset($g)) { return 2; } if ($e) { return 3; } return 0;', 0],
	'argument' => ['function use_result(result<function<int()>> $r): int { $f function<int()> = $r; return $f(); } $f function<int()> = fn() => 6; return use_result($f);', 6],
	'reference' => ['$f function<int&(int&)> = fn&(&$n) => $n; $r result<function<int&(int&)>> = $f; $g function<int&(int&)> = $r; $n = 2; $a =& $g($n); $a++; return $n;', 3],
	'variadic' => ['$f function<int(int...)> = fn(...$n) => $n[1]; $r result<function<int(int...)>> = $f; $g function<int(int...)>; $e error; if (take($g, $e) = $r) { return $g(1, 7); } return 99;', 7],
	'capture' => ['$n = 4; $f function<int()> = fn() => $n; $r result<function<int()>> = $f; $n = 8; $g function<int()> = $r; return $g();', 4],
	'false_success' => ['$f function<int()>; $r result_or_false<function<int()>> = $f; $g function<int()>; if (take($g) = $r) { return isset($g) ? 1 : 0; } return 2;', 0],
	'false_failure' => ['$r result_or_false<function<int()>> = false_sentinel; $g function<int()> = fn() => 7; if (take($g) = $r) { return 1; } return $g();', 7],
	'bool_success' => ['$f function<int()> = fn() => 5; $r result_or_bool<function<int()>> = $f; $g function<int()>; $state = false; if (take($g, $state) = $r) { return $g(); } return 99;', 5],
	'bool_sentinel' => ['$r result_or_bool<function<int()>> = true_sentinel; $g function<int()> = fn() => 7; $state = false; if (!take($g, $state, $r)) { return 1; } return $state ? $g() : 2;', 7],
	'absent_call' => ['$f function<int()>; $r result<function<int()>> = $f; $g function<int()> = $r; return $g();', 0],
	'failed_extract' => ['$r result_or_false<function<int()>> = false_sentinel; $g function<int()> = $r; return 99;', 0],
];
try {
	foreach ($cases as $name => [$source, $exit]) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]);
		try { $compiler->exec_cpp(); } catch (\RuntimeException $error) { throw new \RuntimeException($name . ': ' . $error->getMessage()); }
		$text = CPP_Probe::text(Model::$cpp_files); $compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Callable Result no-op differs'); }
		if (($name === 'absent_call') || ($name === 'failed_extract')) {
			$text = str_replace('int main()', 'int main() try', $text);
			$exception = $name === 'absent_call' ? 'std::bad_function_call' : 'std::exception';
			$end = strrpos($text, '// SCPP-END-ARTIFACT');
			if ($end === false) { $end = strlen($text); }
			$text = substr_replace($text, 'catch (const ' . $exception . ' &) { return 0; }' . "\n", $end, 0);
		}
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $exit];
	}
	foreach ([
		'$r result<function<int()>> = fn(): int => 1;',
		'$n = 1; $f function<int()> = function () use (&$n): int { return $n; }; $r result<function<int()>> = $f;',
		'$f nullable<function<int()>>;',
		'$f shared<function<int()>>;',
		'$f function<int()> = fn() => 1; $r result<function<string()>> = $f;',
		'$f function<int()>; $r result<function<int()>> = $f; $g function<string()> = $r;',
		'$f function<int()>; $r result<function<int()>> = $f; $s result_or_false<function<int()>> = $r;',
		'$f function<int()>; $r result<function<int()>> = $f; $s = true ? $r : $r;',
		'$f function<int()>; $r result<function<int()>> = $f; $s = $r ?? $f;',
		'$f function<int()>; $r result<function<int()>> = $f; if ($r) {}',
		'$f function<int()>; $r result<function<int()>> = $f; isset($r);',
		'$r result<function<int()>>;',
	] as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source . ' return 0;');
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]); $rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid callable Result accepted: ' . $source); }
	}
	$source = $cases['factory'][0]; Compiler_Lifecycle::reset(); file_put_contents($path, $source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp(); $before = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, str_replace('result<function<int(int)>>', 'result<function<string(int)>>', $source)); $rejected = false;
	try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Callable Result signature edit accepted'); }
	file_put_contents($path, $source); $compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Callable Result repair differs'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Callable Result fresh output differs'); }
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Callable Results: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
