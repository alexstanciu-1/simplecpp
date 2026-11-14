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
		if ($backing !== type_string::instance() && ($backing === null || !defined($backing::class . '::MINIMUM') || !defined($backing::class . '::MAXIMUM'))) {
			throw new diagnostic($enum->source(), $enum->byte_start(), 'Enum backing requires an available integer type or string');
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
			if ($backing === type_string::instance()) {
				if (!($case->initializer instanceof ast_string_expression)) {
					throw new diagnostic($case->source(), $case->byte_start(), 'String-backed enum requires string literals');
				}
				$backing->prepare_literal($case->initializer);
				$key = "\0" . $case->initializer->bytes();
				$value = count($seen);
			} else {
				if (!($case->initializer instanceof ast_integer_expression)) {
					throw new diagnostic($case->source(), $case->byte_start(), 'Integer-backed enum requires integer literals');
				}
				type_int64::instance()->prepare_literal($case->initializer);
				$value = $case->initializer->constant();
				if ($value < $backing::MINIMUM || $value > $backing::MAXIMUM) {
					throw new diagnostic($case->source(), $case->byte_start(), 'Enum value is outside ' . $backing->name . ' range');
				}
				$key = $value;
			}
			if (isset($seen[$key])) throw new diagnostic($case->source(), $case->byte_start(), 'Duplicate enum value: ' . ($backing === type_string::instance() ? $case->short_name : $value));
			$seen[$key] = true;
			$minimum = min($minimum, $value);
			$maximum = max($maximum, $value);
		}
		$storage = $backing;
		if ($backing === type_string::instance() || $written === null || $written->name === 'int') {
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

	/** Observe the enum's stable identity even for a missing case or removed declaration. */
	public static function reference(ast_name_expression $syntax, scope $symbols, dependency_watcher $watcher, ?Dependency_Watches $dependencies): bool
	{
		if ($syntax->source()->flavor === flavor::php && !$syntax->constant_only) return false;
		$name = $syntax->name;
		$separator = strrpos($name, '::');
		$width = 2;
		if ($separator === false && $syntax->source()->flavor !== flavor::php) {
			$separator = strrpos($name, '\\');
			$width = 1;
		}
		if ($separator === false) return false;
		$symbol = $symbols->entry($syntax->root()->type_key(substr($name, 0, $separator)));
		$dependencies?->watch($symbol, $watcher);
		$enum = $symbol->declaration();
		if (!($enum instanceof ast_enum_declaration)) return false;
		$case = $enum->cases[substr($name, $separator + $width)] ?? null;
		if ($case === null) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown enum case: ' . $name);
		$syntax->settle_enum_case($case);
		return true;
	}
}
