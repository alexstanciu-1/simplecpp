<?php
declare(strict_types=1);

namespace Scpp\S2S\Support;

final class AstKind
{
	public const STMT_LIST = 132;
	public const FUNC_DECL = 68;
	public const METHOD = 70;
	public const CLASS_ = 71;
	public const RETURN = 278;
	public const NAMESPACE = 542;
	public const ASSIGN = 518;
	public const VAR = 256;
	public const CONST = 257;
	public const NAME = 2048;
	public const BINARY_OP = 521;
	public const NEW = 527;
	public const STATIC_CALL = 770;
	public const CALL = 516;
	public const PARAM = 1536;
	public const STATIC_VAR = 532;
	public const STATIC = 16;
	public const PLUS = 1;
	public const RETURN_REF = 4096;
	public const PARAM_REF = 8;
	public const TYPE_VOID = 14;
	public const TYPE_BOOL = 18;
	public const TYPE_LONG = 4;
	public const TYPE_DOUBLE = 5;
	public const TYPE_STRING = 6;

	private function __construct()
	{
	}
}
