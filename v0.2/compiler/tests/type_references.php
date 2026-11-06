<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{ast_type_reference, ast_node, Source_Frontend, source_file, flavor, diagnostic};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Verify a parsed leaf's exact syntax ownership without copying its written name. */
function leaf(ast_type_reference $type, ast_node $owner): void
{
	verify($type->name === 'int', 'Written type name');
	verify(($type->parent === $owner) && ($type->root() === $owner->root()), 'Type ownership');
	verify(($type->first_child === null) && ($type->next_sibling === null), 'Simple type acquired arguments');
	verify(($type->end === $type->start + 1) && ($type->source_text() === 'int'), 'Type token bounds');
}

$frontend = new Source_Frontend();
foreach ([
	[flavor::cpp, 'const int cap = 8; global int count = 8; int id(int value) { int local = (int) value; return local; } auto inferred = 8; return id(inferred);'],
	[flavor::php, 'const int cap = 8; global $count int = 8; function id(int $value): int { $local int = (int) $value; return $local; } $inferred = 8; return id($inferred);'],
	[flavor::js, 'const cap: int = 8; global let count: int = 8; function id(value: int): int { let local: int = (int) value; return local; } let inferred = 8; return id(inferred);'],
] as [$flavor, $text]) {
	$file = $frontend->parse(new source_file('types', $text, $flavor));
	$function = $file->declarations['func:id'];
	leaf($function->return_type_reference, $function);
	$parameter = $function->parameters[0];
	leaf($parameter->type_reference, $parameter);
	$local = $function->body()->statements[0];
	leaf($local->type_reference, $local);
	leaf($local->initializer->type_reference, $local->initializer);
	foreach ($file->globals() as $global) {
		leaf($global->type_reference, $global);
	}
	foreach ($file->constants() as $constant) {
		leaf($constant->type_reference, $constant);
	}
	verify($file->main()->body()->statements[0]->type_reference === null, 'Inferred local acquired written syntax');
	$implicit = $file->main()->return_type_reference;
	verify(($implicit->name === 'int') && ($implicit->start === $implicit->end)
		&& ($implicit->parent === $file->main()), 'Implicit entry type anchor');

	// Synthetic nested shape only: this does not admit generic source syntax.
	$integer = new ast_type_reference(0, 0, 'int');
	$vector = new ast_type_reference(0, 0, 'vector', $integer);
	$string = new ast_type_reference(0, 0, 'string', null, $vector);
	$hash = new ast_type_reference(0, 0, 'hash', $string);
	$hash->attach($file);
	verify(($hash->first_child === $string) && ($string->next_sibling === $vector)
		&& ($vector->next_sibling === null), 'Ordered sibling chain');
	verify(($string->parent === $hash) && ($vector->parent === $hash)
		&& ($integer->parent === $vector) && ($integer->root() === $file), 'Sibling parents must be the containing type');
	try {
		new ast_type_reference(0, 0, 'other', $string);
		throw new RuntimeException('Shared syntax ownership accepted');
	}
	catch (LogicException $expected) {
	}
	try {
		$vector->next_sibling = $string;
		throw new RuntimeException('Published sibling mutation accepted');
	}
	catch (Error $expected) {
	}
}
echo "Type reference leaves, ordered child/sibling ownership, implicit/inferred types passed\n";
