<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Coordinates whole-program admission, declaration settlement and body preparation before producing an emission handle.
 */
final class Program_Preparation
{
	/**
	 * Settle every signature before preparing any body; publish the program only after all bodies succeed.
	 *
	 * @param ast_file_node[] $files
	 */
	public static function prepare(string $entry_path, array $files /** array<string,ast_file_node> */,
		entry_target $target, ?scope $symbols = null, ?compilation_session $session = null): prepared_program
	{
		if ($session?->scope !== null) {
			throw new \LogicException('Live incremental symbols require Incremental_Preparation');
		}
		$composed = Trait_Composition::replacements($files);
		if ($composed !== []) {
			$files = array_replace($files, $composed);
			if ($session !== null) {
				$units = $session->units();
				foreach ($composed as $key => $file) $units[$key]->ast = $file;
			}
			$symbols = null;
		}
		foreach ($files as $file) {
			$file->begin_preparation();
			$file->reset_implementations();
			foreach ($file->declaration_nodes as $node) {
				if ($node instanceof ast_function_declaration) {
					Function_Preparation::reset($node);
				}
				elseif ($node instanceof ast_literal_declaration) {
					$node->reset_preparation();
				}
			}
		}
		if (!isset($files[$entry_path])) {
			throw new \RuntimeException('Selected entry is not in the source inventory: ' . $entry_path);
		}
		$symbols ??= Symbol_Publication::completed($files);
		$errors = Declaration_Diagnostics::program($files, $symbols);
		if ($errors !== []) {
			throw new diagnostic_group($errors);
		}
		foreach ($files as $path => $file) {
			$path = (string) $path;
			self::admit($file, $path === $entry_path);
		}
		$dependencies = new Dependency_Watches(mt: new mt_worker_roots());
		foreach ($files as $key => $file) {
			foreach ($file->declaration_nodes as $node) {
				if ($node instanceof ast_function_declaration) {
					$node->bind_source_key((string) $key);
				}
			}
		}
		Literal_Declaration_Preparation::declarations($symbols, $dependencies);
		self::layouts($files, $symbols, $dependencies);
		Function_Preparation::declarations($symbols, $dependencies);
		Import_Preparation::files($files, $symbols);
		foreach ($files as $file) {
			$main = $file->main();
			if ($main !== null) {
				$main->settle_return_type(type_registry::integer());
			}
		}
		if ($session !== null) {
			$session->dependency_watches = $dependencies;
		}
		foreach ($files as $file) {
			Function_Preparation::bodies($file, $symbols, $dependencies);
		}
		(new Preparation())->entry($files[$entry_path], $target, $symbols, $dependencies);
		$instances = $dependencies->called_methods();
		if ($instances !== null) $instances->prepare($symbols, $dependencies);
		$dependencies->validate_reference_calls();
		Mt_Safety::check($dependencies->mt);
		foreach ($files as $file) {
			$file->settle_preparation();
		}
		$entry = new prepared_entry($files[$entry_path], $target);
		return new prepared_program($entry_path, $files, $entry, $session, $dependencies);
	}

	/** Bind all owners before resolving field dependencies across source units. @param ast_file_node[] $files */
	public static function layouts(array $files /** array<string,ast_file_node> */, scope $symbols, Dependency_Watches $dependencies,
		?preparation_work $work = null): void
	{
		$layouts = [];
		foreach ($files as $key => $file) {
			foreach ($file->declaration_nodes as $record) {
				if ($record instanceof ast_enum_declaration) Enum_Preparation::declaration($record);
				if ($record instanceof ast_record_declaration) {
					$record->definition->bind_layout((string) $key, $symbols->entry($record->symbol_key()), $dependencies);
					$layouts[] = $record->definition;
				}
			}
		}
		$active = [];
		$pending = [];
		foreach ($layouts as $layout) {
			if ($layout->prepare_layout($symbols, $active, $work) === record_layout_state::awaiting_union_fields) $pending[] = $layout;
		}
		foreach ($pending as $layout) $layout->finish_union_fields($symbols);
	}

	/** Only the explicitly selected entry may contain executable statements. */
	public static function admit(ast_file_node $file, bool $is_entry): void
	{
		$main = $file->main();
		if ((!$is_entry) && ($main !== null)) {
			$span = $main->body()->statements[0];
			throw new diagnostic($span->source(), $span->byte_start(),
				'Top-level executable statements are only allowed in the selected entry');
		}
	}
}
