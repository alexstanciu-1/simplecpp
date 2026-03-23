<?php
declare(strict_types=1);

namespace Scpp\S2S\Support;

/**
 * Centralizes the php-ast numeric kind/flag constants that the loader, IR builder, and generator interpret.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with php_generator/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class AstKind
{
	public const ENCAPS_LIST = 130;
	public const STMT_LIST = 132;
	public const IF = 133;
	public const SWITCH_LIST = 134;
	public const FUNC_DECL = 68;
	public const METHOD = 70;
	public const CLASS_ = 71;
	public const RETURN = 278;
	public const AST_ECHO = 282;
	public const NAMESPACE = 542;
	public const USE = 143;
	public const GROUP_USE = 545;
	public const CONST_DECL = 139;
	public const CONST_ELEM = 775;
	public const USE_ELEM = 543;
	public const ASSIGN = 518;
	public const VAR = 256;
	public const CONST = 257;
	public const AST_ISSET = 264;
	public const AST_UNSET = 277;
	public const BREAK = 285;
	public const CONTINUE = 286;
	public const NAME = 2048;
	public const BINARY_OP = 521;
	public const ASSIGN_REF = 519;
	public const DIM = 512;
	public const PROP = 513;
	public const STATIC_PROP = 515;
	public const METHOD_CALL = 768;
	public const CAST = 261;
	public const PROP_DECL = 774;
	public const PROP_ELEM = 1027;
	public const NEW = 527;
	public const STATIC_CALL = 770;
	public const CALL = 516;
	public const CLASS_CONST = 517;
	public const PARAM = 1536;
	public const STATIC_VAR = 532;
	public const STATIC = 16;
	public const CLASS_INTERFACE = 1;
	public const CLASS_ABSTRACT = 64;
	public const IF_ELEM = 535;
	public const SWITCH = 536;
	public const SWITCH_CASE = 537;
	public const DECLARE = 538;
	public const WHILE = 533;
	public const DO_WHILE = 534;
	public const POST_INC = 272;
	public const UNARY_OP = 269;
	public const FOR = 1024;
	public const CONDITIONAL = 771;
	public const PLUS = 1;
	public const MINUS = 2;
	public const BINARY_CONCAT = 8;
	public const BINARY_IS_EQUAL = 16;
	public const BINARY_IS_IDENTICAL = 18;
	public const BINARY_IS_SMALLER = 20;
	public const BINARY_IS_SMALLER_OR_EQUAL = 21;
	public const MUL = 3;
	public const BINARY_IS_GREATER = 256;
	public const BINARY_BOOL_OR = 258;
	public const BINARY_BOOL_AND = 259;
	public const UNARY_BOOL_NOT = 14;
	public const RETURN_REF = 4096;
	public const PARAM_REF = 8;
	public const TYPE_VOID = 14;
	public const TYPE_BOOL = 18;
	public const TYPE_LONG = 4;
	public const TYPE_DOUBLE = 5;
	public const TYPE_STRING = 6;
	public const NULLABLE_TYPE = 2050;
	public const USE_NORMAL = 1;
	public const USE_FUNCTION = 2;
	public const USE_CONST = 4;

	/**

	 * Stores collaborators and default state for this phase object.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function __construct()
	{
	}
}
