<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Validate all file-local imports after publication, including unused imports and unchanged source files. */
final class Import_Preparation
{
	/**
	 * Function imports are the current executable slice; no aliases are published as declarations.
	 * Recheck each incremental pass so removed targets and new namespace conflicts cannot hide in cached bodies.
	 *
	 * @param ast_file_node[] $files
	 */
	public static function files(array $files /** array<string,ast_file_node> */, scope $symbols): void
	{
		$errors = [];
		foreach ($files as $file)
		{
			foreach ($file->imports as $import)
			{
				$message = self::error($file, $import, $symbols);
				if ($message !== null) {
					$errors[] = new diagnostic($file->source(), $import->byte_start(), $message);
				}
			}
		}
		if ($errors !== []) {
			throw new diagnostic_group(Declaration_Diagnostics::ordered($errors));
		}
	}

	/** Check the supported kind, absolute target and namespace-body binding conflicts. */
	private static function error(ast_file_node $file, ast_import $import, scope $symbols): ?string
	{
		if ($import->kind === import_kind::constant_only) {
			return 'Constant imports are not supported in this slice: ' . $import->target;
		}
		$key = 'func:' . $import->target;
		if ($symbols->resolve($key) === null) {
			return 'Unknown function import: ' . $import->target;
		}
		if ($file->function_key($import->local_name) !== $key) {
			return 'Conflicting function import: ' . $import->local_name;
		}
		$local_key = 'func:' . Source_Names::resolve($import->local_name, $file->scope->namespace);
		if (($local_key !== $key) && ($symbols->resolve($local_key) !== null)) {
			return 'Function import conflicts with namespace declaration: ' . $import->local_name;
		}
		return null;
	}
}
