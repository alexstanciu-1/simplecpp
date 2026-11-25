<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory);
$api = $directory . '/api.phs';
$main = $directory . '/main.phs';
$valid = 'class Base {} class Derived extends Base {}';
$client = '$a ?Base = null; $b ?Derived = null; echo $a === $b;';
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($api, $valid);
	file_put_contents($main, $client);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach ([
		[str_replace(' extends Base', '', $valid), 'compatible base/derived'],
	] as [$changed_api, $diagnostic]) {
		file_put_contents($api, $changed_api);
		foreach ([[$api], []] as $changed) {
			$failed = false;
			try { $compiler->sync($changed); $compiler->prepare(); }
			catch (\RuntimeException $error) {
				$failed = str_contains($error->getMessage(), $diagnostic);
				if (!$failed) { throw $error; }
			}
			if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Identity relationship edit retained stale admission/output'); }
		}
		file_put_contents($api, $valid);
		$compiler->update_cpp([$api]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Identity relationship recovery changed output'); }
	}
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare(); $compiler->cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Identity preparation reset changed output'); }
}
echo "Shared identity retained relationships and recovery passed\n";
