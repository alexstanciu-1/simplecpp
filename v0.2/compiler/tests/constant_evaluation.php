<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../boot.php';

/** Test-only destination that declines evaluation without changing conversion admission. */
final class test_constant_type extends type_definition
{
	public function __construct()
	{
		parent::__construct('test_constant');
	}

	public function cpp_type(): string
	{
		return 'scpp::int64';
	}

	public function convert_scalar_constant(int $conversion /** uint16 */, int|float|bool|null $value /** int64|float64|bool|null */): int|float|bool|null|constant_evaluation /** int64|float64|bool|null|constant_evaluation */
	{
		return constant_evaluation::unavailable;
	}
}

/** Test-only scalar source isolates evaluation outcomes from source-language admission. */
final class test_constant_expression extends ast_expression
{
	public function __construct(int|float|bool|null $value /** int64|float64|bool|null */, bool $known)
	{
		parent::__construct(0, 1);
		$this->settle($value, $known);
	}

	public function settle(int|float|bool|null $value /** int64|float64|bool|null */, bool $known): void
	{
		$this->settle_value(type_int64::instance(), $value, $known);
	}
}

function verify(bool $condition, string $message): void
{
	if (!$condition) throw new \RuntimeException($message);
}

$type = new test_constant_type();
$identity = value_conversion::identity;
$cast = new ast_cast_expression(0, 1, new ast_type_reference(0, 1, 'int'), new test_constant_expression(8, true));
$local = new ast_local_declaration(0, 1, 'item', null, new test_constant_expression(8, true));
$local->identify(0);
$write = new ast_assignment_statement(0, 1, 'item', new test_constant_expression(9, true), assignment_intent::existing_binding);
$constant = new ast_constant_declaration(0, 1, 'LIMIT', null, null, new test_constant_expression(8, true));
$read = new ast_name_expression('item', 0, 1);
foreach ([$type, type_int64::instance(), $type, type_int64::instance()] as $target) {
	$known = $target !== $type;
	$cast->settle_cast($target, $identity);
	verify($cast->constant_known() === $known && $cast->constant() === ($known ? 8 : null), 'Cast evaluation');
	$local->settle_type($target, $target->evaluate_constant($identity, $local->initializer), true);
	verify($local->constant_known() === $known && $local->constant() === ($known ? 8 : null), 'Local evaluation');
	$write->settle_assignment($local, $identity);
	verify($write->constant_known() === $known && $write->constant() === ($known ? 9 : null), 'Write evaluation');
	$read->settle_local($local, $write);
	verify($read->constant_known() === $known && $read->constant() === ($known ? 9 : null), 'Reference propagation');
	$constant->initializer->reset_preparation();
	// The literal declaration owns initializer reset; settle the existing source facts again.
	$constant->initializer->settle(8, true);
	$constant->settle_type($target);
	verify($constant->constant_known() === $known && $constant->constant() === ($known ? 8 : null), 'Named constant evaluation');
	foreach ([$cast, $local, $write, $read, $constant] as $node) $node->reset_preparation();
}
foreach ([null, false, 0] as $value) {
	$source = new test_constant_expression($value, true);
	verify(type_int64::instance()->evaluate_constant($identity, $source) === $value, 'Known scalar changed');
	$cast = new ast_cast_expression(0, 1, new ast_type_reference(0, 1, 'int'), $source);
	$cast->settle_cast(type_int64::instance(), $identity);
	verify($cast->constant_known() && $cast->constant() === $value, 'Known null/false/zero lost');
}
$unknown = new test_constant_expression(null, false);
verify(type_int64::instance()->evaluate_constant($identity, $unknown) === constant_evaluation::unavailable, 'Unknown became known null');
echo "Unavailable conversion propagation, reset and known null/false/zero passed\n";
