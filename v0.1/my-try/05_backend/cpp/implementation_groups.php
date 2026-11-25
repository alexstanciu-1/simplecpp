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

	/** Index membership once; assembly does not rescan every owner for every source. */
	public static function assemble(Storage $files /** Storage<prepared_file> */,
		Storage $ordered /** Storage<preparation_owner> */, cpp_program $program, ?CPP_Specializations $engine = null): Storage /** Storage<cpp_implementation_group> */
	{
		$by_source /** hash<cpp_implementation_source, collected_file> */ = new \SplObjectStorage /** hash<cpp_implementation_source, collected_file> */();
		foreach ($files as $prepared) { $by_source[$prepared->source] = new cpp_implementation_source(); }
		foreach ($ordered as $owner) {
			if (!($owner instanceof source_callable_signature_work) && !($owner instanceof record_definition_work)
				&& !($owner instanceof type_contract_work)) { continue; }
			$source = $owner->source;
			if ($owner instanceof source_callable_signature_work) {
				$source = self::callable_source(object_cast($owner, source_callable_signature_work::class)->callable_definition());
			}
			if (!isset($by_source[$source])) { continue; }
			$members /** Storage<preparation_owner> */ = $by_source[$source]->owners;
			$members->append($owner);
		}
		$groups /** Storage<cpp_implementation_group> */ = new Storage();
		foreach ($files as $prepared) {
			$owners /** Storage<preparation_owner> */ = $by_source[$prepared->source]->owners;
			foreach (self::source_groups($prepared->source, $owners, $program, $engine, false) as $group) { $groups->append($group); }
			if ($engine !== null) {
				foreach (self::source_groups($prepared->source, $owners, $program, $engine, true) as $group) { $groups->append($group); }
			}
		}
		return $groups;
	}

	/** Collect complete definitions with their own dependencies; static storage keeps source order. */
	private static function source_groups(collected_file $source,
		Storage $ordered /** Storage<preparation_owner> */, cpp_program $program, ?CPP_Specializations $engine, bool $asynchronous): Storage /** Storage<cpp_implementation_group> */
	{
		$path = CPP_Artifacts::unit_path($source);
		if ($asynchronous) { $path = 'specializations/async/' . q_substr($path, q_strlen('units/')); }
		$parts /** Storage<cpp_implementation_group> */ = new Storage();
		foreach ($ordered as $callable_owner) {
			$identity = 'record:' . object_cast($callable_owner->declaration(), collected_definition::class)->qualified_name();
			$group = new cpp_implementation_group($identity, $path);
			$dispatch_context = new cpp_generation_context($engine);
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
					$group->initialization_ordered = true;
				}
			}
			if (!($callable_owner instanceof source_callable_signature_work)) {
				if ($group->text !== '') {
					foreach ($dispatch_context->headers as $header => $used) { $group->headers[$header] = true; }
					$group->forward_declarations = $dispatch_context->forward_declarations;
					$parts->append($group);
				}
				continue;
			}
			$definition = object_cast($callable_owner, source_callable_signature_work::class)->callable_definition();
			$implementation_source = self::callable_source($definition);
			if ($implementation_source !== $source) { continue; }
			$callable_identity = 'callable:' . $definition->qualified_name();
			if ($definition instanceof collected_method) {
				$callable_identity .= ':' . CPP_Callables::method_name(object_cast($definition, collected_method::class));
			}
			$group = new cpp_implementation_group($callable_identity, $path);
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
			$parts->append($group);
		}
		$whole = self::combine($parts, $path);
		$result /** Storage<cpp_implementation_group> */ = new Storage();
		if ($whole->text === '') { return $result; }
		if (self::bytes($whole) <= 180000) { $result->append($whole); return $result; }
		// All ordered static storage remains in the original source unit, in original order.
		$storage /** Storage<cpp_implementation_group> */ = new Storage();
		$functions /** Storage<cpp_implementation_group> */ = new Storage();
		foreach ($parts as $part) {
			if ($part->initialization_ordered) { $storage->append($part); }
			else { $functions->append($part); }
		}
		if (!$storage->is_empty()) { $result->append(self::combine($storage, $path)); }
		self::partition($functions, q_substr($path, 0, q_strlen($path) - 4), '', $result);
		return $result;
	}

	private static function combine(Storage $parts /** Storage<cpp_implementation_group> */, string $path): cpp_implementation_group
	{
		$result = new cpp_implementation_group('implementation:' . $path, $path);
		foreach ($parts as $part) {
			$result->text .= $part->text;
			foreach ($part->headers as $header => $used) { $result->headers[$header] = true; }
			foreach ($part->forward_declarations as $name => $used) { $result->forward_declarations[$name] = true; }
		}
		return $result;
	}

	private static function bytes(cpp_implementation_group $group): int
	{
		return string_byte_len($group->text) + string_byte_len(CPP_Artifacts::preamble($group->headers, $group->forward_declarations, ''));
	}

	/** Stable digest prefixes split only an oversized branch; never split a function's text. */
	private static function partition(Storage $parts /** Storage<cpp_implementation_group> */, string $base, string $prefix,
		Storage $result /** Storage<cpp_implementation_group> */): void
	{
		if ($parts->is_empty()) { return; }
		$group = self::combine($parts, $base . '_b' . ($prefix === '' ? 'root' : $prefix) . '.cpp');
		if ((self::bytes($group) <= 180000) || ($parts->count() === 1) || (string_byte_len($prefix) >= 64)) {
			$result->append($group);
			return;
		}
		$buckets /** hash<cpp_implementation_parts> */ = [];
		foreach ($parts as $part) {
			$key = 'b' . string_byte_slice(content_sha256($part->identity), string_byte_len($prefix), 1);
			if (!isset($buckets[$key])) { $buckets[$key] = new cpp_implementation_parts(); }
			$members /** Storage<cpp_implementation_group> */ = $buckets[$key]->parts;
			$members->append($part);
		}
		foreach ($buckets as $key => $bucket) {
			self::partition($bucket->parts, $base, $prefix . string_byte_slice($key, 1, 1), $result);
		}
	}
}
