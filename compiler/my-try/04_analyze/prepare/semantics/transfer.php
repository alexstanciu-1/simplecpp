<?php
namespace scpp\compiler;

/** Shared bounded transfer classification, independent of copying and storage representation. */
final class Transfer_Preparation
{
	/** Capture permission is separate from copyability; unknown domains stay closed. */
	public static function classify(canonical_type_use $type): capture_transfer
	{
		$canonical = Type_Queries::canonical($type);
		$definition = $canonical->definition();
		if (($definition instanceof callable_type_definition) || ($definition instanceof closure_type_definition)) {
			return capture_transfer::callable_value;
		}
		$family = $canonical->family();
		if (($family === type_family::boolean) || ($family === type_family::integer)
			|| ($family === type_family::floating) || ($family === type_family::null_sentinel)
			|| $type->matches(Language_Types::string_type(Model::$language_scope))) {
			return capture_transfer::owned_value;
		}
		if ($definition instanceof abstract_nominal_type_definition) {
			if (object_cast($definition, abstract_nominal_type_definition::class)->nominal_kind() === nominal_type_kind::enumeration) {
				return capture_transfer::owned_value;
			}
		}
		if ($canonical instanceof applied_template_type) {
			if (($definition === Model::$type_catalog->definition('vector'))
				|| ($definition === Model::$type_catalog->definition('hash'))
				|| ($definition instanceof nullable_type_definition) || ($definition instanceof result_type_definition)) {
				$application = object_cast($canonical, applied_template_type::class);
				foreach ($application->arguments() as $index => $argument) {
					// The hash key protocol already restricts mixed keys to keyable scalars.
					if (($definition === Model::$type_catalog->definition('hash')) && ($index === 1)) {
						if (Type_Queries::canonical($argument)->definition() instanceof mixed_semantics) { continue; }
					}
					if (self::classify($argument) !== capture_transfer::owned_value) { return capture_transfer::forbidden; }
				}
				return capture_transfer::owned_value;
			}
		}
		return capture_transfer::forbidden;
	}

	public static function require_payload(canonical_type_use $type): void
	{
		if (Type_Queries::canonical($type)->family() === type_family::no_value) { return; }
		if (self::classify($type) !== capture_transfer::owned_value) {
			throw new \RuntimeException('Task payload type is not transferable');
		}
	}
}
