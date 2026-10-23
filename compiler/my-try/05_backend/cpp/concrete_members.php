<?php
namespace scpp\compiler;

/** Ordinary member surfaces over composed storage; selected calls still own all value boundaries. */
final class CPP_Concrete_Members
{
	/** Current-instance calls borrow the payload; they must not manufacture a shared owner. */
	public static function current_instance(prepared_call $call, cpp_generation_context $context): string
	{
		$request = self::request($call);
		$entry = CPP_Runtime_Operations::reference($request, $context);
		return 'scpp::internal::' . $entry->name . '(this)';
	}

	public static function reference(prepared_call $call, cpp_generation_context $context): string
	{
		$request = self::request($call);
		CPP_Runtime_Operations::reference($request, $context);
		$binding /** cpp_function_binding */ = $request->binding;
		return $binding->symbol();
	}

	private static function request(prepared_call $call): cpp_runtime_operation
	{
		$target = object_cast(weakref_get($call->target), runtime_function_definition::class);
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::concrete_member);
		$request->call = $call;
		$request->binding = Model::$cpp_callable_bindings->function_binding($target);
		$receiver /** prepared_method_receiver */ = $call->receiver;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($receiver->record_type);
		return $request;
	}

	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = CPP_Runtime_Operations::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) { return $existing; }
		$call /** prepared_call */ = $request->call;
		$receiver /** prepared_method_receiver */ = $call->receiver;
		if ($receiver->lifetime === method_receiver_lifetime::current_instance) {
			return self::borrowed_entry($request, $engine);
		}
		$shared = $receiver->lifetime === method_receiver_lifetime::retain_shared;
		if (($call->signature->reference_return !== null) || ($call->variadic_parameter !== null)) {
			throw new \RuntimeException('Concrete runtime members: reference result and variadic transport are not implemented');
		}
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$owner_type = $inputs[0];
		$owner = $engine->entry($engine->request_composed($owner_type));
		$native = CPP_Types::implementation_representation($owner_type, $engine);
		$payload_provider = CPP_Types::uses_payload_arguments($owner_type);
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
			if ($payload_provider) {
				$argument = CPP_Wrapper_Transport::payload_value($parameter->type, 'a' . $index, false, $engine, $context);
			}
			$arguments .= $separator . $argument;
			$separator = ', ';
		}
		$prototype = $binding->symbol() . '(' . $parameters . ')';
		$return_type = 'void';
		$expression = 'std::launder(static_cast<' . $native->spelling
			. '*>(address()))->' . $binding->symbol() . '(' . $arguments . ')';
		if ($shared) {
			$expression = '(*std::launder(static_cast<' . $native->spelling
				. '*>(address())))->' . $binding->symbol() . '(' . $arguments . ')';
		}
		$operation = $expression . ';';
		if (Type_Queries::canonical($call->type)->family() !== type_family::no_value) {
			$result = CPP_Types::representation($call->type, $context);
			CPP_Types::record_dependencies($result, $context);
			$return_type = $result->spelling;
			if ($result->opaque) {
				$result_entry = $engine->entry($engine->request($call->type));
				$forwards['namespace scpp::internal { struct ' . $result_entry->name . '; }'] = true;
				$native_result = CPP_Types::implementation_representation($call->type, $engine);
				CPP_Types::record_dependencies($native_result, $context);
				if (CPP_Wrapper_Transport::payload($call->type) !== null) {
					$expression = CPP_Wrapper_Transport::adapt($call->type, $expression, true, $engine, $context);
				}
				if ($payload_provider) { $expression = CPP_Wrapper_Transport::provider_vector($call->type, $expression, true, $engine, $context); }
				$operation = CPP_Runtime_Operations::concrete_call_result($return_type, $native_result->spelling, '', $expression);
			}
			else {
				foreach ($result->headers as $header => $used) { $declaration_headers[$header] = true; }
				$operation = 'return ' . $expression . ';';
			}
		}
		$headers = $native->headers;
		if ($binding->header() !== '') { $headers[$binding->header()] = true; }
		foreach ($context->headers as $header => $used) { $headers[$header] = true; }
		$body = CPP_Artifacts::includes($headers, '') . "#include <memory>\n#include <utility>\n#include <type_traits>\n#include <new>\nnamespace scpp::internal {\n"
			. $return_type . ' ' . $owner->name . '::' . $prototype . ' { ' . $operation . " }\n}\n";
		$prefix = CPP_Artifacts::includes($declaration_headers, '');
		foreach ($forwards as $declaration => $used) { $prefix .= $declaration . "\n"; }
		$layout_dependencies /** hash<bool> */ = [];
		return new cpp_specialization_entry($name, $owner->header, $return_type . ' ' . $prototype . ";\n", $body, $layout_dependencies, '', '', '',
			$owner->name, $prefix);
	}

	private static function borrowed_entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$call /** prepared_call */ = $request->call;
		$parameters /** Storage<callable_parameter> */ = $call->signature->parameters;
		if (!$parameters->is_empty() || ($call->signature->reference_return !== null) || ($call->variadic_parameter !== null)) {
			throw new \RuntimeException('Borrowed runtime members require zero parameters and a value or void result');
		}
		$receiver /** prepared_method_receiver */ = $call->receiver;
		$payload = CPP_Types::payload($receiver->record_type, new cpp_generation_context($engine));
		$binding /** cpp_function_binding */ = $request->binding;
		$headers = $payload->headers;
		$headers[$binding->header()] = true;
		$expression = 'static_cast<' . $payload->spelling . '*>(receiver)->' . $binding->symbol() . '()';
		$output = 'void';
		$body = $expression . ';';
		$public_headers /** hash<bool> */ = [];
		if (Type_Queries::canonical($call->type)->family() !== type_family::no_value) {
			$result = CPP_Types::representation($call->type, new cpp_generation_context($engine));
			$native = CPP_Types::implementation_representation($call->type, $engine);
			foreach ($native->headers as $header => $used) { $headers[$header] = true; }
			foreach ($result->headers as $header => $used) { $headers[$header] = true; }
			$public_headers = $result->headers;
			$output = $result->spelling;
			$body = $result->opaque ? CPP_Runtime_Operations::concrete_call_result($output, $native->spelling, '', $expression)
				: 'return ' . $expression . ';';
		}
		$name = CPP_Runtime_Operations::name($request);
		$prototype = $output . ' ' . $name . '(void* receiver)';
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp',
			"#pragma once\n" . CPP_Artifacts::includes($public_headers, '') . 'namespace scpp::internal { ' . $prototype . "; }\n",
			CPP_Artifacts::includes($headers, '') . "#include <new>\n#include <type_traits>\nnamespace scpp::internal { "
				. $prototype . ' { ' . $body . " } }\n", /** hash<bool> */ []);
	}
}
