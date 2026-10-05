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
$unsupported = new data_construction_node();
$literal = new integer_literal_node();
$entry = new data_entry_node();
$entry->value = $literal;
$unsupported->entries->append($entry);
$context = new preparation_context();
$failures = 0;
try {
	Expression_Preparation::prepare($unsupported, $context);
}
catch (\RuntimeException $error) {
	$failures++;
}
try {
	(new CPP_Syntax(new cpp_generation_context()))->expression($unsupported);
}
catch (\RuntimeException $error) {
	$failures++;
}
if ($failures !== 2 || $literal->preparation() !== null || !($unsupported instanceof ast_node_i)) {
	throw new \LogicException('Unsupported dispatch visited children or lost its interface');
}

// Shared syntax exposes data and maintenance, never a backend processing protocol.
if (interface_exists(__NAMESPACE__ . '\\cpp_generation_worker_i')) {
	throw new \LogicException('C++ worker protocol leaked into the shared model');
}
foreach (get_declared_classes() as $class_name) {
	if ($class_name === ast_node::class || is_subclass_of($class_name, ast_node::class)) {
		foreach (['generate_cpp', 'accept', 'prepare', 'prepare_completion', 'prepare_header',
			'prepare_continuation', 'select_preparation', 'integer_constant'] as $processing_hook) {
			if (method_exists($class_name, $processing_hook)) {
				throw new \LogicException('Syntax still owns stage processing: ' . $class_name . '::' . $processing_hook);
			}
		}
	}
}

// Unsupported emission does not prepare children, attach facts or alter output context.
$emission_context = new cpp_generation_context();
$emitter = new CPP_Syntax($emission_context);
$headers_before = $emission_context->headers;
$temporary_before = $emission_context->next_temporary;
try {
	$emitter->expression($unsupported);
	throw new \LogicException('Unsupported expression silently emitted');
}
catch (\RuntimeException $expected) {
	if ($expected->getMessage() !== 'C++ generation is not supported for this node') {
		throw $expected;
	}
}
if ($literal->preparation() !== null || $emission_context->headers !== $headers_before
	|| $emission_context->next_temporary !== $temporary_before) {
	throw new \LogicException('Unsupported backend dispatch performed work');
}

// Shared accessors retain identity, typed assignment and per-instance cleanup for every fact slot.
$fact_pairs = [
	[new integer_literal_node(), new prepared_integer_literal()],
	[new float_literal_node(), new prepared_float_literal()],
	[new boolean_literal_node(), new prepared_boolean_literal()],
	[new string_literal_node(), new prepared_string_literal()],
	[new variable_reference_node(), new prepared_variable_reference()],
	[new constant_reference_node(), new prepared_constant_reference()],
	[new binary_expression_node(), new prepared_binary_expression()],
	[new variable_declaration_node(), new prepared_binding()],
	[new call_node(), new prepared_call()],
	[new assignment_expression_node(), new prepared_assignment()],
	[new field_access_node(), new prepared_field_access()],
];
foreach ($fact_pairs as $pair)
{
	$owner = $pair[0];
	$facts = $pair[1];
	if ($owner->preparation() !== null) {
		throw new \LogicException('New specialization has prepared facts');
	}
	if ($owner instanceof expression_node) {
		$return_type = (new \ReflectionMethod($owner, 'require_preparation'))->getReturnType();
		if ((string)$return_type !== prepared_expression::class) {
			throw new \LogicException('Expression accessor changed the shared return contract');
		}
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
$integer_binding = object_cast($integer_source->root->body->statements[0], variable_declaration_node::class);
$integer_literal = object_cast($integer_binding->initializer, integer_literal_node::class);
$literal_facts = $integer_literal->require_integer_literal_preparation();
$body_work = $integer_source->root->body->work();
$body_state = $body_work->state;
$literal_text = (new CPP_Syntax(new cpp_generation_context()))->expression($integer_literal);
if ($literal_text === '' || $integer_literal->require_integer_literal_preparation() !== $literal_facts
	|| $integer_source->root->body->work() !== $body_work || $body_work->state !== $body_state) {
	throw new \LogicException('Backend dispatch changed semantic facts or work identity/state');
}
$boolean_source = dispatch_parse('$a bool = false; return $a;');
$boolean_prepared = (new File_Preparation($boolean_source->collection, Model::$language_scope))->prepare();
$boolean_output = $generator->generate($boolean_prepared);
if (str_contains($boolean_output->text, 'scpp/int_t.hpp') || !str_contains($boolean_output->text, 'scpp/bool_t.hpp') || (substr_count($integer_output->text, 'scpp::int_t<> local_a') !== 1)) {
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
echo "Specialization dispatch: backend-owned emission, unsupported-parent isolation, fact identity and invocation state passed\n";
