<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Renders source-owned units and assembles their deterministic paths plus the native entry adapter.
 */
final class Cpp_Program
{
	/** Canonical output ordering never changes the retained source or syntax order. */
	public static function emit(prepared_program $program): cpp_program_output
	{
		$bodies = [];
		foreach ($program->files as $path => $file) {
			$bodies[$path] = (string) $path === $program->entry_path
				? (new Cpp_Emitter())->entry_body($program->entry)
				: (new Cpp_Emitter())->functions($program->functions[$path]);
		}
		return self::assemble($program->entry_path, $bodies);
	}

	public static function empty_unit(): string
	{
		return "// Generated empty secondary source unit; no executable statements.\n";
	}

	/** Assemble source-unit text into a deterministically ordered program bundle. */
	public static function assemble(string $entry_path, array $bodies /** array<string,string> */): cpp_program_output
	{
		$units = [];
		$files = [];
		$paths = array_map('strval', array_keys($bodies));
		sort($paths, SORT_STRING);
		foreach ($paths as $path)
		{
			$unit = 'units/' . hash('sha256', $path) . '.cpp';
			$units[$path] = $unit;
			$files[$unit] = $bodies[$path];
		}
		$files['entry.cpp'] = "// Generated adapter for the explicitly selected entry.\n"
			. "int scpp_entry_body();\n\nint main()\n{\n\treturn scpp_entry_body();\n}\n";
		return new cpp_program_output($entry_path, $units, $files);
	}
}
