<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory, 0777, true);
$path = $directory . '/main.phs';
$valid = 'use Exception as RootFailure; class Base extends RootFailure {} class Child extends Base {}
function widen(Child $e): Exception { return $e; }
try { throw new Child(); } catch (Base $e) {}';
$compiler = new Compiler(new cpp_lowering_options(false));
$compiler->init([$directory]);
$cases = [
	'shadow' => ['class Exception {} throw new Exception();', 'Throw and catch require'],
	'unrelated' => ['class Other {} try {} catch (Other $e) {}', 'Throw and catch require'],
	'struct' => ['struct Bad extends Exception {}', 'Exception inheritance requires'],
	'runtime base' => ['class Bad extends string {}', 'Relationship target must'],
	'absent literal' => ['throw null;', 'Throw and catch require'],
	'ancestry removed' => [str_replace(' extends RootFailure', '', $valid), 'requires'],
];
file_put_contents($path, $valid);
$compiler->exec_cpp();
$before = CPP_Probe::text(Model::$cpp_files);
foreach ($cases as $name => [$source, $diagnostic]) {
	file_put_contents($path, $source);
	$failed = false;
	try { $compiler->sync([$path]); $compiler->prepare(); }
	catch (\RuntimeException $error) {
		$failed = true;
		if (!str_contains($error->getMessage(), $diagnostic)) { throw new \LogicException($name . ': ' . $error->getMessage()); }
	}
	if (!$failed) { throw new \LogicException('Invalid exception admitted: ' . $name); }
	file_put_contents($path, $valid);
	$compiler->sync([$path]); $compiler->prepare(); $compiler->cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Exception repair changed output'); }
}
Compiler_Lifecycle::reset();
$fresh = new Compiler(new cpp_lowering_options(false));
$fresh->init([$directory]); $fresh->exec_cpp();
if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Exception fresh output differs'); }
echo "Exception ancestry rejection, invalidation and recovery PASS\n";
