<?php

/* Delayed exit transport is backend-local; syntax admission and completion are prepared. */
namespace scpp\compiler;

final class CPP_Finally
{
	/** Capture a fully converted value (or evaluate void forwarding), then exit this lambda. */
	public static function capture_return(cpp_finally_return $pending, string $value): string
	{
		$text = '';
		if ($pending->value !== null) {
			$text = $pending->value . ' = ' . $value . ";\n";
		}
		elseif ($value !== '') {
			$text = $value . ";\n";
		}
		return $text . $pending->flag . " = true;\nreturn;\n";
	}

	/** Native unwinding finishes protected locals before finally runs outside exception capture. */
	public static function generate(try_node $node, cpp_generation_context $context): string
	{
		$facts = $node->require_preparation();
		$id = $context->next_temporary;
		$context->next_temporary++;
		$exception = 'finally_exception_' . $id;
		$pending = new cpp_finally_return();
		$pending->flag = 'finally_return_' . $id;
		$state_type = '';
		$text = '';
		if ($context->concrete_types()) {
			$request = new cpp_runtime_operation(cpp_runtime_operation_kind::exception_state);
			$entry = CPP_Runtime_Operations::reference($request, $context);
			$state_type = 'scpp::internal::' . $entry->name;
			$text = "{\n" . $state_type . ' ' . $exception . ";\n";
		}
		else {
			$context->headers['exception'] = true;
			$text = "{\nstd::exception_ptr " . $exception . ";\n";
		}
		if ($facts->captures_return)
		{
			$text .= 'bool ' . $pending->flag . " = false;\n";
			$type = $context->return_type;
			// Entry returns use native int; only callable returns have a semantic type.
			$spelling = 'int';
			if ($type !== null) {
				$spelling = '';
				if (Type_Queries::canonical($type)->family() !== type_family::no_value) {
					$spelling = CPP_Declarations::type($type, $context);
				}
			}
			if ($spelling !== '') {
				$pending->value = 'finally_value_' . $id;
				$text .= $spelling . ' ' . $pending->value . "{};\n";
			}
		}
		$outer = $context->finally_return;
		$context->finally_return = $pending;
		try {
			$text .= "try { [&]() -> void {\n" . (new CPP_Syntax($context))->generate_protected($node) . "}(); }\n";
		}
		finally {
			$context->finally_return = $outer;
		}
		$text .= 'catch (...) { ' . $exception . ($state_type === '' ? ' = std::current_exception()' : '.capture()') . "; }\n";
		$finalizer /** block_node */ = $node->finalizer;
		$text .= (new CPP_Syntax($context))->generate_block($finalizer);
		$text .= $state_type === '' ? 'if (' . $exception . ') { std::rethrow_exception(' . $exception . "); }\n"
			: $exception . ".replay();\n";
		if ($facts->captures_return)
		{
			$value = $pending->value === null ? '' : $pending->value;
			$replay_type = $context->return_type;
			if (($value !== '') && ($replay_type !== null)) {
				$value = CPP_Declarations::move($value, $replay_type, $context);
			}
			$replay = $outer === null
				? 'return' . ($value === '' ? '' : ' ' . $value) . ";\n"
				: self::capture_return($outer, $value);
			$text .= 'if (' . $pending->flag . ") {\n" . $replay . "}\n";
		}
		if (!$facts->can_fall_through) {
			// The prepared exit composition excludes this path; keep native return analysis explicit.
			$text .= ($state_type === '' ? 'std::terminate' : $state_type . '::unreachable') . "(); // prepared non-fallthrough\n";
		}
		return $text . "}\n";
	}
}
