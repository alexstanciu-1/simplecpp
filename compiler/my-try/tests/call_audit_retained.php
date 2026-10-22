<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function call_audit_publish(Compiler $compiler, string $path, string $output, bool $initial): void
{
	$publication = new Output_Publication($output);
	try {
		if ($initial) { $compiler->exec_cpp($publication); }
		else { $compiler->update_cpp([$path], $publication); }
	}
	finally { $publication->close(); }
}
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$output = $directory . '-published';
$source = 'interface I{function read():int;}class C implements I{function read():int{return 7;}static function value(int $n=3):int{return $n;}}$i I=new C();echo $i->read(),C::value();';
file_put_contents($path, $source);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
call_audit_publish($compiler, $path, $output, true);
$original = CPP_Probe::text(Model::$cpp_files);
$source = str_replace('$n=3', '$n=9', $source);
file_put_contents($path, $source);
call_audit_publish($compiler, $path, $output, false);
$saved = CPP_Probe::text(Model::$cpp_files);
if ($saved === $original) { throw new \LogicException('Default edit did not update caller'); }
$manifest = Output_Publication::require_ready($output);
foreach ([str_replace('C::value()', 'C::value(n:9)', $source),
	str_replace('function read():int;', 'static function read():int;', $source),
	str_replace('function value(int $n=9)', 'function &value(int &$n=9)', $source),
	str_replace('C::value()', 'C::value(...[9])', $source)] as $invalid) {
	file_put_contents($path, $invalid);
	$rejected = false;
	try { call_audit_publish($compiler, $path, $output, false); }
	catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Excluded call accepted'); }
	$unready = false;
	try { Output_Publication::require_ready($output); }
	catch (\RuntimeException $error) { $unready = true; }
	if (!$unready) { throw new \LogicException('Failure left ready call artifacts'); }
	file_put_contents($path, $source);
	call_audit_publish($compiler, $path, $output, false);
	if (CPP_Probe::text(Model::$cpp_files) !== $saved || Output_Publication::require_ready($output) !== $manifest) {
		throw new \LogicException('Call recovery changed artifacts');
	}
}
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
call_audit_publish($fresh, $path, $output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $saved) { throw new \LogicException('Fresh/retained calls differ'); }
echo "Call audit retained publication passed\n";
