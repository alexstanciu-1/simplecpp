<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Validate all file-local imports after publication, including unused imports and unchanged source files. */
final class Import_Preparation
{
	/**
	 * Function/constant imports share validation; no aliases are published as declarations.
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
		$found = false;
		$kinds = match ($import->kind) {
			import_kind::function_only => ['func'],
			import_kind::constant_only => ['const'],
			import_kind::symbol => ['func', 'const'],
		};
		foreach ($kinds as $kind) {
			$key = $kind . ':' . $import->target;
			if ($symbols->resolve($key) === null && ($kind !== 'func' || runtime_functions::free($key, $file->source()->flavor) === null)) {
				continue;
			}
			$found = true;
			$label = $kind === 'func' ? 'function' : 'constant';
			$resolved = $kind === 'func' ? $file->function_key($import->local_name) : $file->constant_key($import->local_name);
			if ($resolved !== $key) {
				return 'Conflicting ' . $label . ' import: ' . $import->local_name;
			}
			$local_key = $kind . ':' . Source_Names::resolve($import->local_name, $file->scope->namespace);
			if (($local_key !== $key) && ($symbols->resolve($local_key) !== null)) {
				return ucfirst($label) . ' import conflicts with namespace declaration: ' . $import->local_name;
			}
		}
		return $found ? null : 'Unknown ' . ($import->kind === import_kind::constant_only ? 'constant' : 'function') . ' import: ' . $import->target;
	}
}
