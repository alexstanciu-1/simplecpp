<?php

/* Model proof: runtime contracts need neither source function syntax nor source parameter storage. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function callable_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function callable_rejects(callable $action, string $message): void
{
	try { $action(); }
	catch (\Exception $error) {
		callable_check(str_contains($error->getMessage(), $message), 'Wrong diagnostic: ' . $error->getMessage());
		return;
	}
	throw new \LogicException('Expected rejection: ' . $message);
}

Compiler_Lifecycle::reset();
$source = new file();
$source->path = 'callables.phs';
$source->content = 'function build(int $n = 2, int ...$tail): vector<int> { return $tail; } $items = build(3, 4); return 0;';
$parsed = (new Parser((new Tokenizer($source))->tokenize()))->parse();
$parsed->root_scope()->set_parent(Model::$language_scope);
(new File_Preparation($parsed->collection, Model::$language_scope))->prepare();
$function = object_cast($parsed->root->declarations[0], function_node::class);
$source_facts = $function->require_preparation();
$assignment = object_cast($parsed->root->body->statements[0]->expression, assignment_expression_node::class);
$call = object_cast($assignment->value, call_node::class);
$call_facts = $call->require_call_preparation();
callable_check(weakref_get($call_facts->target) === $function->occurrence(), 'Source callable identity was copied');
callable_check($call_facts->signature === $source_facts->signature, 'Call lost its shared signature');
callable_check($source_facts->parameters[0]->contract === $source_facts->signature->parameters[0], 'Parameter contract was duplicated');
callable_check($source_facts->parameters[0]->storage_type() === $source_facts->signature->parameters[0]->type, 'Source storage type differs from call contract');
$file_work = $parsed->root->body->work();
callable_check($file_work->dependencies->contains($function->occurrence()->preparation), 'Source signature dependency missing');

// Build an independent installed contract, using canonical types and a source-free default.
$signature = new callable_signature();
$signature->return_type = $source_facts->signature->return_type;
$parameter = new callable_parameter();
$parameter->type = Language_Types::integer(Model::$language_scope);
$parameter->mode = passing_mode::value;
$literal = new prepared_integer_literal();
$literal->type = $parameter->type;
$literal->decimal = '2';
$default_value = new prepared_parameter_default();
$default_value->value = $literal;
$default_value->conversion = Conversion_Preparation::decide($literal->type, $parameter->type, conversion_context::argument);
$parameter->default_value = $default_value;
$signature->parameters->append($parameter);
$runtime = Model::$runtime_callables->register_static_factory('test.vector_int.from_count', $signature->return_type, $signature);
callable_check(Model::$runtime_callables->definition($runtime->key()) === $runtime, 'Registration lost identity');
callable_check($runtime->owner()->matches($signature->return_type), 'Factory owner must be exact canonical target');
callable_check(!property_exists($runtime, 'declaration') && !property_exists($parameter, 'declaration'), 'Runtime model requires fabricated source storage');
callable_rejects(fn() => Model::$runtime_callables->register_static_factory($runtime->key(), $signature->return_type, $signature), 'Duplicate runtime callable');
callable_rejects(fn() => Model::$runtime_callables->register_static_factory('bad.owner', $parameter->type, $signature), 'exact owner type');
callable_rejects(fn() => Model::$runtime_callables->register_static_factory('bad.modifier', new canonical_type_use($signature->return_type->type_id(), true), $signature), 'exact owner type');

$context = new preparation_context();
$context->worker = new Preparation_Worker(Model::$language_scope);
$context->owner = $file_work;
$context->collection = $parsed->collection;
$context->locals = new local_environment(null);
$context->integer = Language_Types::integer(Model::$language_scope);
$context->boolean = Language_Types::boolean(Model::$language_scope);
$context->floating = Language_Types::floating(Model::$language_scope);
$context->string_type = Language_Types::string_type(Model::$language_scope);
$empty /** Storage<expression_node> */ = new Storage();
$dependencies = count($file_work->dependencies);
$selected = Call_Preparation::prepare($runtime, $empty, $context);
callable_check($selected->signature === $signature && weakref_get($selected->target) === $runtime, 'Runtime call changed target/contract identity');
callable_check(count($file_work->dependencies) === $dependencies, 'Runtime contract manufactured a source dependency');
$cpp = new cpp_generation_context();
callable_rejects(fn() => $runtime->cpp_reference($cpp), 'no C++ binding');
Model::$cpp_callable_bindings->bind_static($runtime, 'from_count', 'test/factory.hpp');
$reference = $runtime->cpp_reference($cpp);
callable_check($reference === 'scpp::vector_t<scpp::int_t<>>::from_count', 'Static reference lost concrete owner spelling: ' . $reference);
callable_check(isset($cpp->headers['test/factory.hpp']) && isset($cpp->headers['scpp/vector_t.hpp']), 'Callable/type headers missing');
$text = CPP_Declarations::selected_call($selected, $empty, $cpp);
callable_check(str_contains($text, $reference . '(static_cast<scpp::int_t<>>(2LL))'), 'Runtime call relies on undeclared C++ defaults');
callable_rejects(fn() => Model::$cpp_callable_bindings->bind_static($runtime, 'again', ''), 'Duplicate storage key');
$other = new Runtime_Callables();
$foreign = $other->register_static_factory($runtime->key(), $signature->return_type, $signature);
callable_rejects(fn() => Model::$cpp_callable_bindings->definition($foreign), 'different semantic identity');
callable_rejects(fn() => Call_Preparation::prepare($runtime, $call->arguments, $context), 'argument count');
$one /** Storage<expression_node> */ = new Storage();
$one->append($call->arguments[0]);
$with_value = Call_Preparation::prepare($runtime, $one, $context);
callable_check($with_value->arguments[0]->require_conversion()->target_type->matches($parameter->type), 'Runtime value admission skipped conversion');

$pack_contract = new callable_parameter();
$pack_contract->type = $signature->return_type;
$pack_contract->element_type = $parameter->type;
$pack_contract->mode = passing_mode::value;
$pack_signature = new callable_signature();
$pack_signature->return_type = $signature->return_type;
$pack_signature->parameters->append($pack_contract);
$pack_factory = Model::$runtime_callables->register_static_factory('test.pack', $pack_signature->return_type, $pack_signature);
$packed = Call_Preparation::prepare($pack_factory, $call->arguments, $context);
callable_check($packed->fixed_count === 0 && $packed->variadic_parameter === $pack_contract && count($packed->arguments) === 2, 'Runtime variadic admission diverged');
callable_check(count(Call_Preparation::prepare($pack_factory, $empty, $context)->arguments) === 0, 'Empty runtime pack rejected');

// The same contract validator rejects malformed installed signatures before publication.
$bad = new callable_signature();
$bad->return_type = $signature->return_type;
$bad->parameters->append($parameter);
$required = new callable_parameter();
$required->type = $parameter->type;
$required->mode = passing_mode::value;
$bad->parameters->append($required);
callable_rejects(fn() => Model::$runtime_callables->register_static_factory('bad.order', $bad->return_type, $bad), 'required parameter cannot follow');
callable_rejects(fn() => Model::$runtime_callables->definition('bad.order'), 'Unknown storage key');
$reference_parameter = new callable_parameter();
$reference_parameter->type = $parameter->type;
$reference_parameter->mode = passing_mode::reference;
$reference_signature = new callable_signature();
$reference_signature->return_type = $signature->return_type;
$reference_signature->parameters->append($reference_parameter);
$reference_factory = Model::$runtime_callables->register_static_factory('test.reference', $reference_signature->return_type, $reference_signature);
callable_rejects(fn() => Call_Preparation::prepare($reference_factory, $one, $context), 'stable storage');
callable_rejects(fn() => Call_Preparation::prepare($reference_factory, $empty, $context), 'missing a required argument');

// Preparation reset keeps installed identities; syntax/type reset replaces both registries.
$registry = Model::$runtime_callables;
$bindings = Model::$cpp_callable_bindings;
Compiler_Lifecycle::reset_preparation();
callable_check(Model::$runtime_callables === $registry && Model::$cpp_callable_bindings === $bindings, 'Preparation reset replaced installed contracts');
Compiler_Lifecycle::reset_syntax();
callable_check(Model::$runtime_callables !== $registry && Model::$cpp_callable_bindings !== $bindings, 'Syntax reset retained old callable roots');
callable_rejects(fn() => Model::$cpp_callable_bindings->definition($runtime), 'no C++ binding');
echo "Callable model: shared source contracts, runtime static identity, admission, bindings and reset passed\n";
