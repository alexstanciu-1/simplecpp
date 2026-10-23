<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'function read(vessel<int> $value = []): void {} read();';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[2], ['empty_proof']));
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $valid);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach (['$value vessel<int> = [1];', '$value vessel<int> = {};'] as $invalid) {
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changed) {
			$failed = false;
			try { $compiler->sync($changed); $compiler->prepare(); }
			catch (\RuntimeException $error) {
				$failed = str_contains($error->getMessage(), 'Registered empty-only initialization accepts [] only');
				if (!$failed) { throw $error; }
			}
			if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid initialization admitted or stale output retained'); }
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Empty initialization recovery changed output'); }
	}
}
echo "Empty-only rejection and recovery passed\n";
