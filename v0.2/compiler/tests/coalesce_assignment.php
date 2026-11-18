<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';
use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, Function_Preparation,
	Preparation, Syntax_Inspection, Compilation_Inspection, ast_assignment_statement, binary_operator, token_kind};

function verify(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

foreach (flavor::cases() as $flavor) {
	$source = match ($flavor) {
		flavor::php => '$n nullable<int> = null; $n ??= 3; return 8;',
		flavor::cpp => 'nullable<int> n = null; n ??= 3; return 8;',
		flavor::js => 'let n:nullable<int> = null; n ??= 3; return 8;',
	};
	$frontend = new Source_Frontend();
	$tokens = $frontend->scan(new source_file('tokens', '??= ?? =', $flavor));
	verify(array_map(fn($t) => $t->kind, $tokens->tokens) === [token_kind::coalesce_assign, token_kind::coalesce, token_kind::assign], 'Longest punctuation match');
	$entry = $frontend->prepare(new source_file('coalesce_assignment', $source, $flavor), new entry_target(31));
	$write = $entry->body()->statements[1];
	verify($write instanceof ast_assignment_statement && $write->operator === binary_operator::coalesce, 'Reuse assignment intent');
	verify(!$write->calculates_value() && $write->value->parent === $write, 'Conditional write owns only its RHS');
	verify(!$write->constant_known() && $write->constant() === null, 'Conditional write does not publish an unconditional RHS constant');
	verify($entry->body()->expression_visits === 3, 'Single-pass preparation');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(str_contains($cpp, 'if (!(') && str_contains($cpp, '.has_value()') && !str_contains($cpp, '[&]'), 'Direct guarded write without lambda');
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	verify(serialize($entry) === $before, 'Emission/inspection preserve facts');
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify($again->body()->expression_visits === 3 && (new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare equivalence');
}
echo "Coalescing assignment syntax, ownership, preparation and emission passed\n";
