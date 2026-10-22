<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Publish through the production retained lifecycle, including ready-state recovery. */
function iteration_publish(Compiler $compiler, string $path, string $output, bool $initial): void
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
$sources = [
	'$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v;if($v===1){$xs[]=4;$xs[1]=20;}}',
	'$xs hash<int> = {a:1,b:2,c:3};foreach($xs as $v){echo $v;if($v===1){$xs["d"]=4;$xs["b"]=20;}}',
	'$xs mixed = [1,2,3];$copy=$xs;foreach($xs as $v){$n int=$v;echo $n;if($n===1){$xs[1]=20;$xs[]=4;$xs[2]=30;}}',
	'$xs dynamic = [1,2,3];$alias=$xs;foreach($xs as $v){$n int=$v;echo $n;if($n===1){$alias[1]=20;$alias[]=4;$alias[2]=30;}}',
	'class C{public vector<int> $items=[1,2,3];function __destruct(){echo "D";}}function make():C{return new C();}foreach(make()->items as $v){echo $v;}',
];
$expected = ['123', '123', '1203', '1203', '123D'];
file_put_contents($path, $sources[0]);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
$rows = [];
$output = '';
foreach ($sources as $index => $source) {
	file_put_contents($path, $source);
	$output = $directory . '-published-' . $index;
	iteration_publish($compiler, $path, $output, $index === 0);
	$rows[] = ['published' => $output, 'expected' => $expected[$index]];
}
$saved = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);
file_put_contents($path, '$xs vector<int> = [1];foreach($xs as &$v){echo $v;}');
$rejected = false;
try {
	iteration_publish($compiler, $path, $output, false);
}
catch (\RuntimeException $error) {
	$rejected = true;
}
if (!$rejected || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Reference foreach accepted or left published compiler output');
}
$unready = false;
try {
	Output_Publication::require_ready($output);
}
catch (\RuntimeException $error) {
	$unready = true;
}
if (!$unready) { throw new \LogicException('Invalid iteration left ready artifacts'); }
file_put_contents($path, $sources[4]);
iteration_publish($compiler, $path, $output, false);
if (CPP_Probe::text(Model::$cpp_files) !== $saved || Output_Publication::require_ready($output) !== $manifest) {
	throw new \LogicException('Iteration recovery changed artifacts');
}
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
$fresh_output = $directory . '-fresh';
iteration_publish($fresh, $path, $fresh_output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $saved) {
	throw new \LogicException('Fresh and retained iteration differ');
}
$rows[] = ['published' => $fresh_output, 'expected' => '123D'];
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
