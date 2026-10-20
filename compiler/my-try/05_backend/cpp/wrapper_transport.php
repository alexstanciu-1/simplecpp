<?php
namespace scpp\compiler;

/** Physical wrapper adaptation; registry identities and runtime state rules remain authoritative. */
final class CPP_Wrapper_Transport
{
	public static function payload(canonical_type_use $type): ?canonical_type_use
	{
		$canonical = Type_Queries::canonical($type);
		if (!($canonical instanceof applied_template_type)) { return null; }
		$application = object_cast($canonical, applied_template_type::class);
		$definition = $canonical->definition();
		if ($definition instanceof nullable_type_definition) {
			return object_cast($definition, nullable_type_definition::class)->payload_type($application);
		}
		if ($definition instanceof result_type_definition) {
			return object_cast($definition, result_type_definition::class)->payload_type($application);
		}
		return null;
	}

	/** Convert a provider's native outer wrapper to/from its composed stored payloads. */
	public static function adapt(canonical_type_use $type, string $expression, bool $compose,
		CPP_Specializations $engine, cpp_generation_context $context): string
	{
		$payload = self::payload($type);
		if ($payload === null) { return self::payload_value($type, $expression, $compose, $engine, $context); }
		$target = $compose ? CPP_Types::implementation_representation($type, $engine) : CPP_Types::representation($type);
		CPP_Types::record_dependencies($target, $context);
		$value = self::payload_value($payload, 'wrapper_source.require_value("wrapper transport")', $compose, $engine, $context);
		$body = 'if (wrapper_source.has_value().native_value()) { return ' . $target->spelling . '(' . $value . '); } ';
		$definition = Type_Queries::canonical($type)->definition();
		if ($definition instanceof nullable_type_definition) {
			$body .= 'return ' . $target->spelling . '(scpp::null);';
		}
		else {
			$family = object_cast($definition, result_type_definition::class)->result_family();
			if ($family === result_kind::structured_error) {
				$body .= 'return ' . $target->spelling . '(wrapper_source.require_error("wrapper transport"));';
			}
			else {
				if ($family === result_kind::boolean_sentinels) {
					$body .= 'if (wrapper_source.is_true().native_value()) { return ' . $target->spelling . '(scpp::true_sentinel); } ';
				}
				$body .= 'return ' . $target->spelling . '(scpp::false_sentinel);';
			}
		}
		return '([](const auto& wrapper_source) -> ' . $target->spelling . ' { ' . $body . ' }(' . $expression . '))';
	}

	/** A composed payload is an application object; its bytes hold the native implementation. */
	public static function payload_value(canonical_type_use $type, string $expression, bool $compose,
		CPP_Specializations $engine, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type, new cpp_generation_context($engine));
		CPP_Types::record_dependencies($mapping, $context);
		if (!$mapping->opaque) { return $expression; }
		$native = CPP_Types::implementation_representation($type, $engine);
		CPP_Types::record_dependencies($native, $context);
		$context->headers['memory'] = true;
		$context->headers['new'] = true;
		if (!$compose) {
			$value = '*std::launder(static_cast<const ' . $native->spelling . '*>((' . $expression . ').address()))';
			return self::payload($type) === null ? $value : self::adapt($type, $value, false, $engine, $context);
		}
		$value = self::payload($type) === null ? $expression : self::adapt($type, $expression, true, $engine, $context);
		return '([](const auto& transport_value) -> ' . $mapping->spelling . ' { return '
			. $mapping->spelling . '(' . $mapping->spelling . '::operation_result{}, +[](void* destination, const void* input) { '
			. '::new(destination) ' . $native->spelling . '(*static_cast<const ' . $native->spelling
			. '*>(input)); }, &transport_value); }(' . $value . '))';
	}
}
