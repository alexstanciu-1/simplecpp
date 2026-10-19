<?php
namespace scpp\compiler;

/** Member interfaces for prepared mutations; arithmetic and failure behavior remain native. */
final class CPP_Concrete_Mutations
{
	/** Ordinary target evaluation remains with the emitter; demand only the selected interface. */
	public static function require_interface(operator_decision $decision, canonical_type_use $target,
		string $spelling, cpp_generation_context $context): void
	{
		$mapping = CPP_Types::representation($target, $context);
		if (!$mapping->opaque)
		{
			CPP_Generator::record_operator_headers($decision, $context);
			$context->headers['scpp/generated/operators.hpp'] = true;
			return;
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::concrete_mutation);
		$request->decision = $decision;
		$request->result = $decision->result_type;
		$request->spelling = $spelling;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($target);
		CPP_Runtime_Operations::reference($request, $context);
	}

	/** Reuse composed storage and member assembly, without a type-family dispatch. */
	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = CPP_Runtime_Operations::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) {
			return $existing;
		}
		$decision /** operator_decision */ = $request->decision;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$owner = $engine->entry($engine->request_composed($inputs[0]));
		$native = CPP_Types::implementation_representation($inputs[0], $engine);
		$postfix = ($decision->source_operator === operator_kind::post_increment)
			|| ($decision->source_operator === operator_kind::post_decrement);
		$prototype = 'operator' . $request->spelling . ($postfix ? '(int)' : '()');
		$output = $owner->name . ($postfix ? '' : '&');
		$operand = '(*std::launder(static_cast<' . $native->spelling . '*>(address())))';
		$expression = $postfix ? $operand . $request->spelling : $request->spelling . $operand;
		$body = 'static_assert(std::is_same_v<decltype(' . $expression . '), ' . $native->spelling . '&>); '
			. $expression . '; return *this;';
		if ($postfix) {
			$body = CPP_Runtime_Operations::concrete_call_result($owner->name, $native->spelling, '', $expression);
		}
		$context = new cpp_generation_context();
		CPP_Generator::record_operator_headers($decision, $context);
		$headers = $native->headers;
		foreach ($context->headers as $header => $used) {
			$headers[$header] = $used;
		}
		$headers['scpp/generated/operators.hpp'] = true;
		$implementation = CPP_Artifacts::includes($headers, '')
			. "#include <memory>\n#include <type_traits>\nnamespace scpp::internal {\n"
			. $output . ' ' . $owner->name . '::' . $prototype . ' { ' . $body . " }\n}\n";
		return new cpp_specialization_entry($name, $owner->header, $output . ' ' . $prototype . ";\n",
			$implementation, [], '', '', '', $owner->name);
	}
}
