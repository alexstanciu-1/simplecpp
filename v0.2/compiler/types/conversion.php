<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Prepared boundary operation, not a value/type copy; generated destination handlers select and lower it. */
enum value_conversion: int /** uint8 */
{
	case identity = 1;
	case boolean_integer = 2;
	case integer_boolean = 3;
	case integer_widen = 4;
	case integer_proven = 5;
	case integer_checked = 6;
}
