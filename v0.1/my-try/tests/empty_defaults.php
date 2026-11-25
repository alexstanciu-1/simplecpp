<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'function read(vector<int> $value = []): int { return count($value); } echo read();';
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $valid);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach ([
		'function read(vector<int> $value = [1]): void {}' => 'container parameter defaults require contextual []',
		'function read(vector<int> $value = {}): void {}' => 'container parameter defaults require contextual []',
		'function read(vector<int> &$value = []): void {}' => 'reference defaults require canonical scalar types',
		'struct Point { public int $x = 0; } function read(Point $value = []): void {}' => 'source initialization interfaces are deferred',
		'function read(int $value = []): void {}' => 'target does not support initialization shape',
	] as $source => $diagnostic) {
		file_put_contents($path, $source);
		foreach ([[$path], []] as $changed) {
			$failed = false;
			try { $compiler->sync($changed); $compiler->prepare(); }
			catch (\RuntimeException $error) {
				$failed = str_contains($error->getMessage(), $diagnostic);
				if (!$failed) { throw $error; }
			}
			if (!$failed || !Model::$cpp_files->is_empty()) {
				throw new \LogicException('Invalid default admitted or stale output retained');
			}
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) {
			throw new \LogicException('Nullable default recovery changed output');
		}
	}
}
echo "Empty defaults rejection and recovery passed\n";
