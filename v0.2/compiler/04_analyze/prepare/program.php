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
			throw new \LogicException('Live incremental symbols require Incremental_Preparation; full emission is separate');
		}
		foreach ($files as $file) {
			$file->begin_preparation();
			foreach ($file->declaration_nodes as $node) {
				Function_Preparation::reset($node);
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
		Function_Preparation::declarations($symbols);
		foreach ($files as $file) {
			$main = $file->main();
			if ($main !== null) {
				$main->settle_return_type(type_definition::int64);
			}
		}
		foreach ($files as $file) {
			Function_Preparation::bodies($file, $symbols);
		}
		(new Preparation())->entry($files[$entry_path], $target, $symbols);
		foreach ($files as $file) {
			$file->settle_preparation();
		}
		$entry = new prepared_entry($files[$entry_path], $target);
		return new prepared_program($entry_path, $files, $entry, $session);
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
