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
			$declaration->reset_preparation();
			type_registry::prepare_literal($declaration->initializer);
			$type = $declaration->type_name === null ? $declaration->initializer->type_def()
				: type_registry::resolve($declaration->type_name);
			if ($type === null) {
				throw new diagnostic($declaration->source(), $declaration->byte_start(),
					'Unknown or unsupported global type: ' . $declaration->type_name);
			}
			$conversion = $type->prepare_conversion($declaration->initializer, $declaration->type_name !== null);
			$declaration->settle_type($type, $conversion);
		}
	}
}
