<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/echo-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs';
$cases = [
	'single' => ['echo "hello"; return 0;', 'hello'],
	'scalars' => ['echo "[", true, false, null, -12, ":", 2.5, "]"; return 0;', '[1-12:2.5]'],
	'width' => ['$n int64 = 12; echo $n; return 0;', '12'],
	'ordered' => ['function next(int &$n): int { $n++; echo "p"; return $n; } $n = 0; echo next($n), ":", next($n), ":", $n; return 0;', 'p1:p2:2'],
	'boxed' => ['$m mixed = [1]; $d dynamic = {x: 1}; echo $m, ":", $d; return 0;', 'Array:Object'],
	'wrappers' => ['$n ?int = null; $v ?int = 3; $r result<int> = 4; echo $n, $v, $r; return 0;', '34'],
	'sentinels' => ['$f result_or_false<int> = false_sentinel; $t result_or_bool<int> = true_sentinel; echo "[", $f, $t, "]"; return 0;', '[1]'],
	'closure' => ['$n = 7; $f = function () use ($n): void { echo $n; }; $f(); return 0;', '7'],
	'method' => ['class C { int $n = 8; public function show(): void { echo $this->n; } } $c = new C(); $c->show(); return 0;', '8'],
	'branch' => ['if (false) { echo "bad"; } else if (true) { echo "ok"; } return 0;', 'ok'],
];
$executions = [];
try {
	foreach ($cases as $name => [$source, $stdout]) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->exec_cpp();
		$text = CPP_Probe::text(Model::$cpp_files); $compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Echo no-op changed output'); }
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => 0, 'stdout' => $stdout];
	}
	foreach (['echo;', 'echo 1,;', '$v vector<int> = [1]; echo $v;', '$f function<int()>; echo $f;', 'function f(): void {} echo f();', 'struct S { int $n; } $s = new S(); echo $s;'] as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $rejected = false;
		try { $compiler->exec_cpp(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid echo accepted: ' . $source); }
	}
	Compiler_Lifecycle::reset(); file_put_contents($path, '$v = 7; echo $v; return 0;');
	$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->exec_cpp();
	$before = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, '$v vector<int> = [7]; echo $v; return 0;');
	$failed = false;
	try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $failed = true; }
	if (!$failed) { throw new \LogicException('Unsupported echo edit accepted'); }
	file_put_contents($path, '$v = 7; echo $v; return 0;'); $compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Echo repair differs'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Fresh echo differs'); }
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Echo preparation and recovery: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
