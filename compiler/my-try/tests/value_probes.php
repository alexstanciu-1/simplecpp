<?php

/* Probe syntax, scalar admission, cleanup and incremental recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function probe_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$directory = sys_get_temp_dir() . '/scpp_probes_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try {
	$valid = 'function source(): ?int { return null; } function stable(): int { return 7; } '
		. '$a bool = isset(source()); $b bool = empty(source()); return $b;';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = Model::$cpp_files[0]->text;
	$statements = Model::syntax_files()[0]->root->body->statements;
	foreach ([0 => value_probe_kind::isset, 1 => value_probe_kind::empty] as $index => $kind) {
		$probe = $statements[$index]->initializer;
		probe_check($probe instanceof value_probe_node && $probe->probe_kind === $kind, 'Wrong probe syntax');
		probe_check(iterator_to_array($probe->children()) === [$probe->operand], 'Probe inspection lost operand');
		probe_check($probe->require_probe_preparation()->operation === $kind, 'Wrong prepared probe');
		probe_check(!$probe->require_preparation()->addressable, 'Probe result is addressable');
	}
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('source(): ?int { return null;', 'source(): void { return;', $valid));
	foreach ([[$path], []] as $changed) {
		$compiler->sync($changed);
		$failed = false;
		try { $compiler->prepare(); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'value probes require'); }
		probe_check($failed && Model::$cpp_files->is_empty(), 'Probe failure published stale output');
		probe_check($stable->version === $version, 'Probe change rebuilt unrelated body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	probe_check(Model::$cpp_files[0]->text === $original, 'Probe repair changed output');
	Compiler_Lifecycle::reset();
	$clean = new Compiler();
	$clean->init([$directory]);
	$clean->exec_cpp();
	probe_check(Model::$cpp_files[0]->text === $original, 'Clean and incremental probe output differ');
	$root = Model::syntax_files()[0]->root;
	$probe = $root->body->statements[0]->initializer;
	Preparation_Cleanup::tree($root);
	probe_check($probe->preparation() === null && $probe->operand->preparation() === null, 'Probe cleanup missed facts');
	foreach (['isset()', 'empty()', 'isset(1, 2)', 'empty(1, 2)'] as $bad) {
		file_put_contents($path, 'return ' . $bad . ';');
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$failed = false;
		try { $compiler->init([$directory]); $compiler->exec_cpp(); }
		catch (\RuntimeException $error) { $failed = true; }
		probe_check($failed && Model::$cpp_files->is_empty(), 'Probe accepted invalid arity');
	}
	echo "Value probes: syntax, arity, facts and incremental recovery passed\n";
} finally {
	unlink($path);
	rmdir($directory);
}
