<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Publish each retained revision through the production readiness protocol. */
function enum_helper_publish(Compiler $compiler, string $path, string $output, bool $initial): void
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
$enum = $directory . '/enum.phs';
$main = $directory . '/main.phs';
$output = $directory . '-published';
$valid = 'namespace Domain; enum E:int8{case A=-128;case B=127;}';
file_put_contents($enum, $valid);
$program = 'use Domain\E as Choice;function make():result<Choice>{return Choice::A;}$v=make();if(!isset($v)){return 2;}$n ?Choice=null;if(isset($n)){return 3;}$a=enum_value(Choice::A);echo enum_name(enum_from_value(Choice::class,$a));echo enum_name(Choice::A);';
file_put_contents($main, $program);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
enum_helper_publish($compiler, $enum, $output, true);
$initial = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);
foreach (['namespace Domain; enum E:int8{case A=128;case B=127;}',
	'namespace Domain; enum E:int8{case Gone=-128;case B=127;}'] as $invalid) {
	file_put_contents($enum, $invalid);
	$rejected = false;
	try { enum_helper_publish($compiler, $enum, $output, false); }
	catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid enum edit accepted'); }
	$unready = false;
	try { Output_Publication::require_ready($output); }
	catch (\RuntimeException $error) { $unready = true; }
	if (!$unready) { throw new \LogicException('Invalid enum edit retained ready output'); }
	file_put_contents($enum, $valid);
	enum_helper_publish($compiler, $enum, $output, false);
	if (CPP_Probe::text(Model::$cpp_files) !== $initial || Output_Publication::require_ready($output) !== $manifest) {
		throw new \LogicException('Enum wrapper recovery differs');
	}
}
file_put_contents($enum, 'namespace Domain; enum E:uint64{case A=18446744073709551615;case B=0;}');
enum_helper_publish($compiler, $enum, $output, false);
$changed = CPP_Probe::text(Model::$cpp_files);
if ($changed === $initial) { throw new \LogicException('Enum backing edit did not invalidate output'); }
// Retire helper demand and prove restoring it rebuilds the same published artifacts.
file_put_contents($main, 'echo "AA";');
enum_helper_publish($compiler, $main, $output, false);
if (str_contains(CPP_Probe::text(Model::$cpp_files), 'runtime_enum_helper_')) {
	throw new \LogicException('Retired enum helper remains published');
}
file_put_contents($main, $program);
enum_helper_publish($compiler, $main, $output, false);
if (CPP_Probe::text(Model::$cpp_files) !== $changed) { throw new \LogicException('Restored helper differs'); }
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
enum_helper_publish($fresh, $enum, $output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $changed) { throw new \LogicException('Fresh and retained enum wrappers differ'); }
echo "Enum helper retained recovery and retirement passed\n";
