<?php
namespace scpp\compiler;

/** Coroutine implementation stays behind the concrete boundary; source facts own admission. */
final class CPP_Async
{
	public static function native_task(canonical_type_use $type, cpp_generation_context $context): string
	{
		$mapping = $context->specialization_engine !== null
			? CPP_Types::implementation_representation($type, $context->specialization_engine)
			: CPP_Types::representation($type);
		CPP_Types::record_dependencies($mapping, $context);
		return $mapping->spelling;
	}

	public static function interface(canonical_type_use $type, string $name, string $native,
		CPP_Specializations $engine): cpp_type_interface
	{
		$task = object_cast(Type_Queries::canonical($type)->definition(), async_task_type_definition::class);
		$context = new cpp_generation_context($engine);
		$value = CPP_Declarations::type($task->value_type, $context);
		$interface = new cpp_type_interface();
		$interface->declaration_prefix = CPP_Artifacts::includes($context->headers, '') . "#include <stdint.h>\n";
		$interface->declaration = $value . " wait();\n";
		$interface->implementation = $value . ' ' . $name . '::wait() { return scpp::async_core::sync_wait(std::move(unwrap(*this))); }' . "\n";
		if (Type_Queries::canonical($task->value_type)->family() === type_family::no_value) {
			$interface->declaration .= 'static ' . $name . " sleep(int64_t milliseconds);\n";
			$interface->implementation .= 'static ' . $native . ' sleep_' . $name . '(int64_t milliseconds) { co_await scpp::async_core::sleep_ms(scpp::int_t<>(milliseconds)); }' . "\n"
				. $name . ' ' . $name . '::sleep(int64_t milliseconds) { auto task = sleep_' . $name . '(milliseconds); return '
				. self::wrap_native($name, $native, 'task') . "; }\n";
		}
		return $interface;
	}

	/** Construction callback runs immediately; the resulting object owns the moved frame. */
	private static function wrap_native(string $storage, string $native, string $value): string
	{
		return $storage . '(' . $storage . '::operation_result{}, +[](void* out, const void* in) { ::new(out) '
			. $native . '(std::move(*const_cast<' . $native . '*>(static_cast<const ' . $native . '*>(in)))); }, &' . $value . ')';
	}

	public static function bridge(callable_declaration_node $syntax, cpp_generation_context $context): string
	{
		$definition = object_cast($syntax->occurrence(), collected_function::class);
		$signature = $syntax->require_preparation()->signature;
		$arguments /** vector<string> */ = [];
		$parameters /** Storage<prepared_parameter> */ = $syntax->require_preparation()->parameters;
		foreach ($parameters as $parameter) {
			$arguments[] = 'std::move(' . CPP_Generator::local_name(object_cast(weakref_get($parameter->declaration), collected_name::class)) . ')';
		}
		$storage = CPP_Declarations::type($signature->return_type, $context);
		$native = self::native_task($signature->return_type, $context);
		$context->headers['new'] = true;
		$context->headers['utility'] = true;
		return CPP_Declarations::signature($syntax, $context, false) . ' { auto task = coroutine_'
			. CPP_Callables::source_reference($definition) . '(' . q_implode(', ', $arguments) . '); return '
			. self::wrap_native($storage, $native, 'task') . "; }\n";
	}

	public static function await_value(prepared_await $facts, string $operand, cpp_generation_context $context): string
	{
		if ($facts->operation === await_operation::synchronous_wait) {
			if ($context->concrete_types()) { return '(' . $operand . ').wait()'; }
			$context->headers['scpp/async_core.hpp'] = true;
			return 'scpp::async_core::sync_wait(' . $operand . ')';
		}
		$native = $operand;
		if ($context->concrete_types()) {
			$native = '*static_cast<' . self::native_task($facts->task_type, $context) . '*>((' . $operand . ').address())';
		}
		return '(co_await (' . $native . '))';
	}

	public static function call(call_node $syntax, prepared_async_call $facts, cpp_generation_context $context): string
	{
		$arguments /** Storage<expression_node> */ = $syntax->arguments;
		$operand = (new CPP_Syntax($context))->expression($arguments[0]);
		if ($facts->awaited !== null) { return self::await_value($facts->awaited, $operand, $context); }
		$type = $syntax->require_preparation()->type;
		if ($context->concrete_types()) {
			return CPP_Declarations::type($type, $context) . '::sleep((' . $operand . ').native_value())';
		}
		$context->headers['scpp/async_core.hpp'] = true;
		return 'scpp::async_core::sleep_task(' . $operand . ')';
	}
}
