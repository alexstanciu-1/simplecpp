<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/error-values-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$executions = [];
$native_cases = [];
$cases = [
	'defaults' => '$e = error::make("bad"); $m = $e->get_message(); $l = $e->get_line(); $f = $e->get_file(); if (!$e) { return 1; } if ($m !== "bad") { return 2; } if ($l !== 0) { return 3; } return $f === "" ? 0 : 4;',
	'details_copy' => '$e = error::make("bad", 12, "input.phs"); $copy = $e; $e = error::$no_error; $m = $copy->get_message(); $l = $copy->get_line(); $f = $copy->get_file(); if ($e) { return 1; } if (!$copy) { return 2; } if ($m !== "bad") { return 3; } if ($l !== 12) { return 4; } return $f === "input.phs" ? 0 : 5;',
	'empty_message' => '$e = error::make(""); if (!$e) { return 1; } $r result<int> = $e; $v = 9; $out error; if (take($v, $out) = $r) { return 2; } if (!$out) { return 3; } return $v === 9 ? 0 : 4;',
	'line_default' => '$e = error::make("bad", 7); $l = $e->get_line(); $f = $e->get_file(); if ($l !== 7) { return 1; } return $f === "" ? 0 : 2;',
	'no_error_getters' => '$e error; $m = $e->get_message(); $l = $e->get_line(); $f = $e->get_file(); if ($m !== "") { return 1; } if ($l !== 0) { return 2; } return $f === "" ? 0 : 3;',
	'getter_snapshot' => '$e = error::make("old"); $m = $e->get_message(); $e = error::make("new"); return $m === "old" ? 0 : 1;',
	'return_argument' => 'function failure(): result<int> { return error::make("bad", 4); } function read(result<int> $r): int { $v = 8; $e error; if (take($v, $e) = $r) { return 99; } return $e->get_line(); } $a = read(failure()); $b = read(error::make("arg", 5)); return $a === 4 && $b === 5 ? 0 : 1;',
	'assignment_take_clear' => '$r result<int> = 3; $r = error::make("bad"); $copy = $r; $v = 8; $e error; if (take($v, $e) = $copy) { return 1; } if ($v !== 8) { return 2; } if (!$e) { return 3; } $r = 0; if (!take($v, $e, $r)) { return 4; } if ($e) { return 5; } return $v === 0 ? 0 : 6;',
	'ordered_factory' => 'function message(int &$n): string { $n = $n * 10 + 1; return "m"; } function line(int &$n): int { $n = $n * 10 + 2; return 4; } function file_name(int &$n): string { $n = $n * 10 + 3; return "f"; } $n = 0; $e = error::make(message($n), line($n), file_name($n)); return $n === 123 ? 0 : 1;',
	'no_error_construct' => '$e error; $r result<int> = $e; return 99;',
	'no_error_constant' => '$r result<int> = error::$no_error; return 99;',
	'preserve_assignment' => 'function reject(result<int> &$target): void { $e error; $target = $e; } $r result<int> = 7; reject($r); return 99;',
];
foreach (['string' => '"kept"', 'vector<int>' => '[7]', 'mixed' => 'false', 'dynamic' => 'false', 'function<int()>' => 'fn() => 7'] as $type => $value) {
	$cases['payload_' . count($cases)] = '$v ' . $type . ' = ' . $value . '; $r result<' . $type . '> = error::make("bad"); $e error; if (take($v, $e) = $r) { return 1; } return $e ? 0 : 2;';
}
$rejections = [
	'error::make();',
	'error::make(3);',
	'error::make("bad", "line");',
	'error::make("bad", 1, 2);',
	'error::make("bad", 1, "file", 4);',
	'$e error; $e->get_message(1);',
	'error::get_message();',
	'$e = new error("bad");',
	'$r result<error> = error::make("bad");',
	'$r result_or_false<int> = error::make("bad");',
	'$r result_or_bool<int> = error::make("bad");',
	'$e = error::make("bad"); $r =& $e->get_message();',
	'$m = error::make("bad")->get_message();',
];
try {
	foreach ($cases as $name => $source) {
		Compiler_Lifecycle::reset();
		file_put_contents($path, $source);
		$compiler = new Compiler();
		$compiler->init([$directory]);
		try { $compiler->exec_cpp(); }
		catch (\RuntimeException $error) { throw new \RuntimeException($name . ': ' . $error->getMessage()); }
		$text = CPP_Probe::text(Model::$cpp_files);
		$native_cases[$name] = ['source' => $source, 'raw' => $text];
		$compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Error no-op changed output'); }
		if (str_starts_with($name, 'no_error_') && ($name !== 'no_error_getters')) {
			$text = str_replace('int main(int argc, char** argv)', 'int main(int argc, char** argv) try', $text);
			$end = strrpos($text, '// SCPP-END-ARTIFACT');
			if ($end === false) { $end = strlen($text); }
			$text = substr_replace($text, 'catch (const std::invalid_argument &) { return 0; }' . "\n", $end, 0);
		}
		if ($name === 'preserve_assignment') {
			// Exercise the emitted assignment under a native harness; source catch is not enabled.
			$probe = 'scpp::result<scpp::int_t<>> kept{scpp::int_t<>(7)}; try { function_reject(kept); } catch (const std::invalid_argument &) { return kept.require_value("proof").native_value() == 7 ? 0 : 1; } return 2;';
			$text = preg_replace('/(int main\(int argc, char\*\* argv\)\s*\{)/', '$1' . "\n" . $probe . "\n", $text, 1, $count);
			if ($count !== 1) { throw new \LogicException('Missing generated entrypoint'); }
		}
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => 0];
	}
	foreach ($rejections as $source) {
		Compiler_Lifecycle::reset();
		file_put_contents($path, $source . ' return 0;');
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$rejected = false;
		try { $compiler->sync([]); $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid error use accepted: ' . $source); }
		if (!Model::$cpp_files->is_empty()) { throw new \LogicException('Rejected error use published output'); }
	}
	Compiler_Lifecycle::reset();
	file_put_contents($path, $cases['defaults']);
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$before = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, '$e = error::make(3);');
	$rejected = false;
	try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Invalid factory edit accepted'); }
	file_put_contents($path, $cases['defaults']);
	$compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Error repair differs'); }
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Error fresh output differs'); }
	file_put_contents($output . '/executions.json', json_encode($executions));
	file_put_contents($output . '/native_cases.json', json_encode(['valid' => $native_cases, 'rejected' => $rejections]));
	echo 'Structured errors: ' . count($cases) . ' programs, ' . count($rejections) . " rejections, repair/fresh agreement PASS\n";
}
finally {
	Compiler_Lifecycle::reset();
	unlink($path);
	rmdir($directory);
}
