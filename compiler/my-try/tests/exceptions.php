<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/exceptions-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$executions = [];
$native_cases = [];
$log = 'class Log { static int $n = 0; } data struct Item { int $id; function __destruct() { $id = $this->id; Log::$n = Log::$n * 10 + $id; } } ';
$cases = [
	'factory_identity' => ['$original = Exception::make("bad"); try { throw $original; } catch (Exception $caught) { $m = $caught->get_message(); return $m === "bad" ? 0 : 3; }', 0],
	'identity_transport' => ['function capture(Exception $e): Exception { try { try { throw $e; } catch (Exception $inner) { throw $inner; } } catch (Exception $outer) { return $outer; } } $e = Exception::make("identity"); $out = capture($e); return 0;', 0],
	'callee' => ['function fail(): int { throw Exception::make("callee"); } try { $n = fail(); return 2; } catch (Exception $e) { $m = $e->get_message(); return $m === "callee" ? 0 : 3; }', 0],
	'nested_rethrow' => ['$original = Exception::make("nested"); try { try { throw $original; } catch (Exception $inner) { throw $inner; } } catch (Exception $outer) { $m = $outer->get_message(); return $m === "nested" ? 0 : 2; }', 0],
	'handler_shadow' => ['$e = 7; try { throw Exception::make("shadow"); } catch (Exception $e) { $m = $e->get_message(); if ($m !== "shadow") { return 2; } } return $e === 7 ? 0 : 3;', 0],
	'normal_return' => ['function go(): int { try { return 7; } catch (Exception $e) { return 8; } } return go() === 7 ? 0 : 2;', 0],
	'canonical_alias' => ['use Exception as Failure; try { throw Failure::make("alias"); } catch (Failure $e) { $m = $e->get_message(); return $m === "alias" ? 0 : 2; }', 0],
	'single_evaluation' => ['function make(int &$n): Exception { $n++; return Exception::make("once"); } $n = 0; try { throw make($n); } catch (Exception $e) { return $n === 1 ? 0 : 2; }', 0],
	'empty_message' => ['$e = Exception::make(""); try { throw $e; } catch (Exception $caught) { $m = $caught->get_message(); return $m === "" ? 0 : 2; }', 0],
	'cleanup_order' => [$log . 'function fail(): void { $a Item = {id: 1}; $b Item = {id: 2}; throw Exception::make("unwind"); } $outer Item = {id: 9}; try { fail(); } catch (Exception $e) { if (Log::$n !== 21) { return 2; } } if (Log::$n !== 21) { return 3; } return 0;', 0],
	'handler_cleanup' => [$log . 'try { try { $a Item = {id: 1}; throw Exception::make("a"); } catch (Exception $e) { $b Item = {id: 2}; throw $e; } } catch (Exception $e) { return Log::$n === 12 ? 0 : 2; }', 0],
	'partial_constructor' => [$log . 'class Broken { Item $a = {id: 1}; Item $b = {id: 2}; function __construct() { Log::$n = 0; throw Exception::make("ctor"); } function __destruct() { Log::$n = 999; } } try { $x = new Broken(); } catch (Exception $e) { return Log::$n === 21 ? 0 : 2; }', 0],
	'catch_lifetime' => ['$e = Exception::make("live"); try { throw $e; } catch (Exception $caught) { $e = null; $m = $caught->get_message(); if ($m !== "live") { return 2; } } return 0;', 0],
	'loop_exits' => [$log . '$n = 0; while ($n < 3) { $n++; try { $a Item = {id: 1}; throw Exception::make("loop"); } catch (Exception $e) { $b Item = {id: 2}; if ($n === 1) { continue; } break; } } return Log::$n === 1212 ? 0 : 2;', 0],
	'uncaught_cleanup' => ['struct Guard { function __destruct() { echo "cleaned"; } } $guard Guard; throw Exception::make("unhandled");', 1, 'cleaned', 'unhandled'],
	'absent_throw' => ['try { $e Exception; throw $e; } catch (Exception $caught) { return 8; } return 9;', 1, '', 'Cannot throw an absent Exception'],
	'guard_bypass' => ['try { $v vector<int> = []; $n = $v[0]; } catch (Exception $caught) { return 8; } return 9;', 1],
];
$rejections = [
	'Exception::make();', 'Exception::make(3);', 'Exception::make("a", "b");',
	'$e = Exception::make("x"); $e->get_message(1);',
	'throw 3;', 'throw error::make("bad");', 'class C {} throw new C();',
	'class Exception {} throw new Exception();',
	'try {} catch (int $e) {}', 'try {} catch (error $e) {}',
	'class Exception {} try {} catch (Exception $e) {}',
	'try {} catch (Exception $e) {} $m = $e->get_message();',
	'try { $local = 1; } catch (Exception $e) { echo $local; }',
	'function fail(): int { try { throw Exception::make("x"); } catch (Exception $e) {} } $n = fail();',
	'try {} catch (Exception $e) {} catch (Exception $f) {}',
	'try {} catch (Exception|error $e) {}', 'throw;', '$x = throw Exception::make("x");',
	'$e = new Exception("x");', 'class Child extends Exception {}',
];
try {
	foreach ($cases as $name => $fixture) {
		Compiler_Lifecycle::reset();
		file_put_contents($path, $fixture[0]);
		$compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->init([$directory]);
		try { $compiler->exec_cpp(); }
		catch (\Throwable $error) { throw new \RuntimeException($name . ': ' . $error->getMessage(), 0, $error); }
		$text = CPP_Probe::text(Model::$cpp_files);
		$native_cases[$name] = ['source' => $fixture[0], 'raw' => $text];
		$compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Exception no-op changed output'); }
		if ($name === 'identity_transport') {
			// Source handle comparison is outside this slice; inspect the emitted callable's pointer identity.
			$probe = 'auto original = scpp::Exception::make(scpp::string_t("identity")); std::weak_ptr<scpp::Exception> weak = original.native_value(); auto captured = function_capture(original); if (original.native_value().get() != captured.native_value().get()) { return 7; } original = scpp::shared_p<scpp::Exception>{}; if (weak.expired()) { return 8; } captured = scpp::shared_p<scpp::Exception>{}; if (!weak.expired()) { return 9; }';
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
		$compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->init([$directory]);
		$rejected = false;
		try { $compiler->sync([]); $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid exception use accepted: ' . $source); }
		if (!Model::$cpp_files->is_empty()) { throw new \LogicException('Rejected exception use published output'); }
	}
	Compiler_Lifecycle::reset();
	file_put_contents($path, $cases['factory_identity'][0]);
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$before = CPP_Probe::text(Model::$cpp_files);
	foreach (['try { throw 3; } catch (Exception $e) {}', 'try { throw Exception::make("x"); } catch (int $e) {}', 'try {} catch (Exception $e) {} catch (Exception $f) {}'] as $bad) {
		file_put_contents($path, $bad);
		$rejected = false;
		try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid exception edit accepted'); }
		file_put_contents($path, $cases['factory_identity'][0]);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Exception repair differs'); }
	}
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Exception fresh output differs'); }
	file_put_contents($output . '/executions.json', json_encode($executions));
	file_put_contents($output . '/native_cases.json', json_encode(['valid' => $native_cases, 'rejected' => $rejections]));
	echo 'Exceptions: ' . count($cases) . ' programs, ' . count($rejections) . " rejections, repair/fresh agreement PASS\n";
}
finally {
	Compiler_Lifecycle::reset();
	unlink($path);
	rmdir($directory);
}
