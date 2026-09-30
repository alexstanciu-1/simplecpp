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
	'literal' => ['$a = 10;', 0],
	'value' => ['$a = 10; return $a;', 10],
	'explicit' => ['$a int = 10; return $a;', 10],
	'copy' => ['$a = 10; $b = $a; $a = 12; return $b;', 10],
	'wide' => ['$a = 4294967296; return 7;', 7],
	'maximum' => ['$a = 9223372036854775807; return 9;', 9],
	'keyword' => ['$int = 10; return $int;', 10],
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
$executions = [];
foreach ($cases as $name => [$source, $exit])
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = s2s_snapshot($syntax);
	$compiler = new Compiler();
	$compiler->prepare();
	$compiler->cpp();
	Preparation_Cleanup::tree($syntax->root);
	if (s2s_snapshot($syntax) !== $before) {
		throw new \LogicException('Preparation/emission changed source syntax or its scopes');
	}
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
}
$rejections = ['function f(int &$x): void {} f(1);',
	'function f(int $x): int { return $x; } f();',
	'function f(): int { return; }',
	'function f(): void { return 1; }',
	'function f(): void {} $x = f();',
	'$x = 1; function f(): int { return $x; }',
	'struct S { int $x; }', 'struct S { float $x; }',
	'struct S { uint8 $x; } $s S; $s->missing = 1;',
	'struct A {} struct B {} $a A; $b B = $a;',
	'struct S { uint8 $x; } $s S = [1];',
	'function f(int &$x): void {} $x uint8 = 1; f($x);',
	'$a float = 1;', '$a int = 1.5;', '$a = 1.5; $a = false;', '$a int = true;', '$a bool = 1;', '$a = true; $a = 1;', '$a = 1; $a = false;', '$a = 9223372036854775808;', '$a = 010;', '$a = $a;', '$a = unknown();', '$a void;', 'template<T> function f(): int { return 1; }'];
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
$float_facts = $float_data->require_preparation();
$floating = Language_Types::floating(Model::$language_scope);
$resolved = Scope_Lookup::types(Model::$global_scope, 'float');
if (($resolved[0] !== $floating) || ($floating->value_bits !== 64) || !$floating->signed || ($float_facts->decimal !== '1.2345678901234567') || ($float_facts->type !== $floating) || ($children[1]->expression->require_preparation()->type !== $floating)) {
	throw new \LogicException('Floating literal lost precision or canonical type identity');
}
$compiler->cpp();
Compiler_Lifecycle::reset_cpp();
if ($float_data->require_preparation() !== $float_facts) {
	throw new \LogicException('Output reset changed floating facts');
}
Compiler_Lifecycle::reset_preparation();
if (($float_data->preparation() !== null) || (s2s_snapshot($syntax) !== $before)) {
	throw new \LogicException('Floating cleanup changed syntax or retained facts');
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
$literal_facts = $literal_data->require_preparation();
if (($literal_node->kind() !== node_kind::boolean_literal) || (Syntax_Nodes::category($literal_node) !== node_category::expression) || $literal_data->value || $literal_facts->value || ($literal_facts->type !== $boolean_type) || ($reference_data->require_preparation()->type !== $boolean_type)) {
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
if ($literal_data->require_preparation() !== $literal_facts) {
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
$binding_facts = $first_data->require_preparation();
$literal_facts = $first_literal_data->require_preparation();
$completion = Model::$prepared_files[0];
if (!Model::$cpp_files->is_empty()) {
	throw new \LogicException('Preparation emitted C++ output');
}
$compiler->cpp();
$output = Model::$cpp_files[0];
Compiler_Lifecycle::reset_cpp();
if (($first_data->require_preparation() !== $binding_facts) || (Model::$prepared_files[0] !== $completion) || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Output reset changed shared preparation');
}
$compiler->cpp();
Language_Types::integer(Model::$language_scope)->value_bits = 32;
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
Language_Types::integer(Model::$language_scope)->value_bits = 64;
$compiler->cpp();
if (Model::$cpp_files[0]->text !== $output->text) {
	throw new \LogicException('Emission retry changed output');
}
$compiler->prepare();
if (($first_data->require_preparation() !== $binding_facts) || !Model::$cpp_files->is_empty()) {
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
if (count($found) !== 1 || $found[0]->origin !== type_origin::source || $found[0] !== $root->types_named('int')[0]) {
	throw new \LogicException('Source type did not shadow parent or publication copied its identity');
}
$entry = $found[0]->declaration;
$entry->change_status = change_state::deleted;
(new Preparation_Worker(Model::$language_scope))->remove_deleted_sources(Model::collected_files());
$found = Scope_Lookup::types($local, 'int');
if (count($found) !== 1 || $found[0] !== Language_Types::integer(Model::$language_scope)) {
	throw new \LogicException('Deleted source type blocked parent lookup');
}
$replacement = new type_definition();
$replacement->name = 'int';
$replacement->kind = type_kind::record;
$replacement->origin = type_origin::source;
$replacement->declaration = $entry;
$entry->changes = 0;
$local->register_type($replacement);
if (Scope_Lookup::types($local, 'int')[0] !== $replacement) {
	throw new \LogicException('Nearest scope lost precedence');
}
file_put_contents($directory . '/programs.json', json_encode(['valid' => $cases, 'rejected' => $rejections], JSON_PRETTY_PRINT));
file_put_contents($directory . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo "S2S: canonical types, first assignment, reuse, node cleanup, syntax purity, scope lookup and bounded output passed\n";
