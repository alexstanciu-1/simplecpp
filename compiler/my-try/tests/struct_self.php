<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$base_path = $directory . '/base.phs';
$base = 'struct S { public int $x = 1; function snapshot(): S { return $this; } }';
$child = '$s = new S(); $t = $s->snapshot(); echo $t->x;';
$invalid = [
 ['struct S { public unique<int> $item; function snapshot(): S { return $this; } }', 'copyable_value'],
 ['struct S { function invoke(): void { borrow($this); } } function borrow(S &$s): void {}', 'cannot borrow'],
 ['struct S { function alias(): void { $other =& $this; } }', 'cannot borrow'],
 ['struct S { function &bad(S &$s): S { return $this; } }', 'reference'],
 ['struct S { function bad(): void { $this = new S(); } }', '$this'],
 ['struct S { static function bad(): S { return $this; } } S::bad();', 'instance body'],
 ['class C { function bad(): C { return $this; } }', 'shared-owner retention'],
 ['abstract struct S { function snapshot(): S { return $this; } }', 'Abstract type'],
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
			if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Struct self rejection retained invalid output: ' . $source); }
		}
		file_put_contents($path, $base . $child);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Struct self recovery changed output'); }
	}
	Compiler_Lifecycle::reset();
	file_put_contents($base_path, $base);
	file_put_contents($path, $child);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($base_path, str_replace('public int $x = 1;', 'public int $x = 1; public unique<int> $item;', $base));
	$failed = false;
	try { $compiler->sync([$base_path]); $compiler->prepare(); }
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'copyable_value');
		if (!$failed) { throw $error; }
	}
	if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Noncopyable self retained output'); }
	file_put_contents($base_path, $base);
	$compiler->update_cpp([$base_path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Struct self recovery changed output'); }
	unlink($base_path);
}
echo "Struct self exclusions and retained recovery passed\n";
