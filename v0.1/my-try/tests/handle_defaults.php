<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'class Row {} function read(?Row $value = null): bool { return $value === null; } echo read();';
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $valid);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach ([
		'class Row {} function read(?Row $value = new Row()): void {}' => 'handle parameter defaults require null',
		'class Row {} function read(?Row $value = null): void {} read(null);' => 'ownership boundary requires',
		'class Row {} function read(unique<Row> $value = null): void {}' => 'copy',
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
			throw new \LogicException('Handle default recovery changed output');
		}
	}
}
echo "Handle defaults rejection and recovery passed\n";
