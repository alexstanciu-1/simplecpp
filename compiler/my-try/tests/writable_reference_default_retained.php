<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Publish through the production retained lifecycle, including ready-state recovery. */
function default_publish(Compiler $compiler, string $path, string $output, bool $initial): void
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
$prefix = 'function f(string &$s="saved"):string{$s.="!";return $s;}';
$explicit_call = $prefix . '$s="live";echo f($s);';
$omitted_call = $prefix . 'echo f();';
file_put_contents($path, $explicit_call);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
default_publish($compiler, $path, $output, true);
$previous = CPP_Probe::text(Model::$cpp_files);
foreach ([$omitted_call, str_replace('"saved"', '"edited"', $omitted_call), $explicit_call, str_replace('string &$s="saved"', 'string &$s', $explicit_call), 'function f(const string &$s="saved"):string{return $s;}echo f();', $omitted_call] as $index => $source) {
	file_put_contents($path, $source);
	default_publish($compiler, $path, $output, false);
	$current = CPP_Probe::text(Model::$cpp_files);
	// Removing an unused default changes admission, not the explicit call artifacts.
	if (($current !== $previous) !== ($index !== 3))
	{
		throw new \LogicException('Default/call edit changed the wrong emitted consumers');
	}
	$previous = $current;
}
$saved = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);
file_put_contents($path, str_replace('function f(', 'function &f(', $omitted_call));
$rejected = false;
try {
	default_publish($compiler, $path, $output, false);
}
catch (\RuntimeException $error) {
	$rejected = str_contains($error->getMessage(), 'Reference return cannot escape omitted default storage');
}
if (!$rejected || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Invalid reference-default accepted');
}
$unready = false;
try {
	Output_Publication::require_ready($output);
}
catch (\RuntimeException $error) {
	$unready = true;
}
if (!$unready) {
	throw new \LogicException('Failure left ready reference-default artifacts');
}
file_put_contents($path, $omitted_call);
default_publish($compiler, $path, $output, false);
if (CPP_Probe::text(Model::$cpp_files) !== $saved || Output_Publication::require_ready($output) !== $manifest) {
	throw new \LogicException('Reference-default recovery changed artifacts');
}
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
default_publish($fresh, $path, $output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $saved) {
	throw new \LogicException('Fresh/retained reference-default differs');
}
echo "Reference-default retained publication passed\n";

// Source reference defaults do not extend provider metadata admission.
foreach (['reference', 'readonly_reference'] as $passing)
{
	$schema = ['version' => 1, 'module' => 'default_probe', 'dependencies' => ['core'],
		'functions' => [['id' => 'read', 'name' => 'read', 'parameters' => [
			['name' => 'n', 'type' => 'int', 'passing' => $passing, 'default' => ['int' => 7]]], 'returns' => 'int']],
		'cpp' => [['id' => 'read', 'symbol' => 'read', 'header' => 'probe.hpp']]];
	$rejected = false;
	try
	{
		Runtime_Catalog_Reader::decode(json_encode($schema), 'provider-default.json');
	}
	catch (\RuntimeException $error)
	{
		$rejected = str_contains($error->getMessage(), 'Runtime reference parameter defaults');
	}
	if (!$rejected)
	{
		throw new \LogicException('Provider reference default escaped its separate admission gate');
	}
}
