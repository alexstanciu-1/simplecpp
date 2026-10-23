<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$base_path = $directory . '/base.phs';
$base = 'class B { #[\Virtual] public function value(): int { return 1; } }';
$child = 'class C extends B { #[\Override] public function value(): int { return 2; } } $c B = new C(); echo $c->value();';
$invalid = [
	['class B { #[\Virtual] public function __construct() {} }', 'supported modifiers'],
	['class B { #[\Virtual] public int $value = 1; }', 'supported modifiers'],
	[$base . 'class C extends B { public function value(): string { return "bad"; } }', 'compatible instance signature'],
	['class B { #[\Virtual] private function value(): int { return 1; } }', 'Virtual requires'],
	['class B { #[\Virtual] public static function value(): int { return 1; } }', 'Virtual requires'],
	['class B { #[\Virtual] public final function value(): int { return 1; } }', 'Virtual requires'],
	['abstract class B { #[\Virtual] public abstract function value(): int; }', 'Virtual requires'],
	['interface B { #[\Virtual] function value(): int; }', 'Virtual requires'],
	['class B { #[\Virtual] public function __destruct() {} }', 'Virtual requires'],
	['class B { #[\Virtual] #[\Override] public function value(): int { return 1; } }', 'Virtual requires'],
	['class B { #[\Virtual] #[\Virtual] public function value(): int { return 1; } }', 'Duplicate Virtual'],
	['class B { #[\Override] public function value(): int { return 1; } }', 'Override assertion'],
	[$base . 'class C extends B { #[\Virtual] public function value(): int { return 2; } }', 'Virtual starts'],
	['interface I { function value(): int; } class B implements I { #[\Virtual] public function value(): int { return 1; } }', 'Virtual starts'],
	[$base . 'class C extends B { #[\Override] public final function value(): int { return 2; } } class D extends C { public function value(): int { return 3; } }', 'final method'],
];
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $base . $child);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach ($invalid as [$source, $message]) {
		file_put_contents($path, $source);
		foreach ([[$path], []] as $changed) {
			$failed = false;
			try { $compiler->sync($changed); $compiler->prepare(); }
			catch (\RuntimeException $error) {
				$failed = str_contains($error->getMessage(), $message);
				if (!$failed) { throw $error; }
			}
			if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Virtual rejection retained invalid output'); }
		}
		file_put_contents($path, $base . $child);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Virtual recovery changed output'); }
	}
	Compiler_Lifecycle::reset();
	file_put_contents($base_path, $base);
	file_put_contents($path, $child);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($base_path, str_replace('#[\Virtual]', '', $base));
	$failed = false;
	try { $compiler->sync([$base_path]); $compiler->prepare(); }
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'Nonvirtual inherited');
		if (!$failed) { throw $error; }
	}
	if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Virtual ancestor edit did not invalidate child'); }
	file_put_contents($base_path, $base);
	$compiler->update_cpp([$base_path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Virtual ancestor recovery changed output'); }
	unlink($base_path);
}
echo "Virtual exclusions and retained recovery passed\n";
