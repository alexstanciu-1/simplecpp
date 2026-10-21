<?php
namespace scpp\compiler;

/** Assemble rendered fragments and dispatch definitions without retaining semantic work. */
final class CPP_Implementation_Groups
{
	/** Shared placement query for assembly and optional diagnostics. */
	public static function callable_source(collected_source_callable $definition): collected_file
	{
		if ($definition instanceof collected_method) {
			return object_cast($definition, collected_method::class)->declaring_type()->collection;
		}
		return $definition->collection;
	}

	/** Existing file order and source membership remain the grouping policy. */
	public static function assemble(Storage $files /** Storage<prepared_file> */,
		Storage $ordered /** Storage<preparation_owner> */, cpp_program $program, ?CPP_Specializations $engine = null): Storage /** Storage<cpp_implementation_group> */
	{
		$groups /** Storage<cpp_implementation_group> */ = new Storage();
		foreach ($files as $prepared) {
			$group = self::source_group($prepared->source, $ordered, $program, $engine, false);
			if ($group->text !== '') { $groups->append($group); }
			if ($engine !== null) {
				$async = self::source_group($prepared->source, $ordered, $program, $engine, true);
				if ($async->text !== '') { $groups->append($async); }
			}
		}
		return $groups;
	}

	/** Preserve member/dispatch/storage and include insertion order from artifact assembly. */
	private static function source_group(collected_file $source,
		Storage $ordered /** Storage<preparation_owner> */, cpp_program $program, ?CPP_Specializations $engine, bool $asynchronous): cpp_implementation_group
	{
		$path = CPP_Artifacts::unit_path($source);
		if ($asynchronous) { $path = 'specializations/async/' . q_substr($path, q_strlen('units/')); }
		$group = new cpp_implementation_group('implementation:' . $path, $path);
		$dispatch_context = new cpp_generation_context($engine);
		foreach ($ordered as $callable_owner) {
			if (!$asynchronous && (($callable_owner instanceof record_definition_work) || ($callable_owner instanceof type_contract_work))) {
				if ($callable_owner->source === $source) {
					$dispatch_owner = object_cast($callable_owner->declaration(), collected_member_owner::class);
					$group->text .= CPP_Dispatch::definitions($dispatch_owner, $dispatch_context);
				}
			}
			if (!$asynchronous && ($callable_owner instanceof record_definition_work) && ($callable_owner->source === $source)) {
				$layout = $program->fragments[$callable_owner];
				if ($layout->record_definition_text !== '') {
					$record = object_cast($callable_owner, record_definition_work::class)->record_definition();
					$group->headers[CPP_Artifacts::type_header($record->qualified_name())] = true;
					foreach ($layout->definition_headers as $header => $used) { $group->headers[$header] = true; }
					$group->text .= $layout->record_definition_text;
				}
			}
			if (!($callable_owner instanceof source_callable_signature_work)) { continue; }
			$definition = object_cast($callable_owner, source_callable_signature_work::class)->callable_definition();
			$implementation_source = self::callable_source($definition);
			if ($implementation_source !== $source) { continue; }
			$function = $definition->callable_syntax();
			$compiled_async = ($engine !== null) && ($function->require_preparation()->signature->async_value_type !== null);
			if ($compiled_async !== $asynchronous) { continue; }
			if (!$function->has_parsed_body()) { continue; }
			$signature_owner = object_cast($definition->preparation_work_owner(), preparation_owner::class);
			$body_owner = object_cast($function->require_body()->work(), preparation_owner::class);
			$signature = $program->fragments[$signature_owner];
			$body = $program->fragments[$body_owner];
			if ($definition instanceof collected_method) {
				$group->headers[CPP_Artifacts::type_header(object_cast($definition, collected_method::class)->declaring_type()->qualified_name())] = true;
			}
			else { $group->headers[CPP_Artifacts::callable_header($definition->qualified_name())] = true; }
			foreach ($signature->definition_headers as $header => $used) {
				$group->headers[$header] = true;
			}
			foreach ($body->headers as $header => $used) { $group->headers[$header] = true; }
			foreach ($body->forward_declarations as $name => $used) {
				$group->forward_declarations[$name] = true;
			}
			$group->text .= $signature->text . $body->initializer_text . "\n{\n" . $body->text . "}\n\n" . $signature->async_bridge_text;
		}
		foreach ($dispatch_context->headers as $header => $used) { $group->headers[$header] = true; }
		foreach ($dispatch_context->forward_declarations as $name => $used) { $group->forward_declarations[$name] = true; }
		return $group;
	}
}
