<?php
declare(strict_types=1);

namespace Scpp\S2S\Support;

final class AstKind
{
	public const ENCAPS_LIST = 130;
	public const STMT_LIST = 132;
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
	public const PLUS = 1;
	public const BINARY_CONCAT = 8;
	public const BINARY_BOOL_AND = 259;
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

	private function __construct()
	{
	}
}
