<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Conversion evaluation did not produce a scalar fact; this singleton is never stored in the AST. Native uint8. */
enum constant_evaluation: int
{
	case unavailable = 0;
}

/** Source boundary authorizing a conversion; operator promotion is authorized separately by its metadata row. Native uint8. */
enum conversion_context: int
{
	case explicit_cast = 1;
	case typed_destination = 2;
	case assignment = 3;
	case inference = 4;
	case compound_write_back = 5;
	case value_argument = 6;
}

/** Packed prepared conversion: low byte is the scalar operation; high bits describe carrier transport. */
final class value_conversion
{
	public const int identity /** uint16 */ = 1;
	public const int boolean_integer /** uint16 */ = 2;
	public const int integer_boolean /** uint16 */ = 3;
	public const int integer_widen /** uint16 */ = 4;
	public const int integer_proven /** uint16 */ = 5;
	public const int integer_checked /** uint16 */ = 6;
	public const int numeric_floating /** uint16 */ = 7;
	public const int floating_boolean /** uint16 */ = 8;
	public const int floating_integer_proven /** uint16 */ = 9;
	public const int floating_integer_checked /** uint16 */ = 10;

	public const int OPERATION_MASK /** uint16 */ = 0x00ff;
	public const int EXTRACT /** uint16 */ = 0x0100;
	public const int WRAP /** uint16 */ = 0x0200;
	public const int empty_carrier /** uint16 */ = 11;
	public const int sentinel_carrier /** uint16 */ = 12;

	public const int box_scalar /** uint16 */ = 13;
	public const int unbox_explicit /** uint16 */ = 14;
	public const int unbox_typed /** uint16 */ = 15;
	public const int unbox_assignment /** uint16 */ = 16;

	public const int format_bool_t /** uint16 */ = 17;
	public const int format_uint8 /** uint16 */ = 18;
	public const int format_int64 /** uint16 */ = 19;
	public const int format_float64 /** uint16 */ = 20;

	public const int empty_owner /** uint16 */ = 21;
	public const int require_owner /** uint16 */ = 22;
	public const int owner_view /** uint16 */ = 23;
	public const int require_owner_view /** uint16 */ = 24;

	public const int intersection_base /** uint16 */ = 25;
	public const int intersection_capability /** uint16 */ = 26;
	public const int guarded_intersection /** uint16 */ = 27;

	public const int export_record /** uint16 */ = 32;
	public const int export_list /** uint16 */ = 31;
	public const int decimal_text /** uint16 */ = 29;
	public const int decimal_literal /** uint16 */ = 30;
	public const int boxed_nullable_argument /** uint16 */ = 28;

	/** Inspection only; preparation and lowering use numeric operations directly. */
	public static function name(int $code /** uint16 */): string
	{
		$operation = $code & self::OPERATION_MASK;
		$name = match ($operation) {
			self::export_record => 'export_record',
			self::export_list => 'export_list',
			self::decimal_text => 'decimal_text',
			self::decimal_literal => 'decimal_literal',
			self::boxed_nullable_argument => 'boxed_nullable_argument',
			self::guarded_intersection => 'guarded_intersection',
			self::intersection_base => 'intersection_base',
			self::intersection_capability => 'intersection_capability',
			self::empty_owner => 'empty_owner',
			self::require_owner => 'require_owner',
			self::owner_view => 'owner_view',
			self::require_owner_view => 'require_owner_view',
			self::format_bool_t => 'format_bool_t',
			self::format_uint8 => 'format_uint8',
			self::format_int64 => 'format_int64',
			self::format_float64 => 'format_float64',
			self::box_scalar => 'box_scalar',
			self::unbox_explicit => 'boxed_explicit_cast',
			self::unbox_typed => 'boxed_typed_destination',
			self::unbox_assignment => 'boxed_assignment',
			self::identity => 'identity',
			self::boolean_integer => 'boolean_integer',
			self::integer_boolean => 'integer_boolean',
			self::integer_widen => 'integer_widen',
			self::integer_proven => 'integer_proven',
			self::integer_checked => 'integer_checked',
			self::numeric_floating => 'numeric_floating',
			self::floating_boolean => 'floating_boolean',
			self::floating_integer_proven => 'floating_integer_proven',
			self::floating_integer_checked => 'floating_integer_checked',
			self::empty_carrier => 'empty_carrier',
			self::sentinel_carrier => 'sentinel_carrier',
			default => throw new \LogicException('Unknown conversion operation'),
		};
		return (($code & self::EXTRACT) !== 0 ? 'extract|' : '') . $name . (($code & self::WRAP) !== 0 ? '|wrap' : '');
	}
}
