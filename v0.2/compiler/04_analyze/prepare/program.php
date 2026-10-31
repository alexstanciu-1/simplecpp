<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Coordinates whole-program admission, declaration settlement and body preparation before producing an emission handle.
 */
final class Program_Preparation
{
	/** Settle every signature before preparing any body; publish the program only after all bodies succeed. */
	public static function prepare(string $entry_path, array $files /** array<string,ast_file_node> */,
		entry_target $target, ?global_symbols $symbols = null): prepared_program
	{
		if (!isset($files[$entry_path])) {
			throw new \RuntimeException('Selected entry is not in the source inventory: ' . $entry_path);
		}
		foreach ($files as $path => $file)
		{
			$path = (string) $path;
			self::admit($file, $path === $entry_path);
		}
		$callables = Function_Preparation::declarations($symbols ?? Symbol_Publication::completed($files));
		foreach ($files as $file) {
			$main = $file->main();
			if ($main !== null) {
				Function_Preparation::reset($main);
				$main->settle_return_type(type_definition::int64);
			}
		}
		$functions = [];
		foreach ($files as $path => $file) {
			$functions[$path] = Function_Preparation::bodies($file, $callables);
		}
		$entry = (new Preparation())->entry($files[$entry_path], $target, $callables, $functions[$entry_path]);
		return new prepared_program($entry_path, $files, $entry, $functions);
	}

	/** Only the explicitly selected entry may contain executable statements. */
	public static function admit(ast_file_node $file, bool $is_entry): void
	{
		$main = $file->main();
		if (!$is_entry && ($main !== null)) {
			$span = $main->body()->statements[0];
			throw new diagnostic($span->source(), $span->byte_start(),
				'Top-level executable statements are only allowed in the selected entry');
		}
	}
}
