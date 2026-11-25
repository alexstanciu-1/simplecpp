<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';
use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, Function_Preparation,
	Preparation, Syntax_Inspection, Compilation_Inspection, ast_binary_expression, binary_operator};

function verify(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	$source = match ($flavor) {
		flavor::php => '$a nullable<int> = null; $b nullable<int> = 0; $r=$a ?? $b ?? 8;return 8;',
		flavor::cpp => 'nullable<int> a = null; nullable<int> b = 0; auto r=a ?? b ?? 8;return 8;',
		flavor::js => 'let a:nullable<int> = null; let b:nullable<int> = 0; let r=a ?? b ?? 8;return 8;',
	};
	$entry = (new Source_Frontend())->prepare(new source_file('coalesce', $source, $flavor), new entry_target(31));
	$outer = $entry->body()->statements[2]->initializer;
	verify($outer instanceof ast_binary_expression && $outer->operator === binary_operator::coalesce, 'Reuse owned binary syntax');
	verify($outer->right instanceof ast_binary_expression && $outer->right->operator === binary_operator::coalesce, 'Coalescing is right associative');
	verify($outer->left->parent === $outer && $outer->right->parent === $outer, 'Operands retain syntax parents');
	verify($entry->body()->expression_visits === 8, 'Single-pass preparation: ' . $entry->body()->expression_visits);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	verify(serialize($entry) === $before, 'Emission/inspection must not mutate facts');
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify($again->body()->expression_visits === 8 && (new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare equivalence');
}
echo "Coalescing ownership, association, single-pass preparation and pure emission passed\n";
