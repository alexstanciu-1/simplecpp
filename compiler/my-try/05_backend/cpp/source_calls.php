<?php
namespace scpp\compiler;

/** Source call transport; receiver lifetime and argument boundaries come from preparation. */
final class CPP_Source_Calls
{
	public static function require_signature(prepared_call $facts): void
	{
		if (($facts->signature->reference_return !== null) || ($facts->variadic_parameter !== null)) {
			throw new \RuntimeException('prelower_templates: source call reference returns and variadic packs need transport');
		}
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		foreach ($parameters as $parameter) {
			if ($parameter->mode === passing_mode::observation) {
				throw new \RuntimeException('prelower_templates: source call observation needs full-expression transport');
			}
		}
	}

	/** Constructor requests reuse both existing construction facts and selected call facts. */
	public static function construct(prepared_object_construction $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context): string
	{
		$call /** prepared_call */ = $facts->constructor_call;
		self::require_signature($call);
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::construct_call);
		$request->construction = $facts;
		$request->call = $call;
		$request->result = $facts->type;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		foreach ($call->signature->parameters as $parameter) { $inputs->append($parameter->type); }
		return CPP_Declarations::selected_call($call, $arguments, $context, '', $facts->type, '', $request);
	}

	public static function native_construction(cpp_runtime_operation $request, array $arguments /** vector<string> */,
		cpp_generation_context $context): string
	{
		$facts /** prepared_object_construction */ = $request->construction;
		$call /** prepared_call */ = $request->call;
		$callee = CPP_Record_Construction::allocation($facts->type, $facts->constructed_type, $facts->storage, $context);
		$method = object_cast(weakref_get($call->target), collected_method::class);
		$values = CPP_Record_Construction::key($method);
		$parameters /** Storage<callable_parameter> */ = $call->signature->parameters;
		if (($values !== '') && !$parameters->is_empty()) { $values .= ', '; }
		return $callee . '(' . $values . self::native_arguments($call, $arguments, 0, $context) . ')';
	}

	/** Stable identity includes the selected declaration, dispatch and explicit/default value categories. */
	public static function identity(prepared_call $facts): string
	{
		$method = object_cast(weakref_get($facts->target), collected_method::class);
		$key = $method->declaring_type()->qualified_name() . ':' . CPP_Callables::method_name($method);
		if ($facts->receiver !== null) {
			$receiver /** prepared_method_receiver */ = $facts->receiver;
			$lifetime /** method_receiver_lifetime */ = $receiver->lifetime;
			$key .= ':' . Method_Receiver_Lifetime_Name::text($lifetime);
			if ($receiver->dispatch_type !== null) { $key .= ':' . CPP_Opaque_Representation::identity($receiver->dispatch_type); }
		}
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		foreach ($parameters as $parameter) {
			$mode /** passing_mode */ = $parameter->mode;
			$access /** storage_access */ = $parameter->access;
			$key .= ':' . Passing_Mode_Name::text($mode) . ':' . Storage_Access_Name::text($access);
		}
		$arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		foreach ($arguments as $argument) {
			$boundary /** value_boundary_operation */ = $argument->boundary;
			$key .= ':' . Value_Boundary_Operation_Name::text($boundary);
		}
		return $key;
	}

	public static function mutable_input(prepared_call $facts, int $position, bool $has_receiver): bool
	{
		if ($has_receiver && ($position === 0)) { return true; }
		$index = $position - ($has_receiver ? 1 : 0);
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		if ($index >= q_count($arguments)) { return true; }
		if ($arguments[$index]->boundary === value_boundary_operation::consume_value) { return true; }
		return ($parameters[$index]->mode === passing_mode::reference)
			&& ($parameters[$index]->access !== storage_access::read_only);
	}

	/** Preserve the ordinary call's copy/consume/default categories without a second snapshot. */
	public static function native_arguments(prepared_call $facts, array $arguments /** vector<string> */,
		int $offset, cpp_generation_context $context): string
	{
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		$values = '';
		foreach ($facts->signature->parameters as $index => $parameter) {
			if ($index !== 0) { $values .= ', '; }
			$value = $arguments[$index + $offset];
			$consume = $index >= q_count($prepared_arguments);
			if (!$consume) { $consume = $prepared_arguments[$index]->boundary === value_boundary_operation::consume_value; }
			if ($consume) { $context->headers['utility'] = true; $value = 'std::move(' . $value . ')'; }
			$values .= $value;
		}
		return $values;
	}
}
