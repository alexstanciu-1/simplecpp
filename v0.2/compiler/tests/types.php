<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{type_definition, type_registry, type_int64, type_void, Source_Frontend, source_file, flavor,
	entry_target, ast_integer_expression};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$integer = type_int64::instance();
verify(($integer instanceof type_definition) && ($integer === type_int64::instance()), 'Noncanonical int64');
verify($integer->accepts_name('int') && $integer->accepts_name('int64') && !$integer->accepts_name('byte'), 'Alias admission');
verify(type_registry::resolve('int') === $integer && type_registry::resolve('int64') === $integer, 'Registry lost identity');
verify(type_registry::resolve('vector') === null, 'Registry enabled an unsupported type');
verify($integer::MINIMUM === PHP_INT_MIN && $integer::MAXIMUM === PHP_INT_MAX, 'Generated limits');
verify(type_void::instance() === type_void::instance(), 'Noncanonical void');
verify(type_void::instance()->name === 'void', 'Void identity changed');
try {
	clone $integer;
	throw new RuntimeException('Canonical definition cloned');
}
catch (Error $expected) {
}

$literal = new ast_integer_expression(0, 1);
try {
	$integer->lower_cpp_literal($literal);
	throw new RuntimeException('Lowering accepted an unprepared literal');
}
catch (LogicException $expected) {
}

$frontend = new Source_Frontend();
$entry = $frontend->prepare(new source_file('types',
	'global $low = -9223372036854775808; $a int=0008; $b int64=8; function ping(): void {} return $a;',
	flavor::php), new entry_target(31));
foreach ($entry->body()->locals as $local) {
	verify($local->type_def() === $integer, 'Local declaration lost canonical identity');
	verify($local->initializer->type_def() === $integer, 'Literal lost canonical identity');
}
verify($entry->file->functions()[0]->return_type_def() === type_void::instance(), 'Void return changed');
$minimum = $entry->file->globals()[0]->initializer;
$before = serialize($entry);
verify($integer->lower_cpp_literal($minimum) === '(-9223372036854775807LL - 1LL)', 'Minimum lowering changed');
verify(serialize($entry) === $before, 'Type lowering mutated AST facts');
verify($integer->cpp_type() === 'scpp::int64', 'Metadata-derived representation changed');
echo "Canonical type identities, aliases, literal readiness and lowering purity passed\n";
