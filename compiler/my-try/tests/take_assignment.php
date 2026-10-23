<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory);
$api = $directory . '/api.phs';
$main = $directory . '/main.phs';
$valid = 'function extract(result<json_node> $wrapped): json_node { $out json_node; $failure error; if (!take($out, $failure, $wrapped)) { throw Exception::make("failed"); } return $out; }';
$client = 'echo "retained";';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . "/runtime/metadata/modules", ["json"]));
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
		[str_replace('!take(', 'take(', $valid), 'read before assignment'],
	] as [$changed_api, $diagnostic]) {
		file_put_contents($api, $changed_api);
		foreach ([[$api], []] as $changed) {
			$failed = false;
			try { $compiler->sync($changed); $compiler->prepare(); }
			catch (\RuntimeException $error) {
				$failed = str_contains($error->getMessage(), $diagnostic);
				if (!$failed) { throw $error; }
			}
			if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Take assignment edit retained stale admission/output'); }
		}
		file_put_contents($api, $valid);
		$compiler->update_cpp([$api]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Take assignment recovery changed output'); }
	}
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare(); $compiler->cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Take assignment preparation reset changed output'); }
}
echo "Take assignment retained edits and recovery passed\n";
