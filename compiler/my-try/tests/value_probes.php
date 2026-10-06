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
		probe_check(!$probe->require_preparation()->addressable(), 'Probe result is addressable');
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
	$nested = 'function probe(hash<hash<int>> &$v): bool { unset($v["parent"]["x"]); return isset($v["parent"]["y"]); } return 0;';
	file_put_contents($path, $nested);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('probe')[0]->syntax();
	$body = $function->body;
	$removal = $body->statements[0];
	$probe = $body->statements[1]->expression;
	probe_check($removal instanceof unset_node, 'Unset did not retain statement syntax');
	probe_check(iterator_to_array($removal->children()) === [$removal->target], 'Unset inspection lost target');
	$old = $removal->require_preparation();
	probe_check($old->steps->count() === 2 && $probe->require_probe_preparation()->path->steps->count() === 2, 'Keyed facts lost path steps');
	probe_check($old->steps[0]->syntax->preparation() === null, 'Keyed path was prepared as a value read');
	file_put_contents($path, str_replace('hash<hash<int>>', 'hash<hash<string>>', $nested));
	$compiler->update_cpp([$path]);
	probe_check($function->body === $body && $old !== $removal->require_preparation(), 'Retained keyed body did not reprepare');
	file_put_contents($path, str_replace('hash<hash<int>>', 'hash<vector<int>>', $nested));
	$failed = false;
	try { $compiler->update_cpp([$path]); }
	catch (\RuntimeException $error) { $failed = true; }
	probe_check($failed && Model::$cpp_files->is_empty() && $removal->preparation() === null, 'Invalid unset target retained output/facts');
	file_put_contents($path, $nested);
	$compiler->update_cpp([$path]);
	$incremental = Model::$cpp_files[0]->text;
	Token_Cleanup::file(Model::syntax_files()[0]);
	probe_check(Model::syntax_files()[0]->tokens->text_at($removal->start_token()) === 'unset', 'Unset span lost after compaction');
	Preparation_Cleanup::tree($body);
	probe_check($removal->preparation() === null && $probe->preparation() === null
		&& $old->root->preparation() === null, 'Keyed cleanup missed syntax facts');
	Compiler_Lifecycle::reset();
	$clean = new Compiler();
	$clean->init([$directory]);
	$clean->exec_cpp();
	probe_check($incremental === Model::$cpp_files[0]->text, 'Keyed clean/incremental recovery differs');
	foreach (['unset();', 'unset($v[0], $v[1]);'] as $bad) {
		file_put_contents($path, '$v mixed = {}; ' . $bad);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$failed = false;
		try { $compiler->init([$directory]); $compiler->exec_cpp(); }
		catch (\RuntimeException $error) { $failed = true; }
		probe_check($failed && Model::$cpp_files->is_empty(), 'Unset accepted invalid arity');
	}
	echo "Value probes/removal: syntax, arity, path facts and incremental recovery passed\n";
} finally {
	unlink($path);
	rmdir($directory);
}
