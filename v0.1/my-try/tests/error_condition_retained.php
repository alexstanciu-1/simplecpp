<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Publish through the production retained lifecycle, including ready-state recovery. */
function error_condition_publish(Compiler $compiler, string $path, string $output, bool $initial): void
{
	$publication = new Output_Publication($output);
	try {
		if ($initial) {
			$compiler->exec_cpp($publication);
		}
		else {
			$compiler->update_cpp([$path], $publication);
		}
	}
	finally {
		$publication->close();
	}
}
require_once dirname(__DIR__) . '/host/runtime_build.php';
$input = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['core']);
Host_Runtime_Build::resolve($input);
Compiler_Lifecycle::configure_runtime($input);
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$sources = [
	'$e error;echo $e?"present":"absent";',
	'$e=error::make("");echo !$e?"absent":"present";',
	'$e=error::make("saved");$copy=$e;$e=error::$no_error;echo $copy?"present":"absent";',
	'$e=error::$no_error;echo !$e?"absent":"present";',
	'$e=error::make("final");echo $e?"present":"absent";',
];
$expected = ['absent', 'present', 'present', 'absent', 'present'];
file_put_contents($path, $sources[0]);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
$rows = [];
$output = '';
foreach ($sources as $index => $source) {
	file_put_contents($path, $source);
	$output = $directory . '-published-' . $index;
	error_condition_publish($compiler, $path, $output, $index === 0);
	$rows[] = ['published' => $output, 'expected' => $expected[$index]];
}
$saved = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);
$invalid = [
	'$e error;$b bool=$e;',
	'$e error;$b=(bool)$e;',
	'$e error;echo $e===null;',
	'$e error;$x=$e+1;',
];
$messages = ['requires matching types', 'explicit boolean cast', 'requires a nullable value or owning handle', 'S2S'];
$diagnostics = [];
foreach ($invalid as $index => $source) {
	file_put_contents($path, $source);
	$rejected = false;
	try { error_condition_publish($compiler, $path, $output, false); }
	catch (\RuntimeException $error) {
		$diagnostics[] = $error->getMessage();
		$rejected = str_contains(strtolower($error->getMessage()), strtolower($messages[$index]));
	}
	if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid error operation accepted or stale output'); }
	$unready = false;
	try { Output_Publication::require_ready($output); }
	catch (\RuntimeException $error) { $unready = true; }
	if (!$unready) { throw new \LogicException('Invalid error condition left ready artifacts'); }
	file_put_contents($path, $sources[4]);
	error_condition_publish($compiler, $path, $output, false);
	if (CPP_Probe::text(Model::$cpp_files) !== $saved || Output_Publication::require_ready($output) !== $manifest) {
		throw new \LogicException('Error-condition recovery changed artifacts');
	}
}
file_put_contents($directory . '/rejections.json', json_encode($diagnostics, JSON_THROW_ON_ERROR));
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
$fresh_output = $directory . '-fresh';
error_condition_publish($fresh, $path, $fresh_output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $saved) {
	throw new \LogicException('Fresh and retained error conditions differ');
}
$rows[] = ['published' => $fresh_output, 'expected' => 'present'];
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
