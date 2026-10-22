<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Each publication attempt owns its reservation, including failed revisions. */
function publish_exception_probe(Compiler $compiler, string $path, string $output, bool $initial): void
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
$valid = 'try { throw Exception::make("ok"); } catch (Exception $e) { echo $e->get_message(); }';
file_put_contents($path, $valid);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
publish_exception_probe($compiler, $path, $output, true);
$initial = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);

$invalid = [
	'throw 3;',
	'try {} catch (int $e) {}',
	'class Exception {} throw new Exception();',
	'try {} catch (Exception $e) {} echo $e;',
	'function &f(int &$n):int{try{return $n;}finally{}}$n=1;$a =& f($n);',
	'try{}finally{return 1;}',
	'while(true){try{}finally{break;}}',
	'while(true){try{}finally{continue;}}',
	'try{}finally{try{return 1;}finally{}}',
];
foreach ($invalid as $bad)
{
	file_put_contents($path, $bad);
	$rejected = false;
	try {
		publish_exception_probe($compiler, $path, $output, false);
	}
	catch (\RuntimeException $error) {
		$rejected = true;
	}
	if (!$rejected || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Invalid exception edit accepted');
	}
	$unready = false;
	try {
		Output_Publication::require_ready($output);
	}
	catch (\RuntimeException $error) {
		$unready = str_contains($error->getMessage(), 'pending or failed');
	}
	if (!$unready) {
		throw new \LogicException('Rejected exception revision remained ready');
	}

	file_put_contents($path, $valid);
	publish_exception_probe($compiler, $path, $output, false);
	if ((CPP_Probe::text(Model::$cpp_files) !== $initial)
		|| (Output_Publication::require_ready($output) !== $manifest)) {
		throw new \LogicException('Exception repair differs');
	}
	publish_exception_probe($compiler, $path, $output, true);
	if ((CPP_Probe::text(Model::$cpp_files) !== $initial)
		|| (Output_Publication::require_ready($output) !== $manifest)) {
		throw new \LogicException('Exception replay differs');
	}
}

$edits = [
	'function f():vector<int>{try{return [7];}finally{echo 1;}}$v=f();',
	'while(true){try{break;}finally{echo 1;}}',
	'try { try { throw Exception::make("nested"); } catch(Exception $e) { throw $e; } } catch(Exception $e) { echo $e->get_message(); }',
	'async function f():int{try{await async_sleep_ms(0);return 7;}finally{echo 1;}}echo await f();',
	'echo 1;',
	$valid,
];
foreach ($edits as $edit)
{
	file_put_contents($path, $edit);
	publish_exception_probe($compiler, $path, $output, false);
	$incremental = CPP_Probe::text(Model::$cpp_files);
	$incremental_manifest = Output_Publication::require_ready($output);

	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	publish_exception_probe($fresh, $path, $output . '-fresh', true);
	if ((CPP_Probe::text(Model::$cpp_files) !== $incremental)
		|| (Output_Publication::require_ready($output . '-fresh') !== $incremental_manifest)) {
		throw new \LogicException('Exception edit differs from fresh');
	}
	$compiler = $fresh;
}
echo "Exception admission, disk rejection, repair, removal and fresh parity passed\n";
