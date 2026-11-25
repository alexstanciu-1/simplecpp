<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Publish through the production retained lifecycle, including ready-state recovery. */
function presence_publish(Compiler $compiler, string $path, string $output, bool $initial): void
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
$input = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['webview']);
Host_Runtime_Build::resolve($input);
Compiler_Lifecycle::configure_runtime($input);
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$sources = [
    '$h ui_app=null;echo $h===null?"absent":"present";',
    '$h ui_app=null;echo null!==$h?"present":"absent";',
    '$h ?int=0;echo $h===null?"absent":"present";',
    'class P{}$h=new P();echo $h!==null?"present":"absent";',
    '$h webview=null;echo $h!==null?"present":"absent";',
];
$expected = ['absent', 'absent', 'present', 'present', 'absent'];
file_put_contents($path, $sources[0]);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
$rows = [];
$output = '';
foreach ($sources as $index => $source) {
	file_put_contents($path, $source);
	$output = $directory . '-published-' . $index;
	presence_publish($compiler, $path, $output, $index === 0);
	$rows[] = ['published' => $output, 'expected' => $expected[$index]];
}
$saved = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);
$invalid = [
    '$h webview=null;echo $h==null;',
    '$h webview=null;echo null!=$h;',
    '$h=1;echo $h===null;',
    'class P{}$h=new P();$w=weak($h);echo $w===null;',
];
$messages = ['requires === or !==', 'requires === or !==', 'requires a nullable value or owning handle', 'weak'];
foreach ($invalid as $index => $source) {
    file_put_contents($path, $source);
    $rejected = false;
    try { presence_publish($compiler, $path, $output, false); }
    catch (\RuntimeException $error) {
        $rejected = str_contains(strtolower($error->getMessage()), strtolower($messages[$index]));
    }
    if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid null comparison accepted or stale output'); }
    $unready = false;
    try { Output_Publication::require_ready($output); }
    catch (\RuntimeException $error) { $unready = true; }
    if (!$unready) { throw new \LogicException('Invalid presence left ready artifacts'); }
    file_put_contents($path, $sources[4]);
    presence_publish($compiler, $path, $output, false);
    if (CPP_Probe::text(Model::$cpp_files) !== $saved || Output_Publication::require_ready($output) !== $manifest) {
        throw new \LogicException('Presence recovery changed artifacts');
    }
}
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
$fresh_output = $directory . '-fresh';
presence_publish($fresh, $path, $fresh_output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $saved) {
	throw new \LogicException('Fresh and retained presence differ');
}
$rows[] = ['published' => $fresh_output, 'expected' => 'absent'];
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
