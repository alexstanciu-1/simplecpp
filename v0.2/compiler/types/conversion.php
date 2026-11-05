<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Source boundary authorizing a conversion; operator promotion is authorized separately by its metadata row. Native uint8. */
enum conversion_context: int
{
	case explicit_cast = 1;
	case typed_destination = 2;
	case assignment = 3;
	case inference = 4;
}

/** Prepared boundary operation, not a value/type copy; generated destination handlers select and lower it. */
enum value_conversion: int /** uint8 */
{
	case identity = 1;
	case boolean_integer = 2;
	case integer_boolean = 3;
	case integer_widen = 4;
	case integer_proven = 5;
	case integer_checked = 6;
	case numeric_floating = 7;
	case floating_boolean = 8;
	case floating_integer_proven = 9;
	case floating_integer_checked = 10;
}
