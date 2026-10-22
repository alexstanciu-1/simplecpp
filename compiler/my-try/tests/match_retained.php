<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function match_publish(Compiler $compiler, string $path, string $output, bool $initial): void
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
$prefix = 'enum E:int{case A=0;case B=7;}';
$defaulted = $prefix . 'echo match(E::B){E::A=>"A",E::B=>"B",default=>"D"};';
$unguarded = $prefix . 'echo match(E::B){E::A=>"A",E::B=>"B"};';
file_put_contents($path, $defaulted);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
match_publish($compiler, $path, $output, true);
foreach ([$unguarded, $defaulted, str_replace('case B=7', 'case B=8', $unguarded), $unguarded] as $source) {
	file_put_contents($path, $source);
	match_publish($compiler, $path, $output, false);
	$text = CPP_Probe::text(Model::$cpp_files);
	$has_failure = str_contains($text, 'runtime_match_failure_');
	if ($has_failure !== !str_contains($source, 'default=>')) { throw new \LogicException('Match helper demand/retirement mismatch'); }
}
$saved = CPP_Probe::text(Model::$cpp_files);
$manifest = Output_Publication::require_ready($output);
file_put_contents($path, str_replace('E::A=>', 'E::Gone=>', $unguarded));
$rejected = false;
try { match_publish($compiler, $path, $output, false); }
catch (\RuntimeException $error) { $rejected = true; }
if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid match accepted'); }
$unready = false;
try { Output_Publication::require_ready($output); }
catch (\RuntimeException $error) { $unready = true; }
if (!$unready) { throw new \LogicException('Failure left ready match artifacts'); }
file_put_contents($path, $unguarded);
match_publish($compiler, $path, $output, false);
if (CPP_Probe::text(Model::$cpp_files) !== $saved || Output_Publication::require_ready($output) !== $manifest) {
	throw new \LogicException('Match recovery changed artifacts');
}
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
match_publish($fresh, $path, $output, true);
if (CPP_Probe::text(Model::$cpp_files) !== $saved) { throw new \LogicException('Fresh/retained match differs'); }
echo "Match retained publication passed\n";
