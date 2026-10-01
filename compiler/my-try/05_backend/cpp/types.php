<?php

/* Role: map canonical type identities to independent C++ representation bindings. */
namespace scpp\compiler;

final class CPP_Types
{
	/** Resolve the semantic identity first; backend metadata never establishes type equality. */
	public static function representation(canonical_type_use $type_use): cpp_type
	{
		$result = self::unmodified_representation($type_use);
		if ($type_use->by_value()) {
			$modifier = Model::$cpp_type_bindings->modifier(type_use_modifier_kind::by_value);
			$result->spelling = $modifier->name() . '<' . $result->spelling . '>';
			self::add_header($result, $modifier->header());
			$result->literal = cpp_literal_kind::none;
		}
		return $result;
	}

	/** Add every representation dependency to one generation context exactly once. */
	public static function record_headers(cpp_type $type, cpp_generation_context $context): void
	{
		foreach ($type->headers as $header => $used) {
			$context->headers[$header] = true;
		}
	}

	/** Resolve the canonical core; recursive arguments contribute their own direct headers. */
	private static function unmodified_representation(canonical_type_use $type_use): cpp_type
	{
		$type = Type_Preparation::canonical($type_use);
		$definition = $type->definition();
		$result = new cpp_type();
		if ($type instanceof applied_template_type) {
			$application = object_cast($type, applied_template_type::class);
			$binding = Model::$cpp_type_bindings->definition($definition->definition_id());
			$arguments = '';
			foreach ($application->arguments() as $position => $argument) {
				if ($position !== 0) {
					$arguments .= ', ';
				}
				$argument_type = self::representation($argument);
				$arguments .= $argument_type->spelling;
				self::merge_headers($result, $argument_type);
			}
			$result->spelling = $binding->name() . '<' . $arguments . '>';
			self::add_header($result, $binding->header());
			$result->literal = cpp_literal_kind::none;
			return $result;
		}
		if ($definition->origin() === type_definition_origin::source) {
			$entry = Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
			$result->spelling = CPP_Generator::source_name('record', $entry->name);
			$result->literal = cpp_literal_kind::none;
			return $result;
		}

		$binding = Model::$cpp_type_bindings->definition($definition->definition_id());
		$result->spelling = $binding->name();
		self::add_header($result, $binding->header());
		$result->literal = self::literal_kind($type, $definition);
		return $result;
	}

	private static function add_header(cpp_type $type, string $header): void
	{
		if ($header !== '') {
			$type->headers[$header] = true;
		}
	}

	private static function merge_headers(cpp_type $destination, cpp_type $source): void
	{
		foreach ($source->headers as $header => $used) {
			$destination->headers[$header] = true;
		}
	}

	/** Literal families are semantic; exact target spelling remains in the binding catalog. */
	private static function literal_kind(canonical_type_i $type, type_definition_i $definition): cpp_literal_kind
	{
		if ($type->family() === type_family::integer) {
			return cpp_literal_kind::signed_integer;
		}
		if ($type->family() === type_family::floating) {
			return cpp_literal_kind::floating;
		}
		if ($type->family() === type_family::boolean) {
			return cpp_literal_kind::boolean;
		}
		if (($type->family() === type_family::nominal) && ($definition->name() === 'string')) {
			return cpp_literal_kind::string_value;
		}
		return cpp_literal_kind::none;
	}
}
