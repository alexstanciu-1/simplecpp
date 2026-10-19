<?php
namespace scpp\compiler;

/** Ordinary member surfaces over composed storage; selected calls still own all value boundaries. */
final class CPP_Concrete_Members
{
	public static function reference(prepared_call $call, cpp_generation_context $context): string
	{
		$target = object_cast(weakref_get($call->target), runtime_function_definition::class);
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::concrete_member);
		$request->call = $call;
		$request->binding = Model::$cpp_callable_bindings->function_binding($target);
		$receiver /** prepared_method_receiver */ = $call->receiver;
		$request->inputs->append($receiver->record_type);
		$engine /** CPP_Specializations */ = $context->specialization_engine;
		$entry = $engine->entry($engine->request_operation($request));
		$context->runtime_operations[$entry->name] = $request;
		$context->headers[$entry->header] = true;
		return $request->binding->symbol();
	}

	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = CPP_Runtime_Operations::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) { return $existing; }
		$call /** prepared_call */ = $request->call;
		if (($call->signature->reference_return !== null) || ($call->variadic_parameter !== null)
			|| (Type_Queries::canonical($call->type)->family() !== type_family::no_value)) {
			throw new \RuntimeException('Concrete runtime members: result and variadic transport are not implemented');
		}
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$owner_type = $inputs[0];
		$owner = $engine->entry($engine->request_composed($owner_type));
		$native = CPP_Types::implementation_representation($owner_type, $engine);
		$binding /** cpp_function_binding */ = $request->binding;
		$context = new cpp_generation_context($engine);
		$parameters = '';
		$arguments = '';
		$separator = '';
		$declaration_headers /** hash<bool> */ = [];
		$forwards /** hash<bool> */ = [];
		foreach ($call->signature->parameters as $index => $parameter) {
			$mapping = CPP_Types::representation($parameter->type, $context);
			CPP_Types::record_dependencies($mapping, $context);
			if ($mapping->opaque) {
				$parameter_entry = $engine->entry($engine->request($parameter->type));
				$forwards['namespace scpp::internal { struct ' . $parameter_entry->name . '; }'] = true;
			}
			else {
				foreach ($mapping->headers as $header => $used) { $declaration_headers[$header] = true; }
			}
			$qualifier = $parameter->access === storage_access::read_only ? 'const ' : '';
			$reference = '&';
			$argument = 'a' . $index;
			if (($parameter->mode === passing_mode::value) && CPP_Callables::argument_consumed($call, $index)) {
				$reference = '&&';
				$argument = 'std::move(' . $argument . ')';
			}
			elseif ($parameter->mode === passing_mode::observation) { $qualifier = 'const '; }
			$parameters .= $separator . $qualifier . $mapping->spelling . $reference . ' a' . $index;
			$arguments .= $separator . $argument;
			$separator = ', ';
		}
		$prototype = $binding->symbol() . '(' . $parameters . ')';
		$headers = $native->headers;
		if ($binding->header() !== '') { $headers[$binding->header()] = true; }
		foreach ($context->headers as $header => $used) { $headers[$header] = true; }
		$body = CPP_Artifacts::includes($headers, '') . "#include <memory>\n#include <utility>\nnamespace scpp::internal {\n"
			. 'void ' . $owner->name . '::' . $prototype . ' { std::launder(static_cast<' . $native->spelling
			. '*>(address()))->' . $binding->symbol() . '(' . $arguments . "); }\n}\n";
		$prefix = CPP_Artifacts::includes($declaration_headers, '');
		foreach ($forwards as $declaration => $used) { $prefix .= $declaration . "\n"; }
		return new cpp_specialization_entry($name, $owner->header, 'void ' . $prototype . ";\n", $body, [], '', '', '',
			$owner->name, $prefix);
	}
}
