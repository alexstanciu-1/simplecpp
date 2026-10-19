<?php
namespace scpp\compiler;

/** Source method transport; receiver lifetime and argument boundaries come from preparation. */
final class CPP_Method_Calls
{
	public static function generate(call_node $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_call_preparation();
		if (!(weakref_get($facts->target) instanceof collected_method)) {
			throw new \RuntimeException('runtime template ABI: provider methods need receiver transport');
		}
		if (($facts->signature->reference_return !== null) || ($facts->variadic_parameter !== null)) {
			throw new \RuntimeException('runtime template ABI: method reference returns and variadic packs need transport');
		}
		$receiver /** prepared_method_receiver */ = $facts->receiver;
		if (($receiver->lifetime !== method_receiver_lifetime::retain_shared)
			&& ($receiver->lifetime !== method_receiver_lifetime::borrow_inline)) {
			throw new \RuntimeException('runtime template ABI: unsupported method receiver lifetime');
		}
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		foreach ($parameters as $parameter) {
			if ($parameter->mode === passing_mode::observation) {
				throw new \RuntimeException('runtime template ABI: method observation needs full-expression transport');
			}
		}
		$base /** expression_node */ = $syntax->receiver;
		$type = $base->require_preparation()->type;
		$value = (new CPP_Syntax($context))->expression($base);
		$name = 'receiver_' . $context->next_temporary;
		$context->next_temporary++;
		$prefix = ($receiver->lifetime === method_receiver_lifetime::borrow_inline ? 'auto& ' : 'auto ')
			. $name . ' = ' . $value . ";\n";
		if ($receiver->lifetime === method_receiver_lifetime::retain_shared) {
			$check = new cpp_runtime_operation(cpp_runtime_operation_kind::receiver_check);
			$check_inputs /** Storage<canonical_type_use> */ = $check->inputs;
			$check_inputs->append($type);
			$prefix .= CPP_Runtime_Operations::invoke($check, [$name], $context) . ";\n";
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::method_call);
		$request->call = $facts;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($type);
		foreach ($parameters as $parameter) { $inputs->append($parameter->type); }
		if (Type_Queries::canonical($facts->type)->family() !== type_family::no_value) { $request->result = $facts->type; }
		return CPP_Declarations::selected_call($facts, $syntax->arguments, $context, $name, null, $prefix, $request);
	}

	/** Stable identity includes the selected declaration, dispatch and explicit/default value categories. */
	public static function identity(prepared_call $facts): string
	{
		$method = object_cast(weakref_get($facts->target), collected_method::class);
		$receiver /** prepared_method_receiver */ = $facts->receiver;
		$key = $method->declaring_type()->qualified_name() . ':' . CPP_Callables::method_name($method)
			. ':' . enum_name($receiver->lifetime);
		if ($receiver->dispatch_type !== null) { $key .= ':' . CPP_Opaque_Representation::identity($receiver->dispatch_type); }
		foreach ($facts->signature->parameters as $parameter) {
			$key .= ':' . enum_name($parameter->mode) . ':' . enum_name($parameter->access);
		}
		foreach ($facts->arguments as $argument) { $key .= ':' . enum_name($argument->boundary); }
		return $key;
	}

	public static function mutable_input(prepared_call $facts, int $position): bool
	{
		if ($position === 0) { return true; }
		$index = $position - 1;
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		if ($index >= q_count($arguments)) { return true; }
		if ($arguments[$index]->boundary === value_boundary_operation::consume_value) { return true; }
		return ($parameters[$index]->mode === passing_mode::reference)
			&& ($parameters[$index]->access !== storage_access::read_only);
	}

	public static function native_call(cpp_runtime_operation $request, array $arguments /** vector<string> */,
		cpp_generation_context $context): string
	{
		$facts /** prepared_call */ = $request->call;
		$receiver /** prepared_method_receiver */ = $facts->receiver;
		$types /** Storage<canonical_type_use> */ = $request->inputs;
		$arrow = ($receiver->lifetime !== method_receiver_lifetime::borrow_inline) || $types[0]->by_value();
		$callee = CPP_Declarations::method_reference($facts, $arguments[0], $arrow, $context);
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		$values = '';
		foreach ($facts->signature->parameters as $index => $parameter) {
			if ($index !== 0) { $values .= ', '; }
			$value = $arguments[$index + 1];
			$consume = $index >= q_count($prepared_arguments);
			if (!$consume) { $consume = $prepared_arguments[$index]->boundary === value_boundary_operation::consume_value; }
			if ($consume) { $context->headers['utility'] = true; $value = 'std::move(' . $value . ')'; }
			$values .= $value;
		}
		return $callee . '(' . $values . ')';
	}
}
