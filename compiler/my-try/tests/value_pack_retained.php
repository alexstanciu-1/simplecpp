<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Publish through the production retained lifecycle, including ready-state recovery. */
function pack_publish(Compiler $compiler, string $path, string $output, bool $initial): void
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
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$output = $directory . '-published';
$prefix = 'function f(int ...$xs):int{$n=0;foreach($xs as $x){$n+=$x;}return $n;}';
$empty_call = $prefix . 'echo f();';
$nonempty_call = $prefix . 'echo f(3,6);';
file_put_contents($path, $empty_call);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
pack_publish($compiler, $path, $output, true);
foreach ([$nonempty_call, $empty_call, $prefix . 'echo f(1);', $nonempty_call] as $source) {
	file_put_contents($path, $source);
	pack_publish($compiler, $path, $output, false);
	$text = CPP_Probe::text(Model::$cpp_files);
	$has_buffer = str_contains($text, 'runtime_initialize_buffer_');
	if ($has_buffer !== ($source !== $empty_call)) {
		throw new \LogicException('Pack helper demand/retirement mismatch');
	}
}
$saved = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);
file_put_contents($path, str_replace('int ...$xs', 'const int &...$xs', $nonempty_call));
$rejected = false;
try {
	pack_publish($compiler, $path, $output, false);
}
catch (\RuntimeException $error) {
	$rejected = true;
}
if (!$rejected || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Invalid pack accepted');
}
$unready = false;
try {
	Output_Publication::require_ready($output);
}
catch (\RuntimeException $error) {
	$unready = true;
}
if (!$unready) {
	throw new \LogicException('Failure left ready pack artifacts');
}
file_put_contents($path, $nonempty_call);
pack_publish($compiler, $path, $output, false);
if (CPP_Probe::text(Model::$cpp_files) !== $saved || Output_Publication::require_ready($output) !== $manifest) {
	throw new \LogicException('Pack recovery changed artifacts');
}
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
pack_publish($fresh, $path, $output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $saved) {
	throw new \LogicException('Fresh/retained pack differs');
}
echo "Pack retained publication passed\n";
