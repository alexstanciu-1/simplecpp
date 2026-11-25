<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/void-returns-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs';
$prefix = 'class Log { public static int $n = 0; } function mark(): void { Log::$n++; } ';
$cases = [
	'function' => 'function forward(): void { return mark(); Log::$n = 99; } forward();',
	'closure' => '$f = function (): void { return mark(); }; $f();',
	'arrow' => '$f = fn(): void => mark(); $f();',
	'contextual' => '$f function<void()> = fn() => mark(); $f();',
	'argument' => 'function apply(function<void()> $f): void { return $f(); } apply(fn() => mark());',
	'factory' => 'function factory(): function<void()> { return fn() => mark(); } factory()();',
	'chain' => 'function first(): void { return mark(); } function second(): void { return first(); } second();',
	'branch' => 'function forward(bool $yes): void { if ($yes) { return mark(); } return; } forward(false); forward(true);',
	'borrow' => '$n = 0; function bump(int &$n): void { $n++; } $f = function () use (&$n): void { return bump($n); }; $f(); Log::$n = $n;',
];
foreach (['class', 'struct'] as $kind) {
	$cases[$kind] = $kind . ' C { public function forward(): void { return mark(); } } $c = new C(); $c->forward();';
	$cases[$kind . '_static'] = $kind . ' C { public static function forward(): void { return mark(); } } C::forward();';
	$cases[$kind . '_receiver'] = $kind . ' C { public function action(): void { return mark(); } public function forward(): void { $f = fn(): void => $this->action(); return $f(); } } $c = new C(); $c->forward();';
}
$executions = [];
try {
	foreach ($cases as $name => $body) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $prefix . $body . ' return Log::$n;');
		$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->exec_cpp();
		$text = CPP_Probe::text(Model::$cpp_files); $compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Void return no-op changed output'); }
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => 1];
	}
	foreach ([
		'function f(): void { return 1; } return 0;',
		'$f = fn(): void => 1; return 0;',
		'$f function<void()> = fn() => 1; return 0;',
		'function noop(): void {} function f(): int { return noop(); } return 0;',
		'function noop(): void {} return noop();',
		'function noop(): void {} $n = noop(); return 0;',
		'function f(): int { return; } return 0;',
		'class C { public function f(): void { return 1; } } return 0;',
	] as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->sync([]); $rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid void return accepted: ' . $source); }
	}
	Compiler_Lifecycle::reset();
	$provider = $directory . '/api.phs'; file_put_contents($provider, 'function action(): void {}');
	file_put_contents($path, 'function forward(): void { return action(); } forward(); return 0;');
	$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$before = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($provider, 'function action(): int { return 1; }'); $rejected = false;
	try { $compiler->update_cpp([$provider]); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Return type change failed to invalidate void forwarding'); }
	file_put_contents($provider, 'function action(): void {}'); $compiler->update_cpp([$provider]);
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Void return recovery differs'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Fresh void return output differs'); }
	unlink($provider);
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Shared void-return forwarding: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
