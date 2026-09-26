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
	$compiler->cpp();
	Preparation_Cleanup::tree($syntax->root);
	if (serialize($syntax) !== $before) {
		throw new \LogicException('Preparation/emission changed source syntax or its scopes');
	}
	$path = $directory . '/' . $name . '.cpp';
	file_put_contents($path, Model::$cpp_files[0]->text);
	$executions[] = ['path' => $path, 'exit_code' => $exit];
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
foreach (['$a = 9223372036854775808;', '$a = 010;', '$a = $a;', '$a = unknown();', '$a int;', 'function f(): int { return 1; }'] as $source)
{
	Compiler_Lifecycle::reset();
	s2s_parse($source);
	$failed = false;
	try {
		(new Compiler())->cpp();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty()) {
		throw new \LogicException('Unsupported generation published output');
	}
}

// A standalone preparation failure must clear an earlier successful statement too.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; $b = $missing;');
$children = Syntax_Nodes::block_data($syntax->root)->children;
$first_data = Syntax_Nodes::binding_data($children[0]);
$first_literal_data = Syntax_Nodes::expression_data($first_data->value);
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

// Emission failure after successful preparation must also release attached facts.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; return $a;');
$children = Syntax_Nodes::block_data($syntax->root)->children;
$first_data = Syntax_Nodes::binding_data($children[0]);
$first_literal_data = Syntax_Nodes::expression_data($first_data->value);
Language_Types::integer(Model::$language_scope)->value_bits = 32;
$failed = false;
try {
	(new Compiler())->cpp();
}
catch (\RuntimeException $expected) {
	$failed = true;
}
if ((!$failed) || ($first_data->preparation() !== null) || ($first_literal_data->preparation() !== null) || (!Model::$prepared_files->is_empty()) || (!Model::$cpp_files->is_empty())) {
	throw new \LogicException('Emission failure left prepared facts or output');
}

// The cleanup traversal reaches nested expression specializations through syntax-only parents.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('function nested(): int { return 7; }');
$children = Syntax_Nodes::block_data($syntax->root)->children;
$body = Syntax_Nodes::function_data($children[0])->body;
$statements = Syntax_Nodes::block_data($body)->children;
$nested_literal_data = Syntax_Nodes::expression_data(Syntax_Nodes::return_data($statements[0])->expression);
$before = serialize($syntax);
$facts = new prepared_expression();
$facts->type = Language_Types::integer(Model::$language_scope);
$facts->literal = '7';
$nested_literal_data->set_preparation($facts);
Compiler_Lifecycle::reset_cpp();
if (($nested_literal_data->preparation() !== null) || (serialize($syntax) !== $before)) {
	throw new \LogicException('Nested node cleanup changed syntax or missed attached facts');
}

// Reset must clear the old graph before dropping it, including externally retained nodes.
Compiler_Lifecycle::reset();
$syntax = s2s_parse('$a = 10; return $a;');
(new Compiler())->cpp();
$children = Syntax_Nodes::block_data($syntax->root)->children;
$first_data = Syntax_Nodes::binding_data($children[0]);
$first_literal_data = Syntax_Nodes::expression_data($first_data->value);
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
