<?php

/* Role: publish scope references and retain deletion evidence during source replacement. */
namespace scpp\compiler;

final class Scope_Publication
{
	/** Export a completed local scope exactly once without copying declaration identities. */
	public static function publish(scope $local_scope, scope $global): void
	{
		if ($local_scope->published_scope() !== null) {
			throw new \LogicException('Parsed file was already published');
		}
		foreach ($local_scope->declarations() as $entry) {
			$global->register($entry);
		}
		foreach ($local_scope->type_definitions() as $definition) {
			$global->register_type($definition);
		}
		$local_scope->set_publication($global);
	}

	/** Superseded live rows leave the index; deleted rows and built-ins stay observable. */
	public static function replace_source(scope $global, string $path): void
	{
		$entries /** vector<collected_name> */ = [];
		foreach ($global->declarations() as $entry) {
			if (self::retain($entry, $path)) {
				$entries[] = $entry;
			}
		}
		$definitions /** vector<type_definition> */ = [];
		foreach ($global->type_definitions() as $definition)
		{
			$keep = true;
			if ($definition->declaration !== null) {
				$declaration /** collected_name */ = $definition->declaration;
				$keep = self::retain($declaration, $path);
			}
			if ($keep) {
				$definitions[] = $definition;
			}
		}
		$global->replace_declarations($entries);
		$global->replace_types($definitions);
	}

	private static function retain(collected_name $entry, string $path): bool
	{
		if ($entry->changes === \scpp\compiler\SYNC_DELETED) {
			return true;
		}
		return $entry->source_file()->path !== $path;
	}
}
