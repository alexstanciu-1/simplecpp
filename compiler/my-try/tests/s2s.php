<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/s2s_proof.php';

/** Standalone source setup also verifies private roots publish through global scope. */
function s2s_parse(string $text): parsed_file
{
	$input = new file();
	$input->path = 's2s.phs';
	$input->content = $text;
	$syntax = (new Parser((new Tokenizer($input))->tokenize()))->parse();
	Source_Publication::publish_parsed($syntax);
	return $syntax;
}

S2S_Proof::run();
$directory = $argv[1];
$cases = [
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
$executions = [];
foreach ($cases as $name => [$source, $exit])
{
	Compiler_Lifecycle::reset();
	$syntax = s2s_parse($source);
	$before = serialize($syntax);
	$compiler = new Compiler();
	$compiler->prepare();
	$compiler->cpp();
	Preparation_Cleanup::tree($syntax->root);
	if (serialize($syntax) !== $before) {
		throw new \LogicException('Preparation/emission changed source syntax or its scopes');
	}
	$path = $directory . '/' . $name . '.cpp';
	file_put_contents($path, Model::$cpp_files[0]->text);
	$executions[] = ['path' => $path, 'exit_code' => $exit];
	if (($name === 'bool_true') || ($name === 'bool_false')) {
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_0), scpp::bool_t>);\n";
		$probe_path = $directory . '/' . $name . '_type.cpp';
		$probe_text = str_replace("\treturn static_cast<int>", $probe . "\treturn static_cast<int>", Model::$cpp_files[0]->text);
		file_put_contents($probe_path, $probe_text);
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}

	// Independent native probes observe the value and type of large emitted literals.
	if (($name === 'wide') || ($name === 'maximum'))
	{
		$magnitude = $name === 'wide' ? '4294967296' : '9223372036854775807';
		$probe = "\tstatic_assert(std::is_same_v<decltype(local_0), scpp::int_t<>>);\n";
		$probe .= "\tif (local_0.native_value() != " . $magnitude . "LL) { return 91; }\n";
		$probe_path = $directory . '/' . $name . '_value.cpp';
		$probe_text = str_replace("\treturn static_cast<int>", $probe . "\treturn static_cast<int>", Model::$cpp_files[0]->text);
		file_put_contents($probe_path, $probe_text);
		$executions[] = ['path' => $probe_path, 'exit_code' => $exit];
	}
}
foreach (['$a int = true;', '$a bool = 1;', '$a = true; $a = 1;', '$a = 1; $a = false;', '$a = 9223372036854775808;', '$a = 010;', '$a = $a;', '$a = unknown();', '$a int;', 'function f(): int { return 1; }'] as $source)
{
	Compiler_Lifecycle::reset();
	s2s_parse($source);
	$failed = false;
	try {
		(new Compiler())->prepare();
		(new Compiler())->cpp();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty()) {
		throw new \LogicException('Unsupported generation published output');
	}
}

// Boolean literals keep canonical identity without entering reference/name collection.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = false; $b = $a; return $b;');
$children = Syntax_Nodes::block_data($syntax->root)->children;
$binding_data = Syntax_Nodes::binding_data($children[0]);
$literal_node = $binding_data->value;
$literal_data = Syntax_Nodes::boolean_data($literal_node);
$reference_data = Syntax_Nodes::reference_data(Syntax_Nodes::binding_data($children[1])->value);
$before = serialize($syntax);
$compiler = new Compiler();
$compiler->prepare();
$boolean_type = Language_Types::boolean(Model::$language_scope);
$literal_facts = $literal_data->require_preparation();
if (($literal_node->kind() !== node_kind::boolean_literal) || (Syntax_Nodes::category($literal_node) !== node_category::expression) || $literal_data->value || $literal_facts->value || ($literal_facts->type !== $boolean_type) || ($reference_data->require_preparation()->type !== $boolean_type)) {
	throw new \LogicException('Boolean syntax, false value or inferred type changed');
}
foreach ($syntax->collection->entries as $entry) {
	if ($entry->node === $literal_node) {
		throw new \LogicException('Boolean literal was collected as a name');
	}
}
$compiler->cpp();
$expected = "#include \"scpp/bool_t.hpp\"\n\nint main()\n{\n\tauto local_0 = static_cast<scpp::bool_t>(false);\n\tauto local_4 = local_0;\n\treturn static_cast<int>((local_4).native_value());\n\treturn 0;\n}\n";
if (Model::$cpp_files[0]->text !== $expected) {
	throw new \LogicException('Unexpected boolean C++ representation or includes');
}
Compiler_Lifecycle::reset_cpp();
if ($literal_data->require_preparation() !== $literal_facts) {
	throw new \LogicException('Boolean facts lost across output reset');
}
Compiler_Lifecycle::reset_preparation();
if (($literal_data->preparation() !== null) || ($reference_data->preparation() !== null) || (serialize($syntax) !== $before)) {
	throw new \LogicException('Boolean cleanup missed facts or changed syntax');
}

// A standalone preparation failure must clear an earlier successful statement too.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; $b = $missing;');
$children = Syntax_Nodes::block_data($syntax->root)->children;
$first_data = Syntax_Nodes::binding_data($children[0]);
$first_literal_data = Syntax_Nodes::integer_data($first_data->value);
$before = serialize($syntax);
$failed = false;
try {
	(new File_Preparation($syntax->collection, Model::$language_scope))->prepare();
}
catch (\RuntimeException $expected) {
	$failed = true;
}
if ((!$failed) || ($first_data->preparation() !== null) || ($first_literal_data->preparation() !== null) || (serialize($syntax) !== $before)) {
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
$children = Syntax_Nodes::block_data($syntax->root)->children;
$first_data = Syntax_Nodes::binding_data($children[0]);
$first_literal_data = Syntax_Nodes::integer_data($first_data->value);
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
if (($first_data->require_preparation() === $binding_facts) || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Repreparation reused facts or left stale output');
}
$compiler->cpp();
Compiler_Lifecycle::reset_preparation();
if (($first_data->preparation() !== null) || ($first_literal_data->preparation() !== null) || !Model::$prepared_files->is_empty() || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Preparation reset retained facts or dependent output');
}

// The cleanup traversal reaches nested expression specializations through syntax-only parents.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('function nested(): int { return 7; }');
$children = Syntax_Nodes::block_data($syntax->root)->children;
$body = Syntax_Nodes::function_data($children[0])->body;
$statements = Syntax_Nodes::block_data($body)->children;
$nested_literal_data = Syntax_Nodes::integer_data(Syntax_Nodes::return_data($statements[0])->expression);
$before = serialize($syntax);
$facts = new prepared_integer_literal();
$facts->type = Language_Types::integer(Model::$language_scope);
$facts->decimal = '7';
$nested_literal_data->set_preparation($facts);
Compiler_Lifecycle::reset_preparation();
if (($nested_literal_data->preparation() !== null) || (serialize($syntax) !== $before)) {
	throw new \LogicException('Nested node cleanup changed syntax or missed attached facts');
}

// Reset must clear the old graph before dropping it, including externally retained nodes.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; return $a;');
(new Compiler())->prepare();
(new Compiler())->cpp();
$children = Syntax_Nodes::block_data($syntax->root)->children;
$first_data = Syntax_Nodes::binding_data($children[0]);
$first_literal_data = Syntax_Nodes::integer_data($first_data->value);
Compiler_Lifecycle::reset_syntax();
if (($first_data->preparation() !== null) || ($first_literal_data->preparation() !== null)) {
	throw new \LogicException('Syntax reset dropped roots before cleaning their nodes');
}

// Ordinary parent traversal permits source shadowing; reserved-name enforcement is deferred.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('struct int { int $field; }');
$root = Syntax_Nodes::block_data($syntax->root)->lexical_scope();
$local = new scope();
$local->set_parent($root);
$found = Scope_Lookup::types($local, 'int');
if (count($found) !== 1 || $found[0]->origin !== type_origin::source || $found[0] !== $root->types_named('int')[0]) {
	throw new \LogicException('Source type did not shadow parent or publication copied its identity');
}
$entry = $found[0]->declaration;
$entry->changes = SYNC_DELETED;
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
file_put_contents($directory . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo "S2S: canonical types, first assignment, reuse, node cleanup, syntax purity, scope lookup and bounded output passed\n";
