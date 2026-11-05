<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Settle namespace literal declarations and global storage initialization before any executable body reads them. */
final class Literal_Declaration_Preparation
{
	/** All literal declarations are validated, including unused declarations; unchanged settled AST facts can be reused. */
	public static function declarations(scope $symbols): void
	{
		foreach ($symbols->declarations as $declaration)
		{
			if (!($declaration instanceof ast_literal_declaration) || $declaration->type_ready()) {
				continue;
			}
			$declaration->reset_preparation();
			type_registry::prepare_literal($declaration->initializer);
			$type = $declaration->type_name === null ? $declaration->initializer->type_def()
				: type_registry::resolve($declaration->type_name);
			if ($type === null) {
				throw new diagnostic($declaration->source(), $declaration->byte_start(),
					'Unknown or unsupported ' . ($declaration instanceof ast_global_declaration ? 'global' : 'constant')
					. ' type: ' . $declaration->type_name);
			}
			if (($declaration instanceof ast_global_declaration) && ($type === type_string::instance())) {
				throw new diagnostic($declaration->source(), $declaration->byte_start(), 'String globals are deferred');
			}
			$conversion = $type->prepare_conversion($declaration->initializer, $declaration->type_name !== null ? conversion_context::typed_destination : conversion_context::inference);
			$declaration->settle_type($type, $conversion);
		}
	}
}
