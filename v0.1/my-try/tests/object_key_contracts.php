<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
if (!is_dir($directory)) { mkdir($directory); }
$path = $directory . '/main.phs';
$valid = 'class Row {} $a=new Row();$m hash<int, Row> = [];$m[$a]=1;echo $m[$a];';
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $valid);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach ([
		'struct Row {} $m hash<int, Row> = [];' => 'hashable',
		'class Row {} $m hash<int, weak<Row>> = [];' => 'hashable',
		'class Row {} $m hash<int, unique<Row>> = [];' => 'hashable',
		'class Row {} $m hash<int, value<Row>> = [];' => 'hashable',
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
				throw new \LogicException('Invalid object key admitted or stale output retained');
			}
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) {
			throw new \LogicException('Object key recovery changed output');
		}
	}
}
echo "Object keys rejection and recovery passed\n";
