<?php

namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function generation_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$directory = sys_get_temp_dir() . '/scpp_token_generations_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/a.phs';
file_put_contents($path, 'return 1;');
file_put_contents($directory . '/b.phs', 'return 2;');
try
{
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$module = Model::$modules[$directory];
	$a = $module->sources['a.phs'];
	$b = $module->sources['b.phs'];
	$compiler->tokenize();
	$first = $a->tokens;
	$other = $b->tokens;
	generation_check(($first !== null) && ($a->previous_tokens === null), 'Initial tokenization did not establish the first generation');
	generation_check(($a->file === $first->file) && ($a->file->tokens === $first), 'Current file and token backlinks disagree');

	// Simulate the previous completed run; parsing/analysis coordination is deliberately deferred.
	$old_ast = (new Parser($first))->parse();
	$a->parsed = $old_ast;
	$a->changes = change_state::unchanged;
	$b->changes = change_state::unchanged;
	$compiler->tokenize();
	generation_check(($a->tokens === $first) && ($a->previous_tokens === null), 'Unchanged source was retokenized');
	file_put_contents($path, 'return 22;');
	Module_Loader::discover($module);
	$compiler->tokenize();
	$current = $a->tokens;
	$published_file = $a->file;
	generation_check(($current !== $first) && ($a->previous_tokens === $first), 'Changed source did not rotate its token generation');
	generation_check(($current->content === 'return 22;') && ($first->content === 'return 1;') && ($first->file->content === 'return 1;'), 'Token replacement mutated the old source snapshot');
	generation_check(($a->parsed === $old_ast) && ($old_ast->tokens === $first), 'Tokenization cleared or rewired the old AST');
	generation_check(($b->tokens === $other) && ($b->previous_tokens === null), 'Changed file caused an unchanged file to rotate');
	generation_check($a->changes === change_state::changed, 'Tokenization consumed the change needed by later phases');

	file_put_contents($path, '$');
	$failed = false;
	try {
		$compiler->tokenize();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	generation_check($failed && ($a->tokens === $current) && ($a->previous_tokens === $first), 'Lexical failure rotated token generations');
	generation_check(($a->file === $published_file) && ($published_file->content === 'return 22;'), 'Lexical failure changed the published input');
	unlink($path);
	$failed = false;
	try {
		$compiler->tokenize();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	generation_check($failed && ($a->tokens === $current) && ($a->previous_tokens === $first), 'Read failure changed retained token generations');
	$a->changes = change_state::deleted;
	$compiler->tokenize();
	generation_check(($a->tokens === $current) && ($a->previous_tokens === $first), 'Deleted source was tokenized or discarded prematurely');
}
finally
{
	foreach (glob($directory . '/*') as $file_path) {
		unlink($file_path);
	}
	rmdir($directory);
}
echo "Token generations: added/changed replacement, unchanged/deleted skip, old AST retention and failure isolation passed\n";
