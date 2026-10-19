<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/finally-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$executions = [];
$native_cases = [];
$log = 'class Log { static int $n = 0; } data struct Item { int $id; function __destruct() { $id = $this->id; Log::$n = Log::$n * 10 + $id; } } ';
$cases = [
	'normal' => ['$n = 0; try { $n = 1; } finally { $n = $n * 10 + 2; } return $n === 12 ? 0 : 1;', 0],
	'always_throw' => ['function f(): int { try {} finally { throw Exception::make("F"); } } try { $n = f(); } catch (Exception $e) { return 0; } return 1;', 0],
	'return_evaluation' => ['function next(int &$n): int { $n++; return $n; } function f(int &$n): int { try { return next($n); } finally { $n = $n * 10 + 2; } } $n = 0; $v = f($n); return $v === 1 && $n === 12 ? 0 : 1;', 0],
	'return_failure_caught' => ['function fail(): int { throw Exception::make("x"); } function f(): int { try { return fail(); } catch (Exception $e) { return 7; } finally { echo "F"; } } return f() === 7 ? 0 : 1;', 0, 'F'],
	'nested_return_replaced' => ['function f(): int { try { try { return 7; } finally { throw Exception::make("x"); } } catch (Exception $e) { return 9; } finally { echo "F"; } } return f() === 9 ? 0 : 1;', 0, 'F'],
	'cleanup_finalizer_throw' => [$log . 'function f(): void { $outer Item = {id: 9}; try { $a Item = {id: 1}; throw Exception::make("old"); } finally { $b Item = {id: 2}; throw Exception::make("new"); } } try { f(); } catch (Exception $e) { $m = $e->get_message(); return $m === "new" && Log::$n === 129 ? 0 : 1; }', 0],
	'finally_in_finally' => ['try { throw Exception::make("original"); } finally { try { echo "1"; } finally { echo "2"; } }', 1, '12', 'original'],
	'empty' => ['try {} finally {} try {} catch (Exception $e) {} finally {} return 0;', 0],
	'snapshot' => ['function f(): int { $n = 7; try { return $n; } finally { $n = 99; } } return f() === 7 ? 0 : 1;', 0],
	'string_snapshot' => ['function f(): string { $s = "old"; try { return $s; } finally { $s = "new"; } } return f() === "old" ? 0 : 1;', 0],
	'float_bool' => ['function f(): float { try { return 2.5; } finally {} } function b(): bool { try { return false; } finally {} } echo f(); return !b() ? 0 : 1;', 0, '2.5'],
	'void_forward' => ['class Log { static int $n = 0; } function mark(): void { Log::$n++; } function f(): void { try { return mark(); } finally { Log::$n = Log::$n * 10 + 2; } Log::$n = 999; } f(); return Log::$n === 12 ? 0 : 1;', 0],
	'void_bare' => ['function f(): void { try { return; } finally { echo "F"; } echo "BAD"; } f(); return 0;', 0, 'F'],
	'entry_return' => ['try { return 7; } finally { echo "F"; }', 7, 'F'],
	'entry_bare' => ['try { return; } finally { echo "F"; }', 0, 'F'],
	'catch_return' => ['function f(): int { try { throw Exception::make("x"); } catch (Exception $e) { return 7; } finally { echo "F"; } } return f() === 7 ? 0 : 1;', 0, 'F'],
	'catch_throw' => ['try { try { throw Exception::make("old"); } catch (Exception $e) { throw Exception::make("new"); } finally { echo "F"; } } catch (Exception $e) { $m = $e->get_message(); return $m === "new" ? 0 : 1; }', 0, 'F'],
	'replace_throw' => ['try { try { throw Exception::make("old"); } finally { throw Exception::make("new"); } } catch (Exception $e) { $m = $e->get_message(); return $m === "new" ? 0 : 1; }', 0],
	'replace_return' => ['function f(): int { try { return 7; } finally { throw Exception::make("new"); } } try { $n = f(); } catch (Exception $e) { $m = $e->get_message(); return $m === "new" ? 0 : 1; } return 2;', 0],
	'finalizer_not_own_catch' => ['try { try {} catch (Exception $e) { echo "BAD"; } finally { throw Exception::make("F"); } } catch (Exception $e) { echo "outer"; } return 0;', 0, 'outer'],
	'nested_returns' => ['function f(): int { try { try { return 7; } finally { echo "inner"; } echo "BAD"; } finally { echo "outer"; } } return f() === 7 ? 0 : 1;', 0, 'innerouter'],
	'nested_throw' => ['try { try { try { throw Exception::make("x"); } finally { echo "1"; } } finally { echo "2"; } } catch (Exception $e) { echo "3"; } return 0;', 0, '123'],
	'cleanup_return' => [$log . 'function f(): int { $outer Item = {id: 9}; try { $a Item = {id: 1}; $b Item = {id: 2}; return 7; } finally { echo Log::$n; $c Item = {id: 3}; } } $n = f(); return $n === 7 && Log::$n === 2139 ? 0 : 1;', 0, '21'],
	'cleanup_catch' => [$log . 'try { try { $a Item = {id: 1}; throw Exception::make("x"); } catch (Exception $e) { $b Item = {id: 2}; throw $e; } finally { echo Log::$n; $c Item = {id: 3}; } } catch (Exception $e) { return Log::$n === 123 ? 0 : 1; }', 0, '12'],
	'for_return' => ['class Log { static int $n = 0; } function f(): int { try { for ($i = 0; $i < 3; Log::$n++) { return 7; } return 8; } finally { Log::$n = Log::$n * 10 + 2; } } $n = f(); return $n === 7 && Log::$n === 2 ? 0 : 1;', 0],
	'local_transfers' => ['$n = 0; try { while ($n < 4) { $n++; if ($n === 1) { continue; } break; } switch ($n) { case 2: $n = 3; break; default: $n = 99; } } finally { while ($n < 5) { $n++; if ($n === 4) { continue; } break; } } return $n === 5 ? 0 : 1;', 0],
	'closure_isolation' => ['$n = 0; try { $f = function (): int { return 3; }; $n = $f(); } finally { $g = function (): int { try { return 4; } finally {} }; $n = $n + $g(); } return $n === 7 ? 0 : 1;', 0],
	'closure_finally' => ['$f = function (): int { try { return 7; } finally { echo "F"; } }; return $f() === 7 ? 0 : 1;', 0, 'F'],
	'method_finally' => ['class C { int $n = 7; function f(): int { try { return $this->n; } finally { $this->n = 9; } } } $c = new C(); $n = $c->f(); return $n === 7 && $c->n === 9 ? 0 : 1;', 0],
	'unsupported_return_outside' => ['function f(): vector<int> { try { echo "F"; } finally {} return [7]; } $v = f(); return $v[0] === 7 ? 0 : 1;', 0, 'F'],
	'reference_outside' => ['function &f(int &$n): int { try {} finally {} return $n; } $n = 7; $alias =& f($n); $alias = 9; return $n === 9 ? 0 : 1;', 0],
	'guard_propagation' => ['try { $v vector<int> = []; $n = $v[0]; } finally { echo "F"; } return 8;', 1, 'F'],
	'return_conversion_failure' => ['function f(): int { $r result<int> = error::make("bad"); try { return $r; } finally { echo "F"; } } return f();', 1, 'F'],
	'identity_transport' => ['function capture(Exception $e): Exception { try { throw $e; } finally { echo "F"; } } $e = Exception::make("identity"); try { $out = capture($e); } catch (Exception $caught) {} return 0;', 0, 'FF'],
];
$rejections = [
	'try {}', 'try {} finally {} finally {}', 'try {} finally {} catch (Exception $e) {}',
	'try {} finally { return 1; }',
	'while (true) { try { break; } finally {} }',
	'while (true) { try { continue; } finally {} }',
	'while (true) { try {} catch (Exception $e) { break; } finally {} }',
	'while (true) { try {} finally { break; } }',
	'while (true) { try {} finally { continue; } }',
	'try {} finally { try { return 1; } finally {} }',
	'try { while (true) { try { break; } finally {} } } finally {}',
	'function &f(int &$n): int { try { return $n; } finally {} } $n = 1; $a =& f($n);',
	'function f(): vector<int> { try { return [1]; } finally {} } $v = f();',
	'function f(): Exception { try { return Exception::make("x"); } finally {} } $v = f();',
	'function f(): result<int> { try { return 1; } finally {} } $v = f();',
	'try { $n = 7; } finally { echo $n; }',
	'try {} catch (Exception $e) {} finally { $m = $e->get_message(); }',
	'try {} finally { $n = 7; } echo $n;',
	'function f(): int { try {} finally {} } $n = f();',
];

try {
	foreach ($cases as $name => $fixture) {
		Compiler_Lifecycle::reset();
		file_put_contents($path, $fixture[0]);
		$compiler = new Compiler();
		$compiler->init([$directory]);
		try { $compiler->exec_cpp(); }
		catch (\Throwable $error) { throw new \RuntimeException($name . ': ' . $error->getMessage(), 0, $error); }
		$text = CPP_Probe::text(Model::$cpp_files);
		$native_cases[$name] = ['source' => $fixture[0], 'raw' => $text];
		$compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Finally no-op changed output'); }
		if ($name === 'identity_transport') {
			// Inspect transport identity across the emitted finally before the source probe runs.
			$probe = 'auto original = scpp::Exception::make(scpp::string_t("identity")); try { function_capture(original); return 8; } catch (const scpp::php::thrown_object& caught) { if (caught.value().native_value().get() != original.native_value().get()) { return 9; } }';
			$text = str_replace('// SCPP-ENTRY-TRY', $probe . "\n// SCPP-ENTRY-TRY", $text);
		}
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$execution = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $fixture[1]];
		if (isset($fixture[2])) { $execution['stdout'] = $fixture[2]; }
		if (isset($fixture[3])) { $execution['stderr_contains'] = $fixture[3]; }
		$executions[] = $execution;
	}
	foreach ($rejections as $source) {
		Compiler_Lifecycle::reset();
		file_put_contents($path, $source . ' return 0;');
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$rejected = false;
		try { $compiler->sync([]); $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid finally use accepted: ' . $source); }
		if (!Model::$cpp_files->is_empty()) { throw new \LogicException('Rejected finally use published output'); }
	}
	Compiler_Lifecycle::reset();
	file_put_contents($path, $cases['snapshot'][0]);
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$before = CPP_Probe::text(Model::$cpp_files);
	foreach (['try {} finally { return 1; }', 'while (true) { try { break; } finally {} }', 'try {} finally {} finally {}'] as $bad) {
		file_put_contents($path, $bad);
		$rejected = false;
		try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid finally edit accepted'); }
		file_put_contents($path, $cases['snapshot'][0]);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Finally repair differs'); }
	}
	foreach (['function f(): int { try { return 8; } finally { echo "edited"; } } return f();', 'try {} finally {} return 0;'] as $changed) {
		file_put_contents($path, $changed);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) === $before) { throw new \LogicException('Finally edit reused stale output'); }
		file_put_contents($path, $cases['snapshot'][0]);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Finally valid-edit repair differs'); }
	}
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Finally fresh output differs'); }
	file_put_contents($output . '/executions.json', json_encode($executions));
	file_put_contents($output . '/native_cases.json', json_encode(['valid' => $native_cases, 'rejected' => $rejections]));
	echo 'Finally: ' . count($cases) . ' programs, ' . count($rejections) . " rejections, repair/fresh agreement PASS\n";
}
finally {
	Compiler_Lifecycle::reset();
	unlink($path);
	rmdir($directory);
}
