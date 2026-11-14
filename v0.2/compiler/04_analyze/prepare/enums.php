<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One declaration-local pass validates literal cases; temporary value membership is discarded afterwards. */
final class Enum_Preparation
{
	/** Validate values once and select the smallest representation covering both extrema. */
	public static function declaration(ast_enum_declaration $enum): void
	{
		if ($enum->definition->prepared()) return;
		$written = $enum->backing;
		$backing = $written === null ? type_int64::instance() : Type_Resolution::resolve($written);
		if ($backing === null || !defined($backing::class . '::MINIMUM') || !defined($backing::class . '::MAXIMUM')) {
			throw new diagnostic($enum->source(), $enum->byte_start(), 'Enum backing requires an available integer type');
		}
		if ($enum->cases === []) throw new diagnostic($enum->source(), $enum->byte_start(), 'Enum requires at least one case');
		$seen = [];
		$minimum = PHP_INT_MAX;
		$maximum = PHP_INT_MIN;
		foreach ($enum->cases as $case) {
			if ($case->duplicate_element() !== null) {
				throw new diagnostic($case->source(), $case->duplicate_element()->byte_start(), 'Duplicate enum case: ' . $case->short_name);
			}
			$case->initializer->reset_preparation();
			type_int64::instance()->prepare_literal($case->initializer);
			$value = $case->initializer->constant();
			if ($value < $backing::MINIMUM || $value > $backing::MAXIMUM) {
				throw new diagnostic($case->source(), $case->byte_start(), 'Enum value is outside ' . $backing->name . ' range');
			}
			if (isset($seen[$value])) throw new diagnostic($case->source(), $case->byte_start(), 'Duplicate enum value: ' . $value);
			$seen[$value] = true;
			$minimum = min($minimum, $value);
			$maximum = max($maximum, $value);
		}
		$storage = $backing;
		if ($written === null || $written->name === 'int') {
			// Select from value extrema, never case count. All source values fit the int64 literal contract.
			$names = $minimum < 0 ? ['int8', 'int16', 'int32', 'int64'] : ['uint8', 'uint16', 'uint32', 'int64'];
			foreach ($names as $name) {
				$type = type_registry::resolve($name);
				if ($minimum >= $type::MINIMUM && $maximum <= $type::MAXIMUM) {
					$storage = $type;
					break;
				}
			}
		}
		$enum->definition->settle($backing, $storage);
	}
}
