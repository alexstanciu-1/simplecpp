<?php

/* Role: publish canonical scope references under the parser task lock. */
namespace scpp\compiler;

final class Scope_Publication
{
	/** Collector invokes this under the task batch lock; local variables never enter this path. */
	public static function register(scope $local_scope, scope $global, collected_name $entry): void
	{
		if ($entry->kind === collected_name_kind::function_declaration) {
			$global->register($entry);
		}
		elseif ($entry->kind === collected_name_kind::struct_declaration)
		{
			foreach ($local_scope->types_named($entry->name) as $definition) {
				if ($definition->declaration === $entry) {
					$global->register_type($definition);
					break;
				}
			}
		}
	}

	/** Export a completed local scope exactly once without copying declaration identities. */
	public static function publish(scope $local_scope, scope $global): void
	{
		if ($local_scope->published_scope() !== null) {
			throw new \LogicException('Parsed file was already published');
		}
		foreach ($local_scope->declarations() as $entry) {
			$entry->exported = true;
			$global->register($entry);
		}
		foreach ($local_scope->type_definitions() as $definition) {
			if ($definition->declaration !== null) {
				$definition->declaration->exported = true;
			}
			$global->register_type($definition);
		}
		$local_scope->set_publication($global);
	}
}
