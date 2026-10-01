<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/s2s_proof.php';

/** Compare syntax/scopes independently of retained preparation and dependency bookkeeping. */
function s2s_snapshot(parsed_file $syntax): string
{
	$seen = new \SplObjectStorage();
	$visit = function ($value) use (&$visit, $seen)
	{
		if (is_array($value)) {
			return array_map($visit, $value);
		}
		if (!is_object($value) || $value instanceof \UnitEnum) {
			return $value;
		}
		if (isset($seen[$value])) {
			return ['ref' => $seen[$value]];
		}
		$seen[$value] = spl_object_id($value);
		$result = ['class' => get_class($value), 'id' => spl_object_id($value)];
		foreach ((new \ReflectionClass($value))->getProperties() as $property) {
			// Successful preparation settles the body dirty flag without changing source syntax.
			if (in_array($property->name, ['prepared_facts', 'preparation', 'body_preparation', 'body_work', 'syntax_changed', 'prepared', 'preparation_lookups', 'change_status', 'preparation_changes'], true)) {
				continue;
			}
			$result[$property->name] = $visit($property->getValue($value));
		}
		return $result;
	};
	return serialize($visit($syntax));
}

/** Standalone source setup also verifies private roots publish through global scope. */
function s2s_parse(string $text): parsed_file
{
	$input = new file();
	$input->path = 's2s.phs';
	$input->content = $text;
	$syntax = (new Parser((new Tokenizer($input))->tokenize()))->parse();
	$module = new module('.', Source_Registry::normalize('.'), '.');
	$modules /** Keyed_Storage<module> */ = Model::$modules;
	$modules->add($module->name, $module);
	$record = Source_Registry::add($module, $input);
	Source_Publication::publish_parsed($record, $syntax);
	return $syntax;
}

S2S_Proof::run();
$directory = $argv[1];
$cases = [
	'float_explicit' => ['$a float = 10.5; return $a;', 10],
	'float_copy' => ['$a = 10.5; $b float = $a; $a = .5; return $b;', 10],
	'float_reassign' => ['$a = 10.5; $a = 2.5; return $a;', 2],
	'float_direct' => ['return 10.e-1;', 1],
	'bool_true' => ['$a = true; return $a;', 1],
	'bool_false' => ['$a = false; return $a;', 0],
	'bool_explicit' => ['$a bool = true; return $a;', 1],
	'bool_copy' => ['$a = true; $b bool = $a; $a = false; return $b;', 1],
	'bool_reassign' => ['$a = true; $a = false; return $a;', 0],
	'bool_direct' => ['return false;', 0],
	'bool_and_int' => ['$a = true; $b = 7; return $b;', 7],
	'cast_int' => ['$b = 3.5; $a = (int)$b; return $a;', 3],
	'cast_float' => ['$b = 4; $a = (float)$b; return $a;', 4],
	'cast_bool' => ['$b = 1; $a = (bool)$b; return $a;', 1],
	'cast_string' => ['$b = 7; $a = (string)$b;', 0],
	'cast_identity' => ['$b = 7; $a = (int)$b; return $a;', 7],
	'cast_fixed_width' => ['$b = 7; $a = (uint8)$b; return $a;', 7],
	'cast_alias_identity' => ['$b uint8 = 7; $a = (byte)$b; return $a;', 7],
	'cast_field' => ['struct Box { int32 $value; } $box Box; $box->value = 9; return (int)$box->value;', 9],
	'cast_nested' => ['$b = 2; $a = (string)(float)$b;', 0],
	'string_single' => ['$a = \'x\';', 0],
	'string_double' => ['$a = "x";', 0],
	'string_empty_single' => ['$a = \'\';', 0],
	'string_empty_double' => ['$a = "";', 0],
	'string_explicit' => ['$x string = "test"; $x = "next";', 0],
	'constant_int_max' => ['$a = PHP_INT_MAX;', 0],
	'constant_name_call' => ['function PHP_INT_MAX(): int { return 3; } return PHP_INT_MAX();', 3],
	'constant_namespaces' => ['$PHP_INT_MAX = 1; $a = PHP_INT_MAX; return $PHP_INT_MAX;', 1],
	'chain' => ['$a = $b = 1; return $a;', 1],
	'chain_deep' => ['$a = $b = $c = 4; return $a;', 4],
	'chain_existing_outer' => ['$a = 2; $a = $b = 1; return $a;', 1],
	'chain_existing_inner' => ['$b = 2; $a = $b = 1; return $b;', 1],
	'chain_same_target' => ['$a = $a = 1; return $a;', 1],
	'chain_conversion' => ['$b uint8 = 0; $a = $b = 257; return $a;', 1],
	'chain_call_once' => ['function value(): int { return 5; } $a = $b = value(); return $a;', 5],
	'literal' => ['$a = 10;', 0],
	'value' => ['$a = 10; return $a;', 10],
	'explicit' => ['$a int = 10; return $a;', 10],
	'var_chain_002' => ['$a = 1; $b = $a;', 0],
	'var_chain_003' => ['$a = 1; $b = $a; $c = $b;', 0],
	'var_chain_004' => ['$a = 1; $b = $a + 1;', 0],
	'var_reassign_002' => ['$a = 1; $a = $a + 1;', 0],
	'var_reassign_002_value' => ['$a = 1; $a = $a + 1; return $a;', 2],
	'var_reassign_003' => ['$a = 1; $a = $a + $a;', 0],
	'var_reassign_003_value' => ['$a = 1; $a = $a + $a; return $a;', 2],
	'expr_arith_001' => ['$a = 1 + 2; return $a;', 3],
	'sub_catalog' => ['$a = 1 - 2; return $a + 2;', 1],
	'sub_left' => ['return 10 - 3 - 2;', 5],
	'sub_grouped' => ['return 10 - (3 - 2);', 9],
	'sub_mixed' => ['return 10 - 3 + 2;', 9],
	'sub_mixed_reverse' => ['return 10 + 3 - 2;', 11],
	'sub_reassignment' => ['$a = 10; $a = $a - 3; return $a;', 7],
	'sub_cast' => ['$x uint8 = 7; return (int)$x - 2;', 5],
	'sub_minimum' => ['$a = 0 - PHP_INT_MAX - 1; return $a + PHP_INT_MAX + 1;', 0],
	'sub_maximum' => ['$a = (PHP_INT_MAX) - 0; return 0;', 0],
	'div_catalog' => ['$a = 4 / 2; return $a;', 2],
	'div_truncate' => ['return 7 / 3;', 2],
	'div_negative' => ['return (0 - 7) / 3 + 10;', 8],
	'div_negative_rhs' => ['return 7 / (0 - 3) + 10;', 8],
	'div_precedence' => ['return 20 / 2 * 3 + 1;', 31],
	'div_grouped' => ['return 20 / (2 * 2);', 5],
	'div_minimum' => ['$a = (0 - PHP_INT_MAX - 1) / 1; return $a + PHP_INT_MAX + 1;', 0],
	'div_zero_guard' => ['function fail(): int { return 1 / 0; } return 0;', 0],
	'mod_catalog' => ['$a = 5 % 2; return $a;', 1],
	'mod_negative' => ['return (0 - 7) % 3 + 10;', 9],
	'mod_negative_rhs' => ['return 7 % (0 - 3);', 1],
	'mod_precedence' => ['return 20 % 6 * 3 + 1;', 7],
	'mod_grouped' => ['return 20 % (6 * 3);', 2],
	'mod_zero_guard' => ['function fail(): int { return 1 % 0; } return 0;', 0],
	'concat_literals' => ['$a = "a" . "b";', 0],
	'concat_left' => ['$b = "b"; $a = $b . "x";', 0],
	'concat_right' => ['$b = "b"; $a = "x" . $b;', 0],
	'concat_cast' => ['$a = "x" . (string)(2 + 3 * 4);', 0],
	'concat_grouped' => ['$a = ("a" . "b") . "c";', 0],
	'concat_empty' => ['$a = "" . "x" . "";', 0],
	'concat_statement' => ['"a" . "b"; return 0;', 0],
	'expr_statement' => ['$a = 1; $a + 1; return $a;', 1],
	'expr_statement_grouped' => ['$a = 1; ($a + 1) * 2; return $a;', 1],
	'logical_and_table' => ['return (int)(false && false) + (int)(false && true) * 2 + (int)(true && false) * 4 + (int)(true && true) * 8;', 8],
	'logical_or_table' => ['return (int)(false || false) + (int)(false || true) * 2 + (int)(true || false) * 4 + (int)(true || true) * 8;', 14],
	'logical_precedence' => ['return true || false && false;', 1],
	'logical_grouped' => ['return (true || false) && false;', 0],
	'logical_compare' => ['return 1 + 2 < 4 && 7 % 3 == 1;', 1],
	'logical_and_skip' => ['return false && (1 / 0 == 0);', 0],
	'logical_or_skip' => ['return true || (1 % 0 == 0);', 1],
	'logical_and_guard' => ['function fail(): bool { return true && (1 / 0 == 0); } return 0;', 0],
	'logical_or_guard' => ['function fail(): bool { return false || (1 % 0 == 0); } return 0;', 0],
	'nested_catalog' => ['$b = 3; $a = ($b + 1) * 2; return $a;', 8],
	'chain_catalog' => ['$b = 2; $c = 4; $a = $b + 1 + $c; return $a;', 7],
	'compare_precedence' => ['return 2 + 3 * 4 > 13;', 1],
	'compare_constant' => ['return PHP_INT_MAX < 1;', 0],
	'compare_grouped_constant' => ['return (PHP_INT_MAX) >= 1;', 1],
	'compare_limits' => ['return (0 - PHP_INT_MAX - 1) < PHP_INT_MAX;', 1],
	'compare_three_way_less' => ['$a = 1 <=> 2; return $a + 1;', 0],
	'compare_three_way_equal' => ['return 2 <=> 2;', 0],
	'compare_three_way_greater' => ['return 3 <=> 2;', 1],
	'compare_three_way_limits' => ['return PHP_INT_MAX <=> (0 - PHP_INT_MAX - 1);', 1],
	'mul_catalog' => ['$a = 2 * 3; return $a;', 6],
	'mul_precedence_right' => ['return 2 + 3 * 4 - 5;', 9],
	'mul_precedence_left' => ['return 2 * 3 + 4;', 10],
	'mul_grouped' => ['return (2 + 3) * 4;', 20],
	'mul_chain' => ['return 2 * 3 * 4;', 24],
	'mul_cast' => ['$x uint8 = 3; return (int)$x * 2;', 6],
	'mul_reassignment' => ['$a = 3; $a = $a * 2; return $a;', 6],
	'mul_assignment_chain' => ['$a = $b = 2 + 3 * 4; return $a + $b;', 28],
	'mul_zero' => ['return PHP_INT_MAX * 0;', 0],
	'mul_negative' => ['$a = (0 - 3) * 2; return $a + 6;', 0],
	'mul_wide' => ['$a = 3037000499 * 3037000499; return 0;', 0],
	'group_catalog' => ['$b = 2; $a = ($b + 1); return $a;', 3],
	'group_right' => ['return 1 + (2 + 3);', 6],
	'group_left' => ['return ((1 + 2)) + 3;', 6],
	'group_cast_operand' => ['return (int)(1 + 2);', 3],
	'group_cast_result' => ['$x = 3.5; return ((int)$x) + 2;', 5],
	'group_constant' => ['$a = (PHP_INT_MAX); return 0;', 0],
	'group_bool' => ['return ((true));', 1],
	'group_reference' => ['function set(int &$x): void { $x = 13; } $x = 1; set((($x))); return $x;', 13],
	'group_field_reference' => ['struct Box { int32 $value; } function set(int32 &$x): void { $x = 17; } $box Box; set((($box)->value)); return ($box)->value;', 17],
	'group_call_once' => ['function bump(int &$x): int { $x = $x + 1; return $x; } $x = 0; $a = (bump($x)); return $x;', 1],
	'copy' => ['$a = 10; $b = $a; $a = 12; return $b;', 10],
	'wide' => ['$a = 4294967296; return 7;', 7],
	'maximum' => ['$a = 9223372036854775807; return 9;', 9],
	'keyword' => ['$int = 10; return $int;', 10],
	'ident_var_001' => ['function f(int $int): void { $while = $int; }', 0],
	'ident_var_001_prefix_collision' => ['function f(int $int, int $local_int): void { $while = $int; $local_while = $local_int; }', 0],
	'ident_var_escaping' => ['function _f(int $_x, int $U_x, int $a__b, int $aU_U_b): int { $_copy = $_x; return $_copy; } return _f(1, 2, 3, 4);', 1],
	'ident_record_escaping' => ['struct _Box__U { int32 $_field__U; } $value _Box__U; $value->_field__U = 5; return $value->_field__U;', 5],
];
// Ordinary functions and value structs exercise the existing frontend without adding syntax.
$cases += [
	'function_no_arguments' => ['function value(): int { return 13; } return value();', 13],
	'function_void' => ['function done(): void { return; } done(); return 0;', 0],
	'function_mixed_parameters' => ['function accept(int $x, float $y, bool $z): void {} accept(1, 2.5, true); return 0;', 0],
	'function_local_shadow' => ['$x = 7; function own(): void { $x = 12; } own(); return $x;', 7],
	'struct_argument_order' => ['struct Item { int32 $value; } function change(Item &$x): int { $x->value = 99; return 0; } function first(Item $x, int $unused): int32 { return $x->value; } $x Item; $x->value = 67; return first($x, change($x));', 67],
	'integer_alias_reference' => ['function change(uint8 &$x): void { $x = 71; } $x byte = 0; change($x); return $x;', 71],
	'function_forward' => ['return identity(19); function identity(int $x): int { return $x; }', 19],
	'function_nested' => ['function identity(int $x): int { return $x; } return identity(identity(23));', 23],
	'function_locals' => ['$x = 7; function own(int $x): int { $x = 12; return $x; } own($x); return $x;', 7],
	'function_reference' => ['function set(int &$x): void { $x = 29; return; } $x = 1; set($x); return $x;', 29],
	'function_forward_reference' => ['function forward(int &$x): void { set($x); } function set(int &$x): void { $x = 31; } $x = 1; forward($x); return $x;', 31],
	'function_argument_order' => ['function change(int &$x): int { $x = 33; return $x; } function first(int $a, int $b): int { return $a; } $x = 11; return first($x, change($x));', 11],
	'function_reference_order' => ['function change(int &$x): int { $x = 33; return $x; } function first(int &$a, int $b): int { return $a; } $x = 11; return first($x, change($x));', 33],
	'function_bool' => ['function identity(bool $x): bool { return $x; } return identity(true);', 1],
	'function_float' => ['function identity(float $x): float { return $x; } return identity(10.5);', 10],
	'function_recursive_signature' => ['function first(int $x): int { return second($x); } function second(int $x): int { return first($x); } return 0;', 0],
	'struct_fields' => ['struct Point { int32 $x; public bool $ok; } $p Point; $p->x = 17; $p->ok = true; return $p->x;', 17],
	'struct_default' => ['struct Point { uint16 $x; } $p Point; return $p->x;', 0],
	'struct_copy' => ['struct Point { int32 $x; } $p Point; $p->x = 19; $q = $p; $p->x = 21; return $q->x;', 19],
	'struct_assignment' => ['struct Point { int32 $x; } $p Point; $q Point; $p->x = 25; $q = $p; $p->x = 27; return $q->x;', 25],
	'struct_nested_forward' => ['struct Outer { Inner $inner; } struct Inner { uint8 $value; } $a Outer; $a->inner->value = 37; return $a->inner->value;', 37],
	'struct_value_parameter' => ['struct Point { int32 $x; } function change(Point $p): void { $p->x = 99; } $p Point; $p->x = 41; change($p); return $p->x;', 41],
	'struct_reference_parameter' => ['struct Point { int32 $x; } function change(Point &$p): void { $p->x = 43; } $p Point; change($p); return $p->x;', 43],
	'struct_return' => ['function make(): Point { $p Point; $p->x = 47; return $p; } struct Point { int32 $x; } $p = make(); return $p->x;', 47],
	'struct_field_reference' => ['struct Point { int32 $x; } function change(int32 &$x): void { $x = 53; } $p Point; change($p->x); return $p->x;', 53],
	'struct_empty' => ['struct Empty {} function copy(Empty $e): Empty { return $e; } $e Empty; $f = copy($e); return 0;', 0],
	'integer_boundary' => ['function identity(uint16 $x): uint8 { return $x; } $x int32 = 59; return identity($x);', 59],
];
// Each fixture independently distinguishes true, false and equality-boundary behavior.
$comparison_cases = [
	'equal' => ['==', 3, 4, 3], 'not_equal' => ['!=', 4, 3, 3],
	'identical' => ['===', 3, 4, 3], 'not_identical' => ['!==', 4, 3, 3],
	'less' => ['<', 4, 3, 3], 'less_equal' => ['<=', 3, 2, 3],
	'greater' => ['>', 2, 3, 3], 'greater_equal' => ['>=', 3, 4, 3],
];
foreach ($comparison_cases as $name => [$symbol, $true_rhs, $false_rhs, $left]) {
	$cases['compare_' . $name] = ['$yes bool = ' . $left . ' ' . $symbol . ' ' . $true_rhs . '; '
		. '$no bool = ' . $left . ' ' . $symbol . ' ' . $false_rhs . '; return (int)$yes + (int)$no * 2;', 1];
}
foreach (['int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64'] as $type) {
	$cases['field_' . $type] = ['struct Item { ' . $type . ' $value; } $x Item; $x->value = 61; return $x->value;', 61];
}
// Exact spellings cover decimal grammar, precision, normal limits and subnormals.
$float_forms = ['10.5', '.5', '10.', '1e3', '1E+3', '1.25e-3', '.5e2', '10.e-1',
	'1.2345678901234567', '0.0', '0008.5', '08e0', '1.7976931348623157e308',
	'2.2250738585072014e-308', '4.9406564584124654e-324'];
foreach ($float_forms as $index => $spelling) {
	$cases['float_form_' . $index] = ['$a = ' . $spelling . ';', 0];
}
$explicit_declarations = [
	'explicit' => ['scpp::int_t<>', 'local_a'],
	'float_explicit' => ['scpp::float_t', 'local_a'],
	'bool_explicit' => ['scpp::bool_t', 'local_a'],
	'string_explicit' => ['scpp::string_t', 'local_x'],
];
$executions = [];
foreach ($cases as $name => [$source, $exit])
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$compiler = new Compiler();
	$compiler->prepare();
	$compiler->cpp();
	$path = $directory . '/' . $name . '.cpp';
	file_put_contents($path, Model::$cpp_files[0]->text);
	$executions[] = ['path' => $path, 'exit_code' => $exit];
	// Verify actual member representation, not merely that small integer values survive.
	if (str_starts_with($name, 'field_')) {
		$alias = substr($name, strlen('field_'));
		$native = $alias === 'byte' ? 'uint8' : $alias;
		$probe = 'static_assert(std::is_same_v<decltype(record_Item{}.field_value), scpp::int_t<std::' . $native . '_t>>);';
		file_put_contents($path, Model::$cpp_files[0]->text . "\n" . $probe . "\n");
	}

	if (($name === 'bool_true') || ($name === 'bool_false')) {
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::bool_t>);\n";
		$probe_path = $directory . '/' . $name . '_type.cpp';
		$probe_text = str_replace("\treturn static_cast<int>", $probe . "\treturn static_cast<int>", Model::$cpp_files[0]->text);
		file_put_contents($probe_path, $probe_text);
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}

	if (in_array($name, ['string_single', 'string_double', 'string_empty_single', 'string_empty_double'], true)) {
		$text = Model::$cpp_files[0]->text;
		$expected_value = str_contains($name, 'empty') ? '' : 'x';
		if (!str_contains($text, 'auto local_a = scpp::string_t("' . $expected_value . '");')) {
			throw new \LogicException('String literal did not use its canonical C++ representation');
		}
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::string_t>);\n";
		$probe .= "\tif (local_a.native_value() != std::string(\"" . $expected_value . '", '
			. string_byte_len($expected_value) . ")) { return 91; }\n";
		file_put_contents($path, str_replace("\treturn 0;", $probe . "\treturn 0;", $text));
	}

	if ($name === 'constant_int_max')
	{
		$text = Model::$cpp_files[0]->text;
		$assignment = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$constant = object_cast($assignment->value, constant_reference_node::class);
		$facts = $constant->require_constant_reference_preparation();
		$definition = object_cast($facts->definition, integer_constant_definition::class);
		if (($definition !== Model::$language_scope->constants_named('PHP_INT_MAX')[0]) ||
			(!$facts->type->matches(Language_Types::integer(Model::$language_scope))) ||
			($definition->decimal !== '9223372036854775807') || $facts->addressable ||
			!str_contains($text, 'auto local_a = static_cast<scpp::int_t<>>(9223372036854775807LL);') ||
			!str_contains($text, '#include "scpp/int_t.hpp"') || str_contains($text, 'string_support.hpp')) {
			throw new \LogicException('Constant reference lost its definition, type, immutability or canonical lowering');
		}
	}

	if ($name === 'chain')
	{
		$text = Model::$cpp_files[0]->text;
		$outer = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$inner = object_cast($outer->value, assignment_expression_node::class);
		$outer_binding = $outer->require_assignment_preparation()->binding;
		$inner_binding = $inner->require_assignment_preparation()->binding;
		$expected = "\tauto local_b = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tauto local_a = local_b;\n";
		if (($inner_binding->resolved_kind !== binding_kind::declaration) ||
			($outer_binding->resolved_kind !== binding_kind::declaration) ||
			(!$inner_binding->type->matches($outer_binding->type)) || !str_contains($text, $expected)) {
			throw new \LogicException('Assignment chain lost right-associative facts or inner-first lowering');
		}
	}

	if ($name === 'chain_deep') {
		$expected = "\tauto local_c = static_cast<scpp::int_t<>>(4LL);\n"
			. "\tauto local_b = local_c;\n"
			. "\tauto local_a = local_b;\n";
		if (!str_contains(Model::$cpp_files[0]->text, $expected)) {
			throw new \LogicException('Deep assignment chain was not flattened from the innermost write');
		}
	}

	if ($name === 'chain_same_target')
	{
		$outer = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$inner = object_cast($outer->value, assignment_expression_node::class);
		$outer_binding = $outer->require_assignment_preparation()->binding;
		$inner_binding = $inner->require_assignment_preparation()->binding;
		$text = Model::$cpp_files[0]->text;
		if (($inner_binding->resolved_kind !== binding_kind::declaration) ||
			($outer_binding->resolved_kind !== binding_kind::assignment) ||
			(weakref_get($outer_binding->declaration) !== weakref_get($inner_binding->declaration)) ||
			(substr_count($text, 'auto local_a =') !== 1) ||
			!str_contains($text, "\tlocal_a = local_a;\n")) {
			throw new \LogicException('Repeated-name chain created two declarations or lost storage identity');
		}
	}

	if (($name === 'chain_conversion') &&
		(!str_contains(Model::$cpp_files[0]->text, 'local_b = static_cast<scpp::int_t<std::uint8_t>>((static_cast<scpp::int_t<>>(257LL)).native_value());') ||
		 !str_contains(Model::$cpp_files[0]->text, 'auto local_a = local_b;'))) {
		throw new \LogicException('Assignment chain copied the unconverted RHS instead of the stored inner value');
	}

	if (($name === 'chain_call_once') && (substr_count(Model::$cpp_files[0]->text, 'return function_value();') !== 1)) {
		throw new \LogicException('Assignment chain evaluated its call RHS more than once');
	}

	if ($name === 'var_chain_002')
	{
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tauto local_b = local_a;\n";
		$text = Model::$cpp_files[0]->text;
		if (!str_contains($text, $expected) || str_contains($text, 'auto local_b = static_cast')) {
			throw new \LogicException('VAR-CHAIN-002 lost sequential declaration order or direct copy lowering');
		}
	}

	if ($name === 'var_chain_003')
	{
		$first = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$second = object_cast($syntax->root->body->statements[1]->expression, assignment_expression_node::class);
		$third = object_cast($syntax->root->body->statements[2]->expression, assignment_expression_node::class);
		$second_source = object_cast($second->value, variable_reference_node::class);
		$third_source = object_cast($third->value, variable_reference_node::class);
		$first_facts = $first->require_assignment_preparation();
		$second_facts = $second->require_assignment_preparation();
		$third_facts = $third->require_assignment_preparation();
		$first_identity = weakref_get($first_facts->binding->declaration);
		$second_identity = weakref_get($second_facts->binding->declaration);
		$third_identity = weakref_get($third_facts->binding->declaration);
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tauto local_b = local_a;\n"
			. "\tauto local_c = local_b;\n";
		$text = Model::$cpp_files[0]->text;
		if (($first_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($second_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($third_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($first_identity === $second_identity) || ($second_identity === $third_identity) ||
			($first_identity === $third_identity) ||
			(weakref_get($second_source->require_variable_reference_preparation()->declaration) !== $first_identity) ||
			(weakref_get($third_source->require_variable_reference_preparation()->declaration) !== $second_identity) ||
			(!$first_facts->type->matches($second_facts->type)) || (!$second_facts->type->matches($third_facts->type)) ||
			!str_contains($text, $expected) || str_contains($text, 'auto local_b = static_cast') ||
			str_contains($text, 'auto local_c = static_cast')) {
			throw new \LogicException('VAR-CHAIN-003 lost source-order identities, canonical type or direct copy lowering');
		}
	}

	if ($name === 'var_chain_004')
	{
		$first = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$second = object_cast($syntax->root->body->statements[1]->expression, assignment_expression_node::class);
		$binary = object_cast($second->value, binary_expression_node::class);
		$left = object_cast($binary->left, variable_reference_node::class);
		$right = object_cast($binary->right, integer_literal_node::class);
		$first_facts = $first->require_assignment_preparation();
		$second_facts = $second->require_assignment_preparation();
		$binary_facts = $binary->require_binary_preparation();
		$integer_type = Language_Types::integer(Model::$language_scope);
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tauto local_b = (local_a + static_cast<scpp::int_t<>>(1LL));\n";
		$text = Model::$cpp_files[0]->text;
		if (($binary_facts->decision->operation !== operator_operation::integer_addition)
			|| (!$binary_facts->type->matches($integer_type)) ||
			$binary_facts->addressable || (!$left->require_preparation()->type->matches($integer_type)) ||
			(!$right->require_preparation()->type->matches($integer_type)) || (!$second_facts->type->matches($integer_type)) ||
			(weakref_get($left->require_variable_reference_preparation()->declaration) !== weakref_get($first_facts->binding->declaration)) ||
			!str_contains($text, $expected) || !str_contains($text, '#include "scpp/generated/operators.hpp"')) {
			throw new \LogicException('VAR-CHAIN-004 lost integer addition facts, source identity or normalized lowering');
		}
	}

	if ($name === 'var_reassign_002')
	{
		$first = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$second = object_cast($syntax->root->body->statements[1]->expression, assignment_expression_node::class);
		$binary = object_cast($second->value, binary_expression_node::class);
		$self_reference = object_cast($binary->left, variable_reference_node::class);
		$first_facts = $first->require_assignment_preparation();
		$second_facts = $second->require_assignment_preparation();
		$binary_facts = $binary->require_binary_preparation();
		$declaration = weakref_get($first_facts->binding->declaration);
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tlocal_a = (local_a + static_cast<scpp::int_t<>>(1LL));\n";
		$text = Model::$cpp_files[0]->text;
		if (($first_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($second_facts->binding->resolved_kind !== binding_kind::assignment) ||
			(weakref_get($second_facts->binding->declaration) !== $declaration) ||
			(weakref_get($self_reference->require_variable_reference_preparation()->declaration) !== $declaration) ||
			($binary_facts->decision->operation !== operator_operation::integer_addition) ||
			(!$first_facts->type->matches($binary_facts->type)) || (!$second_facts->type->matches($binary_facts->type)) ||
			!str_contains($text, $expected) || (substr_count($text, 'auto local_a =') !== 1)) {
			throw new \LogicException('VAR-REASSIGN-002 redeclared its target or lost self-read/addition facts');
		}
	}

	if ($name === 'var_reassign_003')
	{
		$first = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$second = object_cast($syntax->root->body->statements[1]->expression, assignment_expression_node::class);
		$binary = object_cast($second->value, binary_expression_node::class);
		$left = object_cast($binary->left, variable_reference_node::class);
		$right = object_cast($binary->right, variable_reference_node::class);
		$first_facts = $first->require_assignment_preparation();
		$second_facts = $second->require_assignment_preparation();
		$binary_facts = $binary->require_binary_preparation();
		$left_facts = $left->require_variable_reference_preparation();
		$right_facts = $right->require_variable_reference_preparation();
		$declaration = weakref_get($first_facts->binding->declaration);
		$expected = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
			. "\tlocal_a = (local_a + local_a);\n";
		$text = Model::$cpp_files[0]->text;
		if (($first_facts->binding->resolved_kind !== binding_kind::declaration) ||
			($second_facts->binding->resolved_kind !== binding_kind::assignment) ||
			(weakref_get($second_facts->binding->declaration) !== $declaration) ||
			($left === $right) || ($left_facts === $right_facts) ||
			(weakref_get($left_facts->declaration) !== $declaration) ||
			(weakref_get($right_facts->declaration) !== $declaration) ||
			($binary_facts->decision->operation !== operator_operation::integer_addition) ||
			(!$left_facts->type->matches($binary_facts->type)) || (!$right_facts->type->matches($binary_facts->type)) ||
			(!$second_facts->type->matches($binary_facts->type)) || !str_contains($text, $expected) ||
			(substr_count($text, 'auto local_a =') !== 1)) {
			throw new \LogicException('VAR-REASSIGN-003 lost distinct reads, shared identity or reassignment lowering');
		}
	}

	if ($name === 'ident_var_001')
	{
		$function = object_cast($syntax->root->declarations[0], function_node::class);
		$parameter = $function->parameters[0];
		$assignment = object_cast($function->body->statements[0]->expression, assignment_expression_node::class);
		$target = object_cast($assignment->target, variable_reference_node::class);
		$source = object_cast($assignment->value, variable_reference_node::class);
		$parameter_facts = $parameter->require_preparation();
		$assignment_facts = $assignment->require_assignment_preparation();
		$source_facts = $source->require_variable_reference_preparation();
		$parameter_identity = weakref_get($parameter_facts->declaration);
		$local_identity = weakref_get($assignment_facts->binding->declaration);
		$signature = 'void function_f(scpp::int_t<> local_int)';
		$definition = $signature . "\n{\n\tauto local_while = local_int;\n}";
		$text = Model::$cpp_files[0]->text;
		if (($function->name !== 'f') || ($function->occurrence()->name !== 'f') ||
			($parameter->name !== 'int') || ($parameter->occurrence()->name !== 'int') ||
			($target->name !== 'while') || ($target->occurrence()->name !== 'while') ||
			($source->name !== 'int') || ($source->occurrence()->name !== 'int') ||
			($parameter_identity === $local_identity) ||
			(weakref_get($source_facts->declaration) !== $parameter_identity) ||
			(!$parameter_facts->type->matches($source_facts->type)) || (!$source_facts->type->matches($assignment_facts->type)) ||
			(substr_count($text, $signature) !== 2) || !str_contains($text, $definition) ||
			str_contains($text, 'int__') || str_contains($text, 'while__')) {
			throw new \LogicException('IDENT-VAR-001 lost role-prefixed names, identity or signature consistency');
		}
	}

	if ($name === 'ident_var_001_prefix_collision')
	{
		$text = Model::$cpp_files[0]->text;
		$signature = 'void function_f(scpp::int_t<> local_int, scpp::int_t<> local_localU_int)';
		$body = "\tauto local_while = local_int;\n\tauto local_localU_while = local_localU_int;\n";
		if ((substr_count($text, $signature) !== 2) || !str_contains($text, $body)) {
			throw new \LogicException('Role prefixes collided with source names containing the same prefix');
		}
	}

	if ($name === 'ident_var_escaping')
	{
		$function = object_cast($syntax->root->declarations[0], function_node::class);
		$assignment = object_cast($function->body->statements[0]->expression, assignment_expression_node::class);
		$target = object_cast($assignment->target, variable_reference_node::class);
		$source = object_cast($assignment->value, variable_reference_node::class);
		$call = object_cast(object_cast($syntax->root->body->statements[0], return_node::class)->expression, call_node::class);
		$text = Model::$cpp_files[0]->text;
		$signature = 'scpp::int_t<> function_U_f(scpp::int_t<> local_U_x, scpp::int_t<> local_UUU_x, '
			. 'scpp::int_t<> local_aU_U_b, scpp::int_t<> local_aUUU_UUU_b)';
		if (($function->name !== '_f') || ($call->name !== '_f') ||
			($function->parameters[0]->name !== '_x') || ($function->parameters[1]->name !== 'U_x') ||
			($function->parameters[2]->name !== 'a__b') || ($function->parameters[3]->name !== 'aU_U_b') ||
			($target->name !== '_copy') || ($source->name !== '_x') ||
			(substr_count($text, $signature) !== 2) ||
			!str_contains($text, 'auto local_U_copy = local_U_x;') ||
			!str_contains($text, 'return function_U_f(argument_0, argument_1, argument_2, argument_3);') ||
			str_contains($text, '__')) {
			throw new \LogicException('NOTE-021 lost raw names, reversible escaping or consistent function spelling');
		}
	}

	if ($name === 'ident_record_escaping')
	{
		$text = Model::$cpp_files[0]->text;
		if (!str_contains($text, "struct record_U_BoxU_U_UU\n") ||
			(substr_count($text, 'record_U_BoxU_U_UU local_value') !== 1) ||
			!str_contains($text, 'scpp::int_t<std::int32_t> field_U_fieldU_U_UU;') ||
			(substr_count($text, '.field_U_fieldU_U_UU') !== 2) || str_contains($text, '__')) {
			throw new \LogicException('NOTE-021 did not share identifier escaping across record and field roles');
		}
	}

	if ($name === 'function_locals')
	{
		$function = object_cast($syntax->root->declarations[0], function_node::class);
		$parameter = $function->parameters[0]->require_preparation();
		$function_assignment = object_cast($function->body->statements[0]->expression, assignment_expression_node::class);
		$function_binding = $function_assignment->require_assignment_preparation()->binding;
		$entry_assignment = object_cast($syntax->root->body->statements[0]->expression, assignment_expression_node::class);
		$entry_binding = $entry_assignment->require_assignment_preparation()->binding;
		$parameter_identity = weakref_get($parameter->declaration);
		$entry_identity = weakref_get($entry_binding->declaration);
		$text = Model::$cpp_files[0]->text;
		$signature = 'scpp::int_t<> function_own(scpp::int_t<> local_x)';
		if (($entry_binding->resolved_kind !== binding_kind::declaration) ||
			($function_binding->resolved_kind !== binding_kind::assignment) ||
			(weakref_get($function_binding->declaration) !== $parameter_identity) ||
			($entry_identity === $parameter_identity) ||
			(substr_count($text, $signature) !== 2) ||
			(substr_count($text, 'auto local_x = static_cast<scpp::int_t<>>(7LL);') !== 1) ||
			(substr_count($text, 'local_x = static_cast<scpp::int_t<>>(12LL);') !== 1) ||
			!str_contains($text, 'scpp::int_t<> argument_0 = local_x;') ||
			str_contains($text, 'argument_0 = static_cast<scpp::int_t<>>') ||
			!str_contains($text, 'return function_own(argument_0);') ||
			!str_contains($text, 'return static_cast<int>((local_x).native_value());')) {
			throw new \LogicException('NOTE-033.a lost executable-unit isolation or parameter-seeded reassignment');
		}
	}

	if (isset($explicit_declarations[$name]))
	{
		[$cpp_type, $cpp_name] = $explicit_declarations[$name];
		$text = Model::$cpp_files[0]->text;
		if (!str_contains($text, $cpp_type . ' ' . $cpp_name . ' = ') || str_contains($text, 'auto ' . $cpp_name . ' = ')) {
			throw new \LogicException('Explicit local declaration lost its canonical C++ type');
		}
	}

	if (str_starts_with($name, 'float_form_'))
	{
		$spelling = $float_forms[(int) substr($name, strlen('float_form_'))];
		$text = Model::$cpp_files[0]->text;
		if (!str_contains($text, 'static_cast<scpp::float_t>(' . $spelling . ')')) {
			throw new \LogicException('Float literal spelling was rounded or changed');
		}
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::float_t>);\n";
		$probe .= "\tif (local_a.native_value() != " . $spelling . ") { return 91; }\n";
		file_put_contents($path, str_replace("\treturn 0;", $probe . "\treturn 0;", $text));
	}

	// Independent native probes observe the value and type of large emitted literals.
	if (($name === 'wide') || ($name === 'maximum'))
	{
		$magnitude = $name === 'wide' ? '4294967296' : '9223372036854775807';
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::int_t<>>);\n";
		$probe .= "\tif (local_a.native_value() != " . $magnitude . "LL) { return 91; }\n";
		$probe_path = $directory . '/' . $name . '_value.cpp';
		$probe_text = str_replace("\treturn static_cast<int>", $probe . "\treturn static_cast<int>", Model::$cpp_files[0]->text);
		file_put_contents($probe_path, $probe_text);
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}

	// Verify signed values directly; process exit codes alone lose sign and high bits.
	$arithmetic_values = ['sub_catalog' => '-1LL',
		'sub_minimum' => '(-9223372036854775807LL - 1LL)', 'sub_maximum' => '9223372036854775807LL',
		'div_minimum' => '(-9223372036854775807LL - 1LL)', 'compare_three_way_less' => '-1LL',
		'mul_negative' => '-6LL', 'mul_wide' => '9223372030926249001LL'];
	if (isset($arithmetic_values[$name]))
	{
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::int_t<>>);\n";
		$probe .= "\tif (local_a.native_value() != " . $arithmetic_values[$name] . ") { return 91; }\n";
		$text = Model::$cpp_files[0]->text;
		$site = (($name === 'sub_maximum') || ($name === 'mul_wide')) ? "\treturn 0;" : "\treturn static_cast<int>";
		$probe_path = $directory . '/' . $name . '_value.cpp';
		file_put_contents($probe_path, str_replace($site, $probe . $site, $text));
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}

	$concatenation_values = ['concat_literals' => 'ab', 'concat_left' => 'bx',
		'concat_right' => 'xb', 'concat_cast' => 'x14', 'concat_grouped' => 'abc', 'concat_empty' => 'x'];
	if (isset($concatenation_values[$name]))
	{
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_a), scpp::string_t>);\n";
		$probe .= '	if (local_a.native_value() != "' . $concatenation_values[$name] . '") { return 91; }' . "\n";
		$probe_path = $directory . '/' . $name . '_value.cpp';
		file_put_contents($probe_path, str_replace("\treturn 0;", $probe . "\treturn 0;", Model::$cpp_files[0]->text));
		$executions[] = ['path' => $probe_path, 'exit_code' => 0];
	}

	// Observe runtime exceptions in-process without introducing source try/catch support.
	$runtime_error_codes = ['div_zero_guard' => 'division_by_zero', 'mod_zero_guard' => 'modulo_by_zero',
		'logical_and_guard' => 'division_by_zero', 'logical_or_guard' => 'modulo_by_zero'];
	if (isset($runtime_error_codes[$name]))
	{
		$error_code = $runtime_error_codes[$name];
		$probe = '
int main() { try { (void)function_fail(); } catch (const scpp::runtime_error& error) {'
			. ' return error.code() == "' . $error_code . '" ? 0 : 91; } return 92; }
';
		$probe_path = $directory . '/' . $name . '_throw.cpp';
		file_put_contents($probe_path, str_replace('int main()', 'int unused_entry()', Model::$cpp_files[0]->text) . $probe);
		$executions[] = ['path' => $probe_path, 'exit_code' => 0];
	}

	Preparation_Cleanup::tree($syntax->root);
	if (s2s_snapshot($syntax) !== $before) {
		throw new \LogicException('Preparation/emission changed source syntax or its scopes');
	}
}
$scope_isolation_rejections = [
	'$x = 1; function f(): int { return $x; }' => 'established local declaration for x',
];
foreach ($scope_isolation_rejections as $source => $diagnostic)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('NOTE-033.a allowed a function to capture an entry-body local implicitly');
	}
}

$rejections = ['function f(int &$x): void {} f(1);',
	'function f(int &$x): void {} f(((1)));',
	'function f(int $x): int { return $x; } f();',
	'function f(): int { return; }',
	'function f(): void { return 1; }',
	'function f(): void {} $x = f();',
	'struct S { uint8 $x; } $s S; $s->missing = 1;',
	'struct A {} struct B {} $a A; $b B = $a;',
	'struct S { uint8 $x; } $s S = [1];',
	'struct Box {} $box Box; $value = (int)$box;',
	'struct Box {} $box Box; $value = (Box)$box;',
	'$value = (uint8)true;',
	'function f(int &$x): void {} $x uint8 = 1; f($x);',
	'$a float = 1;', '$a int = 1.5;', '$a = 1.5; $a = false;', '$a int = true;', '$a bool = 1;', '$a = true; $a = 1;', '$a = 1; $a = false;', '$a = 9223372036854775808;', '$a = 010;', '$a = $a;', '$a = $b = $a;', '$b = true; $a = $b = 1;', '$a = unknown();', '$a = UNKNOWN_CONSTANT;', '$a = php_int_max;', '$a void;', "return 'x';", 'template<T> function f(): int { return 1; }'];
foreach ($rejections as $source)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
		(new Compiler())->cpp();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Unsupported generation published output');
	}
}

// A fresh local copies one established value; source and target keep distinct identities.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$b int = 11; $a = $b; $b = 19; return $a;');
$children = $syntax->root->body->statements;
$source_declaration = object_cast($children[0], variable_declaration_node::class);
$copy_assignment = object_cast($children[1]->expression, assignment_expression_node::class);
$source_reference = object_cast($copy_assignment->value, variable_reference_node::class);
$source_mutation = object_cast($children[2]->expression, assignment_expression_node::class);
$copy_reference = object_cast($children[3]->expression, variable_reference_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$source_binding = $source_declaration->require_preparation();
$copy_facts = $copy_assignment->require_assignment_preparation();
$copy_binding = $copy_facts->binding;
$source_reference_facts = $source_reference->require_variable_reference_preparation();
$mutation_binding = $source_mutation->require_assignment_preparation()->binding;
$copy_reference_facts = $copy_reference->require_variable_reference_preparation();
$integer_type = Language_Types::integer(Model::$language_scope);
$source_identity = weakref_get($source_binding->declaration);
$copy_identity = weakref_get($copy_binding->declaration);
if (($source_binding->resolved_kind !== binding_kind::declaration) ||
	($copy_binding->resolved_kind !== binding_kind::declaration) ||
	($mutation_binding->resolved_kind !== binding_kind::assignment) ||
	($source_identity === $copy_identity) ||
	(weakref_get($source_reference_facts->declaration) !== $source_identity) ||
	(weakref_get($mutation_binding->declaration) !== $source_identity) ||
	(weakref_get($copy_reference_facts->declaration) !== $copy_identity) ||
	(!$source_binding->type->matches($integer_type)) || (!$copy_facts->type->matches($integer_type)) ||
	(!$copy_binding->type->matches($integer_type)) || (!$source_reference_facts->type->matches($integer_type)) ||
	(!$mutation_binding->type->matches($integer_type)) || (!$copy_reference_facts->type->matches($integer_type))) {
	throw new \LogicException('Variable copy lost source order, declaration identity or canonical type');
}
$compiler->cpp();
$copy_output = Model::$cpp_files[0]->text;
if ((substr_count($copy_output, 'auto local_a = local_b;') !== 1) || str_contains($copy_output, 'auto local_a = static_cast')) {
	throw new \LogicException('Variable copy added a conversion or lost direct inferred declaration lowering');
}
Compiler_Lifecycle::reset_preparation();
if (($source_declaration->preparation() !== null) || ($copy_assignment->preparation() !== null) ||
	($source_reference->preparation() !== null) || ($source_mutation->preparation() !== null) ||
	($copy_reference->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Variable copy cleanup changed syntax or retained prepared facts');
}

$variable_copy_rejections = [
	'VAR-ORDER-001' => ['$a = $b; $b = 1;', 'established local declaration for b'],
	'self-initialization' => ['$a = $a;', 'established local declaration for a'],
];
foreach ($variable_copy_rejections as $name => [$source, $diagnostic])
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException($name . ' missed its source-order diagnostic or published partial results');
	}
}

$integer_addition_rejections = [
	'undeclared operand' => ['$b = $missing + 1;', 'established local declaration for missing'],
	'boolean operand' => ['$a = 1; $b = $a + true;', 'integer binary operation requires canonical int operands'],
	'narrow integer operand' => ['$a uint8 = 1; $b = $a + 1;', 'integer binary operation requires canonical int operands'],
	'effectful operand' => ['function value(): int { return 1; } $a = value() + 1;', 'binary operation requires order-independent operands'],
	'grouped effectful operand' => ['function value(): int { return 1; } $a = (value()) + 1;', 'binary operation requires order-independent operands'],
];
foreach ($integer_addition_rejections as $name => [$source, $diagnostic])
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Invalid integer addition ' . $name . ' missed its diagnostic or published partial results');
	}
}

// Reassignment preserves one local identity and type; explicit syntax cannot redeclare it.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 1; $a = 2; return $a;');
$children = $syntax->root->body->statements;
$declaration_assignment = object_cast($children[0]->expression, assignment_expression_node::class);
$reassignment = object_cast($children[1]->expression, assignment_expression_node::class);
$result_reference = object_cast($children[2]->expression, variable_reference_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$declaration_facts = $declaration_assignment->require_assignment_preparation();
$reassignment_facts = $reassignment->require_assignment_preparation();
$result_facts = $result_reference->require_variable_reference_preparation();
$integer_type = Language_Types::integer(Model::$language_scope);
$declaration_identity = weakref_get($declaration_facts->binding->declaration);
if (($declaration_facts->binding->resolved_kind !== binding_kind::declaration) ||
	($reassignment_facts->binding->resolved_kind !== binding_kind::assignment) ||
	(weakref_get($reassignment_facts->binding->declaration) !== $declaration_identity) ||
	(weakref_get($result_facts->declaration) !== $declaration_identity) ||
	(!$declaration_facts->type->matches($integer_type)) || (!$declaration_facts->binding->type->matches($integer_type)) ||
	(!$reassignment_facts->type->matches($integer_type)) || (!$reassignment_facts->binding->type->matches($integer_type)) ||
	(!$result_facts->type->matches($integer_type))) {
	throw new \LogicException('Reassignment lost declaration identity, outcome or canonical type');
}
$compiler->cpp();
$reassignment_output = Model::$cpp_files[0]->text;
$reassignment_spelling = "\tauto local_a = static_cast<scpp::int_t<>>(1LL);\n"
	. "\tlocal_a = static_cast<scpp::int_t<>>(2LL);\n";
if (!str_contains($reassignment_output, $reassignment_spelling) ||
	(substr_count($reassignment_output, 'auto local_a =') !== 1)) {
	throw new \LogicException('Reassignment redeclared its target or lost canonical literal lowering');
}
Compiler_Lifecycle::reset_preparation();
if (($declaration_assignment->preparation() !== null) || ($reassignment->preparation() !== null) ||
	($result_reference->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Reassignment cleanup changed syntax or retained prepared facts');
}

Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a uint8 = 1; $b = 2; $a = $b; return $a;');
$compiler = new Compiler();
$compiler->prepare();
$compiler->cpp();
if (!str_contains(Model::$cpp_files[0]->text,
	'local_a = static_cast<scpp::int_t<std::uint8_t>>((local_b).native_value());')) {
	throw new \LogicException('Reassignment omitted a required compatible-integer conversion');
}

Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 1; $a = $a; return $a;');
$children = $syntax->root->body->statements;
$self_reassignment = object_cast($children[1]->expression, assignment_expression_node::class);
$self_reference = object_cast($self_reassignment->value, variable_reference_node::class);
$compiler = new Compiler();
$compiler->prepare();
$self_binding = $self_reassignment->require_assignment_preparation()->binding;
$self_reference_facts = $self_reference->require_variable_reference_preparation();
if (($self_binding->resolved_kind !== binding_kind::assignment) ||
	(weakref_get($self_binding->declaration) !== weakref_get($self_reference_facts->declaration))) {
	throw new \LogicException('Reassignment RHS could not read the established target binding');
}

$reassignment_rejections = [
	'$a = 1; $a = false;' => 'value boundary requires matching types or an integer conversion',
	'$a int = 1; $a int = 2;' => 'local a is already declared in this scope',
];
foreach ($reassignment_rejections as $source => $diagnostic)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Invalid reassignment or duplicate declaration missed its semantic diagnostic');
	}
}

// Explicit local types are authoritative semantic boundaries, not deferred C++ failures.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$x string = "test"; $x = "next";');
$children = $syntax->root->body->statements;
$declaration = object_cast($children[0], variable_declaration_node::class);
$assignment = object_cast($children[1]->expression, assignment_expression_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$declaration_facts = $declaration->require_preparation();
$assignment_facts = $assignment->require_assignment_preparation()->binding;
$string_type = Language_Types::string_type(Model::$language_scope);
if (($declaration_facts->resolved_kind !== binding_kind::declaration) ||
	($assignment_facts->resolved_kind !== binding_kind::assignment) ||
	(!$declaration_facts->type->matches($string_type)) || (!$assignment_facts->type->matches($string_type)) ||
	(weakref_get($declaration_facts->declaration) !== weakref_get($assignment_facts->declaration))) {
	throw new \LogicException('Explicit string local lost its prepared type or declaration identity');
}
$compiler->cpp();
$typed_output = Model::$cpp_files[0]->text;
$typed_spelling = "\tscpp::string_t local_x = scpp::string_t(\"test\");\n\tlocal_x = scpp::string_t(\"next\");\n";
if (!str_contains($typed_output, $typed_spelling)) {
	throw new \LogicException('Explicit string local did not retain typed declaration and ordinary reassignment lowering');
}
Compiler_Lifecycle::reset_preparation();
if (($declaration->preparation() !== null) || ($assignment->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Explicit local cleanup changed syntax or retained prepared facts');
}

$typed_local_rejections = [
	'$x string = 1;' => 'value boundary requires matching types or an integer conversion',
];
foreach ($typed_local_rejections as $source => $diagnostic)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Explicit local mismatch missed its semantic diagnostic');
	}
}

// Unsupported double-quoted forms fail during semantic preparation with their agreed diagnostic.
$string_rejections = [
	'$a = "hello $name";' => 'string interpolation is not supported',
	'$a = "${name}";' => 'string interpolation is not supported',
	'$a = "\u{41}";' => 'Unicode escape syntax is not supported',
];
foreach ($string_rejections as $source => $diagnostic)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		(new Compiler())->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty() || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Unsupported string form missed its semantic diagnostic');
	}
}

// Required by-value completion rejects cycles before body preparation or emission.
foreach (['struct Loop { Loop $next; }', 'struct A { B $b; } struct B { A $a; }'] as $source)
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$compiler = new Compiler();
	$before = s2s_snapshot($syntax);
	$failed = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'Cyclic by-value');
	}
	if ((!$failed) || (!Model::$cpp_files->is_empty()) || (!Model::$prepared_files->is_empty()) || (s2s_snapshot($syntax) !== $before)) {
		throw new \LogicException('Recursive layout failure changed syntax or published output');
	}
}

// Malformed numeric tokens must not split into accidentally valid expressions.
foreach (['.', '.e2', '1e', '1e+', '1e-', '1.2.3', '1e2e3', '1.0f', '1_0.5', '0x1.2', '-1.5', '+1.5'] as $spelling)
{
	Compiler_Lifecycle::reset();
	$failed = false;
	try {
		s2s_parse('$a = ' . $spelling . ';');
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	if (!$failed) {
		throw new \LogicException('Unsupported numeric syntax accepted: ' . $spelling);
	}
}

// Floating facts retain exact text and canonical identity; cleanup belongs to specialization.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 1.2345678901234567; $b = $a;');
$children = $syntax->root->body->statements;
$float_node = $children[0]->expression->value;
$float_data = object_cast($float_node, float_literal_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$float_facts = $float_data->require_float_literal_preparation();
$floating = Language_Types::floating(Model::$language_scope);
$resolved = Scope_Lookup::types(Model::$global_scope, 'float');
$floating_definition = object_cast($resolved[0], floating_type_definition::class);
if (($floating_definition->storage_bits() !== 64) || ($floating_definition->precision_bits() !== 53)
	|| ($float_facts->decimal !== '1.2345678901234567') || (!$float_facts->type->matches($floating))
	|| (!$children[1]->expression->require_preparation()->type->matches($floating))) {
	throw new \LogicException('Floating literal lost precision or canonical type identity');
}
$compiler->cpp();
Compiler_Lifecycle::reset_cpp();
if ($float_data->require_float_literal_preparation() !== $float_facts) {
	throw new \LogicException('Output reset changed floating facts');
}
Compiler_Lifecycle::reset_preparation();
if (($float_data->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Floating cleanup changed syntax or retained facts');
}

// Both quote spellings retain decoded bytes and one canonical string identity.
Compiler_Lifecycle::reset();
$string_source = <<<'PHS'
$a = 'x';
$b = 'can\'t';
$c = 'slash\\path';
$d = '\n';
$f = "line\nnext";
$g = "quote\"slash\\dollar\$";
$h = "unknown\q";
$i = "hex\x41";
$j = "octal\101";
$k = "controls\r\t\v\f\e";
$l = '';
$m = "";
PHS;
$string_source .= "\n" . '$e = \'' . string_byte_from_int(0) . '\';';
$syntax = s2s_parse($string_source);
$children = $syntax->root->body->statements;
$expected_values = ['x', "can't", 'slash' . "\\" . 'path', "\\n", "line\nnext",
	'quote"slash' . "\\" . 'dollar$', 'unknown' . "\\" . 'q', 'hexA', 'octalA',
	"controls\r\t\v\f" . string_byte_from_int(27), '', '', string_byte_from_int(0)];
$string_nodes /** vector<string_literal_node> */ = [];
foreach ($children as $index => $statement) {
	$assignment = object_cast($statement->expression, assignment_expression_node::class);
	$string_nodes[] = object_cast($assignment->value, string_literal_node::class);
}
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$string_type = Language_Types::string_type(Model::$language_scope);
$resolved = Scope_Lookup::types(Model::$global_scope, 'string');
if (Type_Preparation::canonical($string_type)->definition() !== $resolved[0]) {
	throw new \LogicException('Canonical string identity changed');
}
foreach ($string_nodes as $index => $string_node)
{
	$facts = $string_node->require_string_literal_preparation();
	if (($facts->value !== $expected_values[$index]) || (!$facts->type->matches($string_type))) {
		throw new \LogicException('String literal lost decoded bytes or canonical type');
	}
	foreach ($syntax->collection->entries as $entry) {
		if ($entry->syntax() === $string_node) {
			throw new \LogicException('String literal was collected as a name');
		}
	}
}
$compiler->cpp();
$string_output = Model::$cpp_files[0]->text;
foreach (['scpp::string_t("x")', 'scpp::string_t("can\'t")', 'scpp::string_t("slash\\\\path")',
	'scpp::string_t("\\\\n")', 'scpp::string_t("")', 'scpp::string_t(std::string("\\x00" "", 1))'] as $expected_spelling) {
	if (!str_contains($string_output, $expected_spelling)) {
		throw new \LogicException('C++ string escaping lost exact bytes: ' . $expected_spelling);
	}
}
Compiler_Lifecycle::reset_preparation();
foreach ($string_nodes as $string_node) {
	if ($string_node->preparation() !== null) {
		throw new \LogicException('String cleanup retained prepared facts');
	}
}
if (s2s_snapshot($syntax) !== $before) {
	throw new \LogicException('String preparation changed source syntax');
}

// Boolean literals keep canonical identity without entering reference/name collection.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = false; $b = $a; return $b;');
$children = $syntax->root->body->statements;
$binding_data = $children[0]->expression;
$literal_node = $binding_data->value;
$literal_data = object_cast($literal_node, boolean_literal_node::class);
$reference_data = object_cast($children[1]->expression->value, variable_reference_node::class);
$before = s2s_snapshot($syntax);
$compiler = new Compiler();
$compiler->prepare();
$boolean_type = Language_Types::boolean(Model::$language_scope);
$literal_facts = $literal_data->require_boolean_literal_preparation();
if (($literal_node->kind() !== node_kind::boolean_literal) || (Syntax_Nodes::category($literal_node) !== node_category::expression) || $literal_data->value || $literal_facts->value || (!$literal_facts->type->matches($boolean_type)) || (!$reference_data->require_preparation()->type->matches($boolean_type))) {
	throw new \LogicException('Boolean syntax, false value or inferred type changed');
}
foreach ($syntax->collection->entries as $entry) {
	if ($entry->syntax() === $literal_node) {
		throw new \LogicException('Boolean literal was collected as a name');
	}
}
$compiler->cpp();
$expected = "#include \"scpp/bool_t.hpp\"\n\nint main()\n{\n\tauto local_a = static_cast<scpp::bool_t>(false);\n\tauto local_b = local_a;\n\treturn static_cast<int>((local_b).native_value());\n\treturn 0;\n}\n";
if (Model::$cpp_files[0]->text !== $expected) {
	throw new \LogicException('Unexpected boolean C++ representation or includes');
}
Compiler_Lifecycle::reset_cpp();
if ($literal_data->require_boolean_literal_preparation() !== $literal_facts) {
	throw new \LogicException('Boolean facts lost across output reset');
}
Compiler_Lifecycle::reset_preparation();
if (($literal_data->preparation() !== null) || ($reference_data->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Boolean cleanup missed facts or changed syntax');
}

// A standalone failure must preserve syntax; partial-fact recovery remains deferred.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; $b = $missing;');
$children = $syntax->root->body->statements;
$first_data = $children[0]->expression;
$first_literal_data = object_cast($first_data->value, integer_literal_node::class);
$before = s2s_snapshot($syntax);
$failed = false;
try {
	(new File_Preparation($syntax->collection, Model::$language_scope))->prepare();
}
catch (\RuntimeException $expected) {
	$failed = true;
}
if ((!$failed) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Standalone failure left prepared facts or changed syntax');
}

// Preparation and emission are independent; an output failure retains valid facts.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; return $a;');
$compiler = new Compiler();
$failed = false;
try {
	$compiler->cpp();
}
catch (\RuntimeException $expected) {
	$failed = true;
}
if (!$failed || !Model::$prepared_files->is_empty() || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Emission implicitly prepared source');
}
$compiler->prepare();
$children = $syntax->root->body->statements;
$first_data = $children[0]->expression;
$first_literal_data = object_cast($first_data->value, integer_literal_node::class);
$binding_facts = $first_data->require_assignment_preparation();
$literal_facts = $first_literal_data->require_integer_literal_preparation();
$completion = Model::$prepared_files[0];
if (!Model::$cpp_files->is_empty()) {
	throw new \LogicException('Preparation emitted C++ output');
}
$compiler->cpp();
$output = Model::$cpp_files[0];
Compiler_Lifecycle::reset_cpp();
if (($first_data->require_assignment_preparation() !== $binding_facts) || (Model::$prepared_files[0] !== $completion) || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Output reset changed shared preparation');
}
$compiler->cpp();
$valid_integer_type = $first_literal_data->require_integer_literal_preparation()->type;
$first_literal_data->require_integer_literal_preparation()->type = new canonical_type_use(Type_Identity::maximum());
// Fault injection must invalidate the fragment whose prepared representation was altered.
Model::$cpp_output_program->fragments[$syntax->collection->root->body->work()]->change_status = change_state::changed;
$failed = false;
try {
	$compiler->cpp();
}
catch (\RuntimeException $expected) {
	$failed = true;
}
if ((!$failed) || ($first_data->preparation() !== $binding_facts) || ($first_literal_data->preparation() !== $literal_facts) || (Model::$prepared_files[0] !== $completion) || (!Model::$cpp_files->is_empty())) {
	throw new \LogicException('Emission failure damaged shared preparation or retained stale output');
}
$first_literal_data->require_integer_literal_preparation()->type = $valid_integer_type;
$compiler->cpp();
if (Model::$cpp_files[0]->text !== $output->text) {
	throw new \LogicException('Emission retry changed output');
}
$compiler->prepare();
if (($first_data->require_assignment_preparation() !== $binding_facts) || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('No-op preparation replaced facts or left stale output');
}
$compiler->cpp();
Compiler_Lifecycle::reset_preparation();
if (($first_data->preparation() !== null) || ($first_literal_data->preparation() !== null) || !Model::$prepared_files->is_empty() || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Preparation reset retained facts or dependent output');
}

// The cleanup traversal reaches nested expression specializations through syntax-only parents.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('function nested(): int { return 7; }');
$children = $syntax->root->declarations;
$body = object_cast($children[0], function_node::class)->body;
$statements = $body->statements;
$nested_literal_data = object_cast(object_cast($statements[0], return_node::class)->expression, integer_literal_node::class);
$before = s2s_snapshot($syntax);
$facts = new prepared_integer_literal();
$facts->type = Language_Types::integer(Model::$language_scope);
$facts->decimal = '7';
$nested_literal_data->set_preparation($facts);
Compiler_Lifecycle::reset_preparation();
if (($nested_literal_data->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Nested node cleanup changed syntax or missed attached facts');
}

// Reset must clear the old graph before dropping it, including externally retained nodes.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; return $a;');
(new Compiler())->prepare();
(new Compiler())->cpp();
$children = $syntax->root->body->statements;
$first_data = $children[0]->expression;
$first_literal_data = object_cast($first_data->value, integer_literal_node::class);
Compiler_Lifecycle::reset_syntax();
if (($first_data->preparation() !== null) || ($first_literal_data->preparation() !== null)) {
	throw new \LogicException('Syntax reset dropped roots before cleaning their nodes');
}

// Ordinary parent traversal permits source shadowing; reserved-name enforcement is deferred.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('struct int { int $field; }');
$root = $syntax->root->file_scope();
$local = new scope();
$local->set_parent($root);
$found = Scope_Lookup::types($local, 'int');
if (count($found) !== 1 || $found[0]->origin() !== type_definition_origin::source || $found[0] !== $root->types_named('int')[0]) {
	throw new \LogicException('Source type did not shadow parent or publication copied its identity');
}
$entry = Model::$type_catalog->source_declarations()->declaration($found[0]->definition_id());
$entry->change_status = change_state::deleted;
(new Preparation_Worker(Model::$language_scope))->remove_deleted_sources(Model::collected_files());
$found = Scope_Lookup::types($local, 'int');
$language_integer = Type_Preparation::canonical(Language_Types::integer(Model::$language_scope))->definition();
if (count($found) !== 1 || $found[0] !== $language_integer) {
	throw new \LogicException('Deleted source type blocked parent lookup');
}
$replacement = Model::$type_catalog->define_source_structure('int', $entry)->nominal_definition();
$entry->changes = 0;
$local->register_type($replacement);
if (Scope_Lookup::types($local, 'int')[0] !== $replacement) {
	throw new \LogicException('Nearest scope lost precedence');
}
file_put_contents($directory . '/programs.json', json_encode(['valid' => $cases, 'rejected' => $rejections], JSON_PRETTY_PRINT));
file_put_contents($directory . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo "S2S: canonical types, first assignment, reuse, node cleanup, syntax purity, scope lookup and bounded output passed\n";
