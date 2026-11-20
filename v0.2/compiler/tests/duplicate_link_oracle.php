<?php

declare(strict_types=1);

require_once __DIR__ . '/../boot.php';

use scpp\compiler\{ast_constant_declaration, ast_declaration_interface, ast_enum_case, ast_enum_declaration,
	ast_function_declaration, ast_global_declaration, ast_instance_field, ast_integer_expression, ast_local_binding,
	ast_local_declaration, ast_parameter_declaration, ast_produced_binding, ast_record_declaration,
	ast_type_declaration, ast_type_reference};

/** Record the outcome of the production PHP contract, including link identity and rejected-write purity. */
function link_succeeds(ast_declaration_interface $head, ast_declaration_interface $element): bool
{
	$before = $head->duplicate_element();
	try {
		$head->link_duplicate($element);
		if ($head->duplicate_element() !== $element) throw new RuntimeException('Wrong duplicate identity');
		return true;
	}
	catch (LogicException $failure) {
		if ($failure->getMessage() !== 'Invalid duplicate declaration link' || $head->duplicate_element() !== $before) {
			throw $failure;
		}
		return false;
	}
}

$local = new ast_local_declaration(0, 1, 'item', null, new ast_integer_expression(0, 1));
$parameter = new ast_parameter_declaration('item', new ast_type_reference(0, 1, 'int'), 0, 0, 1);
$record = new ast_record_declaration('Item', null, [], 0, 1);
$enumeration = new ast_enum_declaration('Item', null, null, [], 0, 1);
$global = new ast_global_declaration(0, 1, 'item', null, null, new ast_integer_expression(0, 1));
$field = new ast_instance_field('item', new ast_type_reference(0, 1, 'int'), 0, 1, false);
$case = new ast_enum_case('item', new ast_integer_expression(0, 1), 0, 1);
$other = new ast_local_declaration(0, 1, 'other', null, new ast_integer_expression(0, 1));

/** @var ast_declaration_interface[] $declarations */
$declarations = [
	$local, $parameter, new ast_produced_binding('item', 0, 0),
	$record, $enumeration, $global, $field, $case,
	new ast_constant_declaration(0, 1, 'item', null, new ast_type_reference(0, 1, 'int'), new ast_integer_expression(0, 1)),
	new ast_function_declaration('item', null, 0, 1),
];
foreach ($declarations as $head) {
	foreach ($declarations as $element) {
		// The original class/family rule serves as the compatibility oracle.
		$compatible = get_class($head) === get_class($element)
			|| ($head instanceof ast_local_binding && $element instanceof ast_local_binding)
			|| ($head instanceof ast_type_declaration && $element instanceof ast_type_declaration);
		if (($head->duplicate_category() === $element->duplicate_category()) !== $compatible
			|| link_succeeds(clone $head, clone $element) !== ($compatible && $head->symbol_key() === $element->symbol_key())) {
			throw new RuntimeException('Duplicate category changed compatibility for ' . get_class($head) . ' and ' . get_class($element));
		}
	}
}
$replacement = match ($argv[1]) {
	'Local_Declaration' => new ast_local_declaration(0, 1, 'item', null, new ast_integer_expression(0, 1)),
	'Parameter_Declaration' => new ast_parameter_declaration('item', new ast_type_reference(0, 1, 'int'), 0, 0, 1),
	'Global_Declaration' => new ast_global_declaration(0, 1, 'item', null, null, new ast_integer_expression(0, 1)),
};

echo (int) link_succeeds($local, $local);
echo (int) link_succeeds($local, $global);
echo (int) link_succeeds($field, $case);
echo (int) link_succeeds($local, $other);
echo (int) link_succeeds($local, $parameter);
echo (int) link_succeeds($local, $parameter);
echo (int) link_succeeds(new ast_local_declaration(0, 1, 'item', null, new ast_integer_expression(0, 1)), $local);
echo (int) link_succeeds($record, $enumeration);

$local->clear_duplicate();
echo (int) link_succeeds($local, $replacement);
if ($parameter->duplicate_element() !== null) throw new RuntimeException('Previous successor was changed');
