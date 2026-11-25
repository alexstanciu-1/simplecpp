<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'class B { private int $n = 1; function read_base(): int { return $this->n; } } '
	. 'class C extends B { private int $n = 2; function read_child(): int { return $this->n; } } '
	. '$c = new C(); echo $c->read_base(), $c->read_child();';
$invalid = [
	['class B { private int $n = 1; function read(C $c): int { return $c->n; } } class C extends B { private int $n = 2; }', 'private property'],
	[$valid . 'echo $c->n;', 'private property'],
	['class B { private int $n = 1; } class C extends B { private int $n = 2; } class D extends C { function read(): int { return $this->n; } }', 'private property'],
	['class B { public int $n = 1; } class C extends B { private int $n = 2; }', 'redeclaration'],
	['class B { private int $n = 1; } class C extends B { public int $n = 2; }', 'redeclaration'],
	['class B { protected int $n = 1; } class C extends B { private int $n = 2; }', 'redeclaration'],
	['class B { private static int $n = 1; } class C extends B { private int $n = 2; }', 'redeclaration'],
	['class B { private int $n = 1; } class C extends B { private static int $n = 2; }', 'redeclaration'],
	['class B { private static int $n = 1; } class C extends B { private static int $n = 2; }', 'redeclaration'],
];
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $valid);
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
			if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Private-field rejection retained invalid output'); }
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Private-field recovery changed output'); }
	}
	// Change only the declaring ancestor. The child source and its name pool stay unchanged.
	$base_path = $directory . '/base.phs';
	$base_source = 'class Ancestor { private int $value = 1; function base_value(): int { return $this->value; } }';
	$child_source = 'class Middle extends Ancestor {} class Descendant extends Middle { private int $value = 2; } $child = new Descendant(); echo $child->base_value();';
	Compiler_Lifecycle::reset();
	file_put_contents($base_path, $base_source);
	file_put_contents($path, $child_source);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($base_path, str_replace('private int', 'public int', $base_source));
	$failed = false;
	try { $compiler->sync([$base_path]); $compiler->prepare(); }
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'Inherited member redeclaration');
		if (!$failed) { throw $error; }
	}
	if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Ancestor visibility edit did not invalidate child'); }
	file_put_contents($base_path, $base_source);
	$compiler->update_cpp([$base_path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Ancestor visibility recovery changed output'); }
	unlink($base_path);
}
echo "Private-field access, exclusions and retained recovery passed\n";
