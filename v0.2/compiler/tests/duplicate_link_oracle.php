<?php

declare(strict_types=1);

require_once __DIR__ . '/../boot.php';

use scpp\compiler\{ast_declaration_interface, ast_enum_case, ast_enum_declaration, ast_global_declaration,
	ast_instance_field, ast_integer_expression, ast_local_declaration, ast_parameter_declaration,
	ast_record_declaration, ast_type_reference};

/** Record the outcome of the production PHP contract, including link identity and rejected-write purity. */
function print_link_result(ast_declaration_interface $head, ast_declaration_interface $element): void
{
	$before = $head->duplicate_element();
	try {
		$head->link_duplicate($element);
		if ($head->duplicate_element() !== $element) throw new RuntimeException('Wrong duplicate identity');
		echo '1';
	}
	catch (LogicException $failure) {
		if ($failure->getMessage() !== 'Invalid duplicate declaration link' || $head->duplicate_element() !== $before) {
			throw $failure;
		}
		echo '0';
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
$replacement = match ($argv[1]) {
	'Local_Declaration' => new ast_local_declaration(0, 1, 'item', null, new ast_integer_expression(0, 1)),
	'Parameter_Declaration' => new ast_parameter_declaration('item', new ast_type_reference(0, 1, 'int'), 0, 0, 1),
	'Global_Declaration' => new ast_global_declaration(0, 1, 'item', null, null, new ast_integer_expression(0, 1)),
};

print_link_result($local, $local);
print_link_result($local, $global);
print_link_result($field, $case);
print_link_result($local, $other);
print_link_result($local, $parameter);
print_link_result($local, $parameter);
print_link_result(new ast_local_declaration(0, 1, 'item', null, new ast_integer_expression(0, 1)), $local);
print_link_result($record, $enumeration);

$local->clear_duplicate();
print_link_result($local, $replacement);
if ($parameter->duplicate_element() !== null) throw new RuntimeException('Previous successor was changed');
