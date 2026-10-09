<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/callable-inputs-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs';
$cases = [
	'reference' => ['$f function<void(int&)> = function (int &$n): void { $n++; }; $n = 4; $f($n); return $n;', 5],
	'contextual_reference' => ['$f function<void(int&)> = function (&$n) { $n += 2; }; $n = 4; $f($n); return $n;', 6],
	'readonly' => ['$f function<int(const int&)> = fn(const int &$n): int => $n; $n = 7; return $f($n);', 7],
	'contextual_readonly' => ['$f function<int(const int&)> = fn(const &$n) => $n; $n = 7; return $f($n);', 7],
	'variadic' => ['$f function<int(int...)> = function (int ...$values): int { $sum = 0; foreach ($values as $n) { $sum += $n; } return $sum; }; return $f(1, 2, 3);', 6],
	'contextual_pack' => ['$f function<int(int...)> = function (...$values) { $sum = 0; foreach ($values as $n) { $sum += $n; } return $sum; }; return $f(2, 3);', 5],
	'empty_pack' => ['$f function<int(int...)> = fn(...$values) => empty($values) ? 0 : 1; return $f();', 0],
	'combined' => ['$f function<void(int&, int...)> = function (&$sum, ...$values) { foreach ($values as $n) { $sum += $n; } }; $n = 1; $f($n, 2, 3); return $n;', 6],
	'argument' => ['function apply(function<void(int&)> $f, int &$n): void { return $f($n); } $n = 3; apply(function (&$v) { $v++; }, $n); return $n;', 4],
	'factory' => ['function make(): function<void(int&)> { return function (&$n) { $n += 2; }; } $f = make(); $g = $f; clean($f); $n = 4; $g($n); return $n;', 6],
	'container' => ['$items vector<function<void(int&)>> = [function (&$n) { $n++; }]; $n = 8; $items[0]($n); return $n;', 9],
	'pack_container' => ['$items hash<function<int(int...)>> = {size: fn(...$n) => $n[1]}; return $items["size"](1, 2);', 2],
];
foreach (['class', 'struct'] as $kind) {
	$cases[$kind] = [$kind . ' C { public function<void(int&)> $f; public static function<int(int...)> $sum; } $c = new C(); $c->f = function (&$n) { $n++; }; C::$sum = fn(...$v) => $v[1]; $n = 3; ($c->f)($n); $count = (C::$sum)(1, 2); return $n + $count;', 6];
}
$executions = [];
try {
	foreach ($cases as $name => [$source, $exit]) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]);
		try { $compiler->exec_cpp(); } catch (\RuntimeException $error) { throw new \RuntimeException($name . ': ' . $error->getMessage()); }
		$text = CPP_Probe::text(Model::$cpp_files); $compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Callable input no-op changed output'); }
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $exit];
	}
	foreach ([
		'$f function<void(int&)> = function ($n) {}; return 0;',
		'$f function<int(const int&)> = fn(&$n) => $n; return 0;',
		'$f function<void(int&)> = function (&$n) {}; $f(1); return 0;',
		'$f function<int(const int&)> = function (const &$n) { $n++; return $n; }; return 0;',
		'$f function<int(int...)> = fn(vector<int> $v): int => 1; return 0;',
		'$f function<int(vector<int>)> = fn(int ...$v): int => 1; return 0;',
		'$f function<int(int..., int)>; return 0;',
		'$f function<int(int&...)>; return 0;',
		'$f function<int(void&)>; return 0;',
		'$f function<int(int)> = fn(int $n = 1): int => $n; return 0;',
		'$f function<int(int&)> = fn&(int &$n): int => $n; return 0;',
		'$n = 1; $f function<void(int&)> = function (int &$v) use (&$n): void { $v = $n; }; return 0;',
	] as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]); $rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid callable input accepted: ' . $source); }
	}
	Compiler_Lifecycle::reset();
	$provider = $directory . '/api.phs';
	$api = 'function apply(function<void(int&)> $f, int &$n): void { return $f($n); }';
	file_put_contents($provider, $api);
	file_put_contents($path, '$n = 1; apply(function (&$n) { $n += 4; }, $n); return $n;');
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$before = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($provider, str_replace('function<void(int&)>', 'function<void(int)>', $api)); $rejected = false;
	try { $compiler->update_cpp([$provider]); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Reference mode edit accepted'); }
	file_put_contents($provider, $api); $compiler->update_cpp([$provider]);
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Reference mode recovery differs'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Fresh reference mode output differs'); }
	file_put_contents($output . '/recovery.cpp', $before);
	$executions[] = ['path' => $output . '/recovery.cpp', 'exit_code' => 5];
	unlink($provider);
	$registry = Model::$type_catalog->registry(); $integer = Language_Types::integer(Model::$language_scope);
	$input = new callable_parameter(); $input->type = $integer; $input->mode = passing_mode::value;
	$value_contract = $registry->define_callable($integer, [$input]);
	$input->mode = passing_mode::reference;
	$reference_contract = $registry->define_callable($integer, [$input]);
	$input->access = storage_access::read_only;
	$readonly_contract = $registry->define_callable($integer, [$input]);
	if (($value_contract === $reference_contract) || ($readonly_contract === $reference_contract)) {
		throw new \LogicException('Callable modes collapsed in registry');
	}
	$vector = $registry->intern_application(Model::$type_catalog->definition('vector'), [$integer]);
	$input->type = $registry->use($vector->type_id()); $input->mode = passing_mode::value; $input->access = storage_access::writable;
	$vector_contract = $registry->define_callable($integer, [$input]);
	$input->element_type = $integer;
	$pack_contract = $registry->define_callable($integer, [$input]);
	if (($vector_contract === $pack_contract) || ($registry->define_callable($integer, [$input]) !== $pack_contract)) {
		throw new \LogicException('Callable packing identity is not exact');
	}
	$stored_inputs = $reference_contract->signature()->parameters;
	if (($stored_inputs[0]->mode !== passing_mode::reference) || ($stored_inputs[0]->access !== storage_access::writable)) {
		throw new \LogicException('Callable interning retained mutable input records');
	}
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Callable reference/variadic inputs: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
