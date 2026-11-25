<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function enum_audit_publish(Compiler $compiler, string $path, string $output, bool $initial): void
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
$library = $directory . '/library.phs';
$main = $directory . '/main.phs';
$output = $directory . '-published';
$original = 'namespace Domain;enum E:int8{case A=-128;case B=127;}class C{public E $v=E::A;}function choose(E $v=E::A):E{return $v;}';
file_put_contents($library, $original);
file_put_contents($main, 'use Domain\C as Holder;use function Domain\choose;$c=new Holder();echo enum_name($c->v);echo enum_name(choose());');
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
enum_audit_publish($compiler, $library, $output, true);
$before = CPP_Probe::text(Model::$cpp_files);
$changed_source = str_replace('=E::A', '=E::B', $original);
file_put_contents($library, $changed_source);
enum_audit_publish($compiler, $library, $output, false);
$changed = CPP_Probe::text(Model::$cpp_files);
if ($changed === $before) { throw new \LogicException('Default edits did not invalidate consumers'); }
$manifest = Output_Publication::require_ready($output);
foreach ([str_replace('case B=127;', 'case Gone=127;', $changed_source),
	str_replace('public E $v=E::B;', 'public E $v=7;', $changed_source),
	str_replace('E $v=E::B):E', 'E $v=7):E', $changed_source)] as $invalid) {
	file_put_contents($library, $invalid);
	$rejected = false;
	try { enum_audit_publish($compiler, $library, $output, false); }
	catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid enum/default accepted'); }
	$unready = false;
	try { Output_Publication::require_ready($output); }
	catch (\RuntimeException $error) { $unready = true; }
	if (!$unready) { throw new \LogicException('Failure left ready artifacts'); }
	file_put_contents($library, $changed_source);
	enum_audit_publish($compiler, $library, $output, false);
	if (CPP_Probe::text(Model::$cpp_files) !== $changed || Output_Publication::require_ready($output) !== $manifest) {
		throw new \LogicException('Enum/default recovery changed artifacts');
	}
}
$wide = str_replace('enum E:int8{case A=-128;case B=127;}', 'enum E:uint64{case A=0;case B=18446744073709551615;}', $changed_source);
file_put_contents($library, $wide);
enum_audit_publish($compiler, $library, $output, false);
$retained = CPP_Probe::text(Model::$cpp_files);
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
enum_audit_publish($fresh, $library, $output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $retained) { throw new \LogicException('Fresh/retained defaults differ'); }
echo "Enum defaults retained recovery passed\n";
