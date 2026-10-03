<?php

/* Nullable syntax identity, cleanup and incremental signature failure/recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function nullable_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$directory = sys_get_temp_dir() . '/scpp_nullable_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try {
	$valid = 'function source(): ?int { return 3; } function read(): int { return source(); } '
		. 'function fallback(): int { return 7; } function stable(): int { return 9; } '
		. '$a ?int = null; $b nullable<int> = $a; $c int = $a ?? $b ?? fallback(); return read();';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = Model::$cpp_files[0]->text;
	$statements = Model::syntax_files()[0]->root->body->statements;
	$a = $statements[0];
	$b = $statements[1];
	nullable_check($a->type_syntax instanceof nullable_type_node, 'Nullable shorthand syntax was lost');
	nullable_check($a->type_syntax->require_preparation()->matches($b->type_syntax->require_preparation()),
		'Shorthand and explicit nullable split canonical identity');
	$children = iterator_to_array($a->type_syntax->children());
	nullable_check(count($children) === 1 && $children[0] === $a->type_syntax->payload,
		'Nullable inspection missed its payload');
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	$coalesce = $statements[2]->initializer;
	nullable_check($coalesce instanceof coalesce_expression_node
		&& $coalesce->fallback instanceof coalesce_expression_node, 'Coalescing must associate right');
	$coalesce_children = iterator_to_array($coalesce->children());
	nullable_check($coalesce_children === [$coalesce->left, $coalesce->fallback], 'Coalesce inspection order changed');
	nullable_check($coalesce->require_preparation()->type->matches($statements[2]->type_syntax->require_preparation()),
		'Coalesce result retained the nullable carrier');
	$invalid = str_replace('source(): ?int { return 3;', 'source(): ?float { return 3.5;', $valid);
	file_put_contents($path, $invalid);
	foreach ([[$path], []] as $changed) {
		$compiler->sync($changed);
		$failed = false;
		try { $compiler->prepare(); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'same payload type'); }
		nullable_check($failed && Model::$cpp_files->is_empty(), 'Nullable failure published stale output');
		nullable_check($stable->version === $version, 'Nullable change rebuilt an independent body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	nullable_check(Model::$cpp_files[0]->text === $original, 'Nullable repair changed output');
	file_put_contents($path, str_replace('fallback(): int { return 7;', 'fallback(): float { return 7.5;', $valid));
	foreach ([[$path], []] as $changed) {
		$compiler->sync($changed);
		$failed = false;
		try { $compiler->prepare(); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'coalesce branches require matching'); }
		nullable_check($failed && Model::$cpp_files->is_empty(), 'Fallback change did not invalidate coalesce');
		nullable_check($stable->version === $version, 'Fallback change rebuilt independent body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	nullable_check(Model::$cpp_files[0]->text === $original, 'Coalesce repair changed output');
	Compiler_Lifecycle::reset();
	$clean = new Compiler();
	$clean->init([$directory]);
	$clean->exec_cpp();
	nullable_check(Model::$cpp_files[0]->text === $original, 'Clean and incremental nullable output differ');
	$root = Model::syntax_files()[0]->root;
	$syntax = $root->body->statements[0];
	$coalesce = $root->body->statements[2]->initializer;
	Preparation_Cleanup::tree($root);
	nullable_check($syntax->preparation() === null && $syntax->type_syntax->preparation() === null
		&& $syntax->type_syntax->payload->preparation() === null && $syntax->initializer->preparation() === null,
		'Nullable cleanup missed attached facts');
	nullable_check($coalesce->preparation() === null && $coalesce->left->preparation() === null
		&& $coalesce->fallback->preparation() === null, 'Coalesce cleanup missed attached facts');
	echo "Nullable: canonical syntax, traversal, cleanup and incremental recovery passed\n";
} finally {
	unlink($path);
	rmdir($directory);
}
