<?php

/* Storage provenance is shared preparation data; reference escape remains disabled. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function reference_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Model rejection must fail at the owning boundary, not at later emission. */
function reference_rejects(callable $action, string $message): void
{
	try {
		$action();
	}
	catch (\Exception $error) {
		reference_check(str_contains($error->getMessage(), $message), 'Wrong rejection: ' . $error->getMessage());
		return;
	}
	throw new \LogicException('Expected rejection: ' . $message);
}

/** Source-free contracts can describe a whole-parameter borrow without syntax or a backend. */
function reference_signature(canonical_type_use $type, int $index): callable_signature
{
	$signature = new callable_signature();
	$signature->return_type = $type;
	$signature->reference_return = new reference_return_contract($index);
	foreach ([0, 1] as $position) {
		$parameter = new callable_parameter();
		$parameter->type = $type;
		$parameter->mode = passing_mode::reference;
		$signature->parameters->append($parameter);
	}
	return $signature;
}

Compiler_Lifecycle::reset();
$input_module = new module('/reference-model', '/reference-model', '/reference-model');
$source = new file();
$source->path = 'main.phs';
$source->content = <<<'PHS'
struct Leaf { int32 $number; }
struct Box { Leaf $leaf; }
function sink(int32 &$n): void { $n = 3; }
function origins(int &$first, int &$second): int { return $first; }
function make(): Box { $local Box; return $local; }
function inspect(Box &$borrowed, Box $owned): void {
    $local Box;
    sink($borrowed->leaf->number);
    sink($owned->leaf->number);
    sink($local->leaf->number);
    { $local Box; sink($local->leaf->number); }
    sink($local->leaf->number);
    $items vector<Box>;
    $copy = $items[0];
    $snapshot = make()->leaf->number;
}
return 0;
PHS;
Source_Registry::add($input_module, $source);
Model::$modules->add($input_module->name, $input_module);
$compiler = new Compiler();
$compiler->exec_cpp();
$original_cpp = CPP_Output::single_unit(Model::$cpp_files)->text;
$function = Model::$global_scope->functions_named('inspect')[0]->syntax();
$body = $function->body;
$parameters = $function->require_preparation()->parameters;
$local = $body->statements[0]->require_preparation();
$expected_roots = [$parameters[0], $parameters[1], $local];
foreach ($expected_roots as $index => $expected)
{
	$call = $body->statements[$index + 1]->expression;
	$selection = $call->arguments[0]->require_preparation();
	$place = $selection->place;
	reference_check($selection->addressable() && $place !== null, 'Stable field lost its place');
	reference_check($place->root_storage() === $expected, 'Field path lost its actual storage root');
	reference_check($place->storage() === $selection->field, 'Field path lost its selected field identity');
	reference_check($place->base()->storage() instanceof prepared_field, 'Nested field projection was flattened');
	reference_check($place->base()->base()->storage() === $expected, 'Nested field base was copied or lost');
	reference_check($place->base()->base()->base() === null, 'Whole storage acquired a synthetic base');
	reference_check($call->require_call_preparation()->arguments[0]->conversion === null, 'Reference argument acquired a value conversion');
}
reference_check($parameters[0]->contract->mode === passing_mode::reference
	&& $parameters[1]->contract->mode === passing_mode::value, 'Borrow origin lost the parameter passing mode');
$inner = $body->statements[4];
$inner_root = $inner->statements[1]->expression->arguments[0]->require_preparation()->place->root_storage();
reference_check($inner_root === $inner->statements[0]->require_preparation()
	&& $inner_root !== $local, 'Shadowed storage reused the outer root');
reference_check($body->statements[5]->expression->arguments[0]->require_preparation()->place->root_storage() === $local,
	'Leaving a block lost the outer root');
foreach ([7, 8] as $index) {
	$value = $body->statements[$index]->expression->value->require_preparation();
	reference_check(!$value->addressable() && $value->place === null, 'Copied/temporary value acquired a storage origin');
}
$field = $body->statements[1]->expression->arguments[0]->require_preparation()->field;
reference_rejects(fn() => new prepared_place($field), 'root requires');
reference_rejects(fn() => new prepared_place($local, new prepared_place($local)), 'projection requires');
$write = new prepared_binding();
$write->resolved_kind = binding_kind::assignment;
reference_rejects(fn() => new prepared_place($write), 'not a write');

// Structural reference contracts do not grant permission to execute a borrowing call.
$integer = Language_Types::integer(Model::$language_scope);
$signature = reference_signature($integer, 0);
Callable_Contracts::validate($signature);
reference_check($signature->reference_return->parameter_index() === 0, 'Return origin changed');
reference_rejects(fn() => new reference_return_contract(-1), 'nonnegative');
$absent = reference_signature($integer, 2);
reference_rejects(fn() => Callable_Contracts::validate($absent), 'absent parameter');
$by_value = reference_signature($integer, 0);
$by_value->parameters[0]->mode = passing_mode::value;
reference_rejects(fn() => Callable_Contracts::validate($by_value), 'fixed reference parameter');
$variadic = reference_signature($integer, 0);
$variadic->parameters[0]->element_type = $integer;
reference_rejects(fn() => Callable_Contracts::validate($variadic), 'fixed reference parameter');
$mismatch = reference_signature($integer, 0);
$mismatch->return_type = Language_Types::boolean(Model::$language_scope);
reference_rejects(fn() => Callable_Contracts::validate($mismatch), 'exact parameter storage type');
reference_rejects(fn() => Model::$runtime_callables->register_static_factory('test.borrow.factory', $integer, $signature),
	'owned value');
$runtime = Model::$runtime_callables->register_function('test.borrow.identity', 'borrow_identity', $signature);
$empty = new Storage();
$context = new preparation_context();
reference_rejects(fn() => Call_Preparation::prepare($runtime, $empty, $context), 'await lifetime integration');

// Signature equivalence observes the result mode and symbolic parameter, not object identity.
$before = new prepared_function();
$before->signature = reference_signature($integer, 0);
$after = new prepared_function();
$after->signature = reference_signature($integer, 0);
$origin_parameters = Model::$global_scope->functions_named('origins')[0]->syntax()->require_preparation()->parameters;
foreach ([$before, $after] as $facts) {
	foreach ($origin_parameters as $index => $source_parameter) {
		$parameter = new prepared_parameter();
		$parameter->declaration = weakref_get($source_parameter->declaration);
		$parameter->contract = $facts->signature->parameters[$index];
		$facts->parameters->append($parameter);
	}
}
reference_check(Preparation_Changes::same_signature($before, $after), 'Equivalent return contracts differ');
$after->signature->reference_return = new reference_return_contract(1);
reference_check(!Preparation_Changes::same_signature($before, $after), 'Changed return origin was ignored');
$after->signature->reference_return = null;
reference_check(!Preparation_Changes::same_signature($before, $after), 'Reference-to-value return change was ignored');
$before->signature->reference_return = null;
reference_check(Preparation_Changes::same_signature($before, $after), 'Ordinary value signature changed');

// Cleanup clears attached places; re-preparation reconnects to current storage facts.
$old_selection = $body->statements[1]->expression->arguments[0];
$old_parameter = $parameters[0];
Compiler_Lifecycle::reset_preparation();
reference_check($old_selection->preparation() === null, 'Cleanup retained a place-bearing expression fact');
$compiler->prepare();
$compiler->cpp();
$new_parameter = $function->require_preparation()->parameters[0];
reference_check($new_parameter !== $old_parameter
	&& $old_selection->require_preparation()->place->root_storage() === $new_parameter,
	'Re-preparation retained a retired storage origin');
reference_check(CPP_Output::single_unit(Model::$cpp_files)->text === $original_cpp, 'Storage provenance changed value/reference lowering');

echo "Reference model: stable places, root identity, contract validation, execution gate and cleanup passed\n";
