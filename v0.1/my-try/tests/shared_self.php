<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$base = 'class S { public int $x = 1; function snapshot(): S { return $this; } } class D extends S {}';
$child = '$s = new D(); $t = $s->snapshot(); echo $t->x;';
$invalid = [
 ['class S { public function __construct() { consume($this); } } function consume(S $v): void {}', 'construction or destruction'],
 ['class S { public function __destruct() { consume($this); } } function consume(S $v): void {}', 'construction or destruction'],
 ['class S { static function bad(): S { return $this; } } S::bad();', 'instance body'],
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
			if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Shared self rejection retained invalid output: ' . $source); }
		}
		file_put_contents($path, $base . $child);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Shared self recovery changed output'); }
	}
	$compiler->update_cpp([]);
	if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Shared self warm replay changed output'); }
}
echo "Shared self lifecycle exclusions and retained recovery passed\n";
