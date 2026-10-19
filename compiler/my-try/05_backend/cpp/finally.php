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
			$text = $pending->value . '.emplace(' . $value . ");\n";
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
		$context->headers['exception'] = true;
		$text = "{\nstd::exception_ptr " . $exception . ";\n";
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
				$context->headers['optional'] = true;
				$context->headers['utility'] = true;
				$text .= 'std::optional<' . $spelling . '> ' . $pending->value . ";\n";
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
		$text .= 'catch (...) { ' . $exception . " = std::current_exception(); }\n";
		$finalizer /** block_node */ = $node->finalizer;
		$text .= (new CPP_Syntax($context))->generate_block($finalizer);
		$text .= 'if (' . $exception . ') { std::rethrow_exception(' . $exception . "); }\n";
		if ($facts->captures_return)
		{
			$value = $pending->value === null ? '' : 'std::move(*' . $pending->value . ')';
			$replay = $outer === null
				? 'return' . ($value === '' ? '' : ' ' . $value) . ";\n"
				: self::capture_return($outer, $value);
			$text .= 'if (' . $pending->flag . ") {\n" . $replay . "}\n";
		}
		if (!$facts->can_fall_through) {
			// The prepared exit composition excludes this path; keep native return analysis explicit.
			$text .= "std::terminate(); // unreachable: prepared finally region cannot fall through\n";
		}
		return $text . "}\n";
	}
}
