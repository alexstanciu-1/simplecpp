<?php

namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Parse fresh or retained syntax without enabling data factory preparation. */
function data_parse(string $source, ?parsed_file $previous = null): parsed_file
{
	$input = new file();
	$input->path = 'data_construction.phs';
	$input->content = $source;
	return (new Parser((new Tokenizer($input))->tokenize(), null, $previous))->parse();
}

function data_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Verify the inspection tree has initialized fields and no duplicated ownership. */
function data_tree(ast_node $node, \SplObjectStorage $seen): void
{
	data_check(!$seen->contains($node), 'Duplicate data syntax ownership');
	$seen->attach($node);
	foreach ((new \ReflectionClass($node))->getProperties() as $property) {
		data_check($property->isInitialized($node), 'Uninitialized syntax property: ' . $property->getName());
	}
	foreach ($node->children() as $child) {
		data_tree($child, $seen);
	}
}

Compiler_Lifecycle::reset();
$cases = [
	['[]', false, 0], ['{}', true, 0],
	['[1, 2, 3,]', false, 3],
	['["key" => 10, null => 20,]', false, 2],
	['{"quoted": 10, bare: 20,}', true, 2],
	['{null: 1, true: 2, function: 3}', true, 3],
	['[($flag ? "a" : "b") => f(),]', false, 1],
	['{value: $flag ? 1 : 2,}', true, 1],
	['{settings: {enabled: true}, items: [1, 2,],}', true, 2],
	['vector<int>::[1, 2,]', false, 2],
	['hash<vector<int>, string>::{"a": vector<int>::[1]}', true, 1],
	['Unknown::{child: Other::{value: 1}}', true, 1],
];
foreach ($cases as [$source, $braces, $count])
{
	$parsed = data_parse('return ' . $source . ';');
	$node = $parsed->root->single_body()->body->statements[0]->expression;
	data_check($node instanceof data_construction_node, 'Missing common node: ' . $source);
	data_check($node->entries->count() === $count, 'Wrong entry count: ' . $source);
	$expected = $braces ? data_source_representation::js_like : data_source_representation::php_like;
	data_check($node->source_representation($parsed->tokens) === $expected, 'Wrong token-derived representation');
	data_check(!property_exists($node, 'representation'), 'Representation must not be duplicated');
	data_tree($parsed->root, new \SplObjectStorage());
	Preparation_Cleanup::tree($parsed->root);
}

$parsed = data_parse('return {bare: $value, "quoted": 2, bare: 3};');
$node = $parsed->root->single_body()->body->statements[0]->expression;
$key = $node->entries[0]->key;
data_check($key instanceof string_literal_node, 'Bare property key did not normalize to a string');
data_check($key->optional_occurrence() === null, 'Bare property key was collected as a name');
data_check($key->literal_spelling($parsed->tokens) === "'bare'", 'Bare property spelling lost');
data_check($parsed->tokens->text_at($key->start_token()) === 'bare', 'Source token was rewritten');
data_check($node->entries->count() === 3, 'Duplicate entries were discarded');
foreach ($parsed->collection->entries as $occurrence) {
	data_check(!($occurrence->syntax() instanceof constant_reference_node), 'Property names became constant lookups');
}
$context = new preparation_context();
$context->collection = $parsed->collection;
$context->string_type = Language_Types::string_type(Model::$language_scope);
Expression_Preparation::prepare($key, $context);
data_check($key->require_string_literal_preparation()->value === 'bare', 'Generic string preparation lost bare key spelling');
Preparation_Cleanup::tree($node);
data_check($key->preparation() === null, 'Maintenance missed a keyed child');
data_check(String_Literals::decode($key->literal_spelling($parsed->tokens)) === 'bare', 'Normalized bare key did not decode');

$parsed = data_parse('return [null => 1, 2];');
$node = $parsed->root->single_body()->body->statements[0]->expression;
data_check($node->entries[0]->key instanceof null_literal_node, 'Explicit null key became absent');
data_check($node->entries[1]->key === null, 'Positional entry acquired a synthetic key');
// Mixed shapes stay faithfully parsed; admitting them is not this syntax slice.
$entry_children = iterator_to_array($node->entries[0]->children());
data_check($entry_children === [$node->entries[0]->key, $node->entries[0]->value], 'Entry child order changed');

foreach (['["a": 1]', '{"a" => 1}', '{a => 1}', '{1}', '{$key: 1}', '{1: 2}',
	'{"$key": 1}', '{a}', '[,]', '[1,,]', '{a: 1,,}', '[1,}', '{a: 1,]',
	'["a" =>]', '{a:}', '[1 2]', '{a: 1 b: 2}', '[...$items]', '{...$items}',
	'[]::{}', 'vector<>::[]'] as $source)
{
	$failed = false;
	try {
		data_parse('return ' . $source . ';');
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'data_construction.phs: byte ');
	}
	data_check($failed, 'Malformed notation accepted or lost location: ' . $source);
}

// Normal blocks, indexing, calls, comparison and static constants retain their paths.
$parsed = data_parse('{ $x = 1; } return ({name: 1})["name"];');
data_check($parsed->root->single_body()->body->statements[0] instanceof block_node, 'Statement braces stopped being blocks');
data_check($parsed->root->single_body()->body->statements[1]->expression instanceof index_node, 'Shared postfix path lost');
foreach (['error::$no_error', 'f<int>(1)', '$a < $b', '($a)', 'f({name: 1})'] as $source) {
	data_parse('return ' . $source . ';');
}
$parsed = data_parse('return (mixed){key: 1};');
data_check($parsed->root->single_body()->body->statements[0]->expression instanceof cast_expression_node, 'Brace expression lost ordinary cast parsing');

// Retained nested trees continue to query their spelling after span moves/compaction.
$source = 'function f(): mixed { return dynamic::{settings: {enabled: true}, values: [1, 2,]}; }';
$parsed = data_parse($source);
$function = $parsed->root_scope()->functions_named('f')[0]->syntax();
$body = $function->body;
$node = $body->statements[0]->expression;
$nested = $node->entries[0]->value;
$parsed = data_parse('function before(): int { return 0; } ' . $source, $parsed);
Token_Cleanup::file($parsed);
data_check($function->body === $body, 'Unchanged construction body was rebuilt');
data_check($node->source_representation($parsed->tokens) === data_source_representation::js_like, 'Typed opening lost on compaction');
data_check($nested->source_representation($parsed->tokens) === data_source_representation::js_like, 'Nested opening lost on compaction');
data_check($nested->entries[0]->key->literal_spelling($parsed->tokens) === "'enabled'", 'Nested key span lost');
$children = iterator_to_array($node->children());
data_check($children === [$node->target_type, $node->entries[0], $node->entries[1]], 'Construction traversal lost type or entries');
data_tree($parsed->root, new \SplObjectStorage());
$parsed = data_parse('function f(): mixed { return ["settings" => {enabled: false}]; }', $parsed);
Token_Cleanup::file($parsed);
$node = $function->body->statements[0]->expression;
data_check($node->target_type === null, 'Target type survived a syntax replacement');
data_check($node->source_representation($parsed->tokens) === data_source_representation::php_like, 'Representation survived a syntax replacement');

$failed = false;
try {
	data_parse('function f(): mixed { return {broken => 1}; }', $parsed);
}
catch (\RuntimeException $error) {
	$failed = true;
}
data_check($failed, 'Invalid edited separator was accepted');
$parsed = data_parse('function f(): mixed { return {repaired: 1}; }', $parsed);
Token_Cleanup::file($parsed);
$repaired = $parsed->root_scope()->functions_named('f')[0]->syntax()->body->statements[0]->expression;
data_check($parsed->complete, 'Parsing did not recover after an invalid edit');
data_check($repaired->entries[0]->key->literal_spelling($parsed->tokens) === "'repaired'", 'Recovery retained stale entry syntax');
data_tree($parsed->root, new \SplObjectStorage());

// Unknown targets and non-scalar program-entry returns still reject during preparation.
foreach (['[]', '{key: 1}', 'dynamic::{key: 1}'] as $source)
{
	Compiler_Lifecycle::reset();
	$syntax = data_parse('return ' . $source . ';');
	$module = new module('.', Source_Registry::normalize('.'), '.');
	Model::$modules->add($module->name, $module);
	$record = Source_Registry::add($module, $syntax->source_file());
	Source_Publication::publish_parsed($record, $syntax);
	$compiler = new Compiler();
	$failed = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'requires a known concrete target')
			|| str_contains($error->getMessage(), 'entry return requires');
	}
	data_check($failed && Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(), 'Invalid data return escaped preparation');
}

echo "data construction syntax OK\n";
