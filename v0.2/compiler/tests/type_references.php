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

	$intersection_source = match ($flavor) {
		flavor::php => 'function visit(Base & Readable $item): void {}',
		flavor::cpp => 'void visit(Base & Readable item) {}',
		flavor::js => 'function visit(item: Base & Readable): void {}',
	};
	$parsed = $frontend->parse(new source_file('intersection', $intersection_source, $flavor));
	$param = $parsed->declarations['func:visit']->parameters[0];
	$pair = $param->type_reference;
	$first = $pair->first_child;
	$second = $first->next_sibling;
	verify($pair->parent === $param && $first->parent === $pair && $second->parent === $pair, 'Intersection parents');
	verify($pair->source_text() === 'Base & Readable' && $pair->spelling() === 'Base&Readable', 'Intersection spelling');
	verify($first->source_text() === 'Base' && $second->source_text() === 'Readable' && $second->next_sibling === null,
		'Intersection operand order and ranges');

	$union_source = $flavor === flavor::cpp ? 'RecordDecl | TraitDecl readDecl() {}' : 'function readDecl(): RecordDecl | TraitDecl {}';
	$return_owner = $frontend->parse(new source_file('union', $union_source, $flavor))->declarations['func:readDecl'];
	$union = $return_owner->return_type_reference;
	$first = $union->first_child;
	$second = $first->next_sibling;
	verify($union->name === '|' && $union->parent === $return_owner && $first->parent === $union && $second->parent === $union,
		'Union return ownership');
	verify($first->source_text() === 'RecordDecl' && $second->source_text() === 'TraitDecl' && $second->next_sibling === null,
		'Union return siblings in written order');

	// Each dimension owns its own literal sibling, including in a C++ return-type lookahead.
	$matrix = 'fixed_array<fixed_array<byte, 0x5>, 3>';
	$source = $flavor === flavor::cpp ? "$matrix grid() {}" : "function grid(): $matrix {}";
	$grid = $frontend->parse(new source_file('grid', $source, $flavor))->declarations['func:grid']->return_type_reference;
	$row = $grid->first_child;
	foreach ([[$grid, '3'], [$row, '0x5']] as [$dimension, $literal]) {
		$extent = $dimension->first_child->next_sibling;
		verify($extent instanceof scpp\compiler\ast_extent_literal && $extent->source_text() === $literal
			&& $extent->extent === ($dimension === $grid ? 3 : 5) && $extent->parent === $dimension && $extent->next_sibling === null, 'Fixed-array extent ownership');
	}
	verify($row->first_child->name === 'byte' && $grid->spelling() === str_replace(' ', '', $matrix), 'Matrix shape');
	foreach (['0' => 0, '0o10' => 8, '008' => 8, '0x7fffffffffffffff' => PHP_INT_MAX] as $literal => $expected) {
		$text = str_replace($matrix, 'fixed_array<byte,' . $literal . '>', $source);
		$type = $frontend->parse(new source_file('extent', $text, $flavor))->declarations['func:grid']->return_type_reference;
		verify($type->first_child->next_sibling->extent === $expected, 'Checked extent value');
	}
	$qualified = match ($flavor) {
		flavor::php => 'Sizes\\CAP', flavor::cpp => 'Sizes::CAP', flavor::js => 'Sizes.CAP'
	};
	$text = str_replace($matrix, 'fixed_array<byte,' . $qualified . '>', $source);
	$type = $frontend->parse(new source_file('named-grid', $text, $flavor))->declarations['func:grid']->return_type_reference;
	$bound = $type->first_child->next_sibling;
	verify($bound instanceof scpp\compiler\ast_extent_reference && $bound->name === 'Sizes\\CAP'
		&& $bound->source_text() === $qualified && $bound->parent === $type, 'Named extent qualification and ownership');
	foreach (['byte, 9223372036854775808', 'byte, 0x8000000000000000', 'byte', 'byte, -1', 'byte, 1+1', 'byte, 2, 3'] as $invalid) {
		try {
			$text = str_replace($matrix, 'fixed_array<' . $invalid . '>', $source);
			$frontend->parse(new source_file('invalid-grid', $text, $flavor));
			throw new RuntimeException('Invalid fixed-array arguments accepted: ' . $invalid);
		} catch (diagnostic) {
		}
	}

	// Manually constructed argument chains obey the same ownership contract.
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
