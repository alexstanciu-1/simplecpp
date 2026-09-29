<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

/** Produce a real source tree while keeping these dispatch checks independent of publication. */
function dispatch_parse(string $text): parsed_file
{
	$source = new file();
	$source->path = 'dispatch.phs';
	$source->content = $text;
	$parsed = (new Parser((new Tokenizer($source))->tokenize()))->parse();
	$parsed->root_scope()->set_parent(Model::$global_scope);
	return $parsed;
}

Compiler_Lifecycle::reset();
$parsed = dispatch_parse('function unsupported(): int { return 17; }');
$unsupported = new binary_expression_node();
$literal = new integer_literal_node();
$unsupported->left = $literal;
$unsupported->right = $literal;
$context = new preparation_context();
$failures = 0;
try {
	$unsupported->prepare($context);
}
catch (\RuntimeException $error) {
	$failures++;
}
try {
	$unsupported->generate_cpp(new CPP_Syntax(new cpp_generation_context()));
}
catch (\RuntimeException $error) {
	$failures++;
}
if ($failures !== 2 || $literal->preparation() !== null || !($unsupported instanceof ast_node_i)) {
	throw new \LogicException('Unsupported dispatch visited children or lost its interface');
}

// Shared accessors retain identity, typed assignment and per-instance cleanup for every fact slot.
$fact_pairs = [
	[new integer_literal_node(), new prepared_integer_literal()],
	[new float_literal_node(), new prepared_float_literal()],
	[new boolean_literal_node(), new prepared_boolean_literal()],
	[new variable_reference_node(), new prepared_variable_reference()],
	[new variable_declaration_node(), new prepared_binding()],
];
foreach ($fact_pairs as $pair)
{
	$owner = $pair[0];
	$facts = $pair[1];
	if ($owner->preparation() !== null) {
		throw new \LogicException('New specialization has prepared facts');
	}
	$owner->set_preparation($facts);
	if (($owner->preparation() !== $facts) || ($owner->require_preparation() !== $facts)) {
		throw new \LogicException('Shared preparation access copied or changed facts');
	}
	$rejected = false;
	try {
		$owner->set_preparation(new \stdClass());
	}
	catch (\TypeError $expected) {
		$rejected = true;
	}
	if (!$rejected || ($owner->preparation() !== $facts)) {
		throw new \LogicException('Shared setter accepted incompatible facts or lost prior state');
	}
	$owner->clear_preparation();
	$rejected = false;
	try {
		$owner->require_preparation();
	}
	catch (\TypeError $expected) {
		$rejected = true;
	}
	if (!$rejected || ($owner->preparation() !== null)) {
		throw new \LogicException('Concrete required accessor accepted cleared facts');
	}
}

// A reused generator must own fresh output state rather than retain a prior invocation's headers.
$generator = new CPP_Generator();
$integer_source = dispatch_parse('$a int = 10; return $a;');
$integer_prepared = (new File_Preparation($integer_source->collection, Model::$language_scope))->prepare();
$integer_output = $generator->generate($integer_prepared);
$boolean_source = dispatch_parse('$a bool = false; return $a;');
$boolean_prepared = (new File_Preparation($boolean_source->collection, Model::$language_scope))->prepare();
$boolean_output = $generator->generate($boolean_prepared);
if (str_contains($boolean_output->text, 'scpp/int_t.hpp') || !str_contains($boolean_output->text, 'scpp/bool_t.hpp') || (substr_count($integer_output->text, 'auto local_a') !== 1)) {
	throw new \LogicException('Generation leaked context or visited a binding twice');
}

// Independent successful work survives a failing body; the phase still withholds completion.
$mixed = dispatch_parse('$a = 10; function unsupported(): int { return missing(); }');
$first_data = $mixed->root->body->statements[0]->expression;
$first_literal = object_cast($first_data->value, integer_literal_node::class);
$failed = false;
try {
	(new File_Preparation($mixed->collection, Model::$language_scope))->prepare();
}
catch (\RuntimeException $error) {
	$failed = true;
}
if (!$failed || ($first_data->preparation() === null) || ($first_literal->preparation() === null) || $mixed->collection->root->body->work()->failed) {
	throw new \LogicException('Preparation did not preserve independent successful body work');
}
echo "Specialization dispatch: inherited contract, unsupported-parent isolation, phase cleanup and invocation state passed\n";
