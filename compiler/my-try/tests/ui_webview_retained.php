<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once dirname(__DIR__) . '/host/project.php';
require_once __DIR__ . '/cpp_probe.php';

function ui_retained_publish(Compiler $compiler, string $path, string $output, bool $initial): void
{
	$publication = new Output_Publication($output);
	try {
		if ($initial) { $compiler->exec_cpp($publication); }
		else { $compiler->update_cpp([$path], $publication); }
	}
	finally { $publication->close(); }
}

$folder = $argv[1];
$path = $folder . '/source/main.phs';
$original = file_get_contents($path);
if (!is_string($original)) { throw new \RuntimeException('Missing GUI fixture'); }
$edited = str_replace('enabled-ok', 'enabled-edited', $original);
$project = new Project_Compilation($folder . '/source');
$compiler = new Compiler();
$project->initialize($compiler);
if (($argv[2] ?? '') === '--fresh') {
	ui_retained_publish($compiler, $path, $folder . '/retained-fresh', true);
	if (CPP_Probe::text(Model::$cpp_files) !== file_get_contents($folder . '/original-cpp.txt')) {
		throw new \LogicException('GUI fresh artifacts differ');
	}
	echo "fresh-ok\n";
	exit(0);
}
try {
	ui_retained_publish($compiler, $path, $folder . '/retained-original', true);
	$saved = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($folder . '/original-cpp.txt', $saved);
	file_put_contents($path, $edited);
	ui_retained_publish($compiler, $path, $folder . '/retained-edited', false);
	$edited_text = CPP_Probe::text(Model::$cpp_files);
	if ($edited_text === $saved) { throw new \LogicException('GUI body edit was ignored'); }
	file_put_contents($path, 'webview_create(3);');
	$rejected = false;
	try { ui_retained_publish($compiler, $path, $folder . '/retained-edited', false); }
	catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid GUI input retained output'); }
	$unready = false;
	try { Output_Publication::require_ready($folder . '/retained-edited'); }
	catch (\RuntimeException $error) { $unready = true; }
	if (!$unready) { throw new \LogicException('Invalid GUI publication stayed ready'); }
	file_put_contents($path, $edited);
	ui_retained_publish($compiler, $path, $folder . '/retained-edited', false);
	if (CPP_Probe::text(Model::$cpp_files) !== $edited_text) { throw new \LogicException('GUI edit recovery differs'); }
	file_put_contents($path, $original);
	ui_retained_publish($compiler, $path, $folder . '/retained-restored', false);
	if (CPP_Probe::text(Model::$cpp_files) !== $saved) { throw new \LogicException('GUI revert differs'); }
}
finally { file_put_contents($path, $original); }
echo "retained-ok\n";
