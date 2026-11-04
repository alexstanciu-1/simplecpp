<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Settle global storage contracts and literal initialization before any executable body reads them. */
final class Global_Preparation
{
	/** All globals are validated, including unused declarations; unchanged settled AST facts can be reused. */
	public static function declarations(scope $symbols): void
	{
		foreach ($symbols->declarations as $declaration)
		{
			if (!($declaration instanceof ast_global_declaration) || $declaration->type_ready()) {
				continue;
			}
			if (!in_array($declaration->type_name, [null, 'int', 'int64'], true)) {
				throw new diagnostic($declaration->source(), $declaration->byte_start(),
					'Unknown or unsupported global type: ' . $declaration->type_name);
			}
			$declaration->initializer->settle_integer(Integer_Preparation::value($declaration->initializer));
			$declaration->settle_type(type_definition::int64);
		}
	}
}
