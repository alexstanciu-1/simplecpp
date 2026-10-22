<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Publish each retained revision through the production readiness protocol. */
function enum_wrapper_publish(Compiler $compiler, string $path, string $output, bool $initial): void
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
file_put_contents($main, 'use Domain\E as Choice;function make():result<Choice>{return Choice::A;}$v=make();$out Choice=Choice::B;$err error;take($out,$err)=$v;echo $out===Choice::A?"A":"bad";$n ?Choice=null;echo ($n??Choice::B)===Choice::B?"A":"bad";');
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
enum_wrapper_publish($compiler, $enum, $output, true);
$initial = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);
foreach (['namespace Domain; enum E:int8{case A=128;case B=127;}',
	'namespace Domain; enum E:int8{case Gone=-128;case B=127;}'] as $invalid) {
	file_put_contents($enum, $invalid);
	$rejected = false;
	try { enum_wrapper_publish($compiler, $enum, $output, false); }
	catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid enum edit accepted'); }
	$unready = false;
	try { Output_Publication::require_ready($output); }
	catch (\RuntimeException $error) { $unready = true; }
	if (!$unready) { throw new \LogicException('Invalid enum edit retained ready output'); }
	file_put_contents($enum, $valid);
	enum_wrapper_publish($compiler, $enum, $output, false);
	if (CPP_Probe::text(Model::$cpp_files) !== $initial || Output_Publication::require_ready($output) !== $manifest) {
		throw new \LogicException('Enum wrapper recovery differs');
	}
}
file_put_contents($enum, 'namespace Domain; enum E:uint64{case A=18446744073709551615;case B=0;}');
enum_wrapper_publish($compiler, $enum, $output, false);
$changed = CPP_Probe::text(Model::$cpp_files);
if ($changed === $initial) { throw new \LogicException('Enum backing edit did not invalidate output'); }
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
enum_wrapper_publish($fresh, $enum, $output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $changed) { throw new \LogicException('Fresh and retained enum wrappers differ'); }
echo "Enum wrapper retained recovery passed\n";
