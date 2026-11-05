<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Renders source-owned units and assembles their deterministic paths plus the native entry adapter. */
final class Cpp_Program
{
	/**
	 * Reuse text outside the supplied changed-source set; null requests full emission.
	 * @param bool[]|null $changed
	 */
	public static function emit(prepared_program $program, ?cpp_program_output $previous = null,
		?array $changed /** array<string,bool>|null */ = null): cpp_program_output
	{
		$program->require_prepared();
		$bodies = [];
		$headers = new Cpp_Headers($program);
		$header_files = $headers->files();
		$emitted = [];
		foreach ($program->files as $path => $file) {
			$prior_path = $previous?->source_units[$path] ?? null;
			$header_path = 'units/' . hash('sha256', (string) $path) . '.hpp';
			$header_changed = ($header_files[$header_path] ?? null) !== ($previous?->files[$header_path] ?? null);
			if (!$header_changed && ($changed !== null) && !isset($changed[$path]) && ($prior_path !== null)
				&& (($path !== $program->entry_path) === ($path !== $previous->entry_path))) {
				$bodies[$path] = $previous->files[$prior_path];
				continue;
			}
			$emitted[] = (string) $path;
			$bodies[$path] = (string) $path === $program->entry_path
				? (new Cpp_Emitter($headers))->entry_body($program->entry)
				: (new Cpp_Emitter($headers))->source_definitions($file);
		}
		$output = self::assemble($program->entry_path, $bodies);
		$native = [];
		foreach ($program->session?->units() ?? [] as $unit) {
			if ($unit->module->type === 'c++') {
				$native[] = $unit->module->root . '/' . $unit->path;
			}
		}
		$links = [];
		foreach (array_reverse($program->session?->modules ?? [], true) as $module) {
			if ($module !== $program->session->entry) {
				$links = array_merge($links, $module->artifacts);
			}
		}
		sort($native, \SORT_STRING);
		return new cpp_program_output($output->entry_path, $output->source_units, array_merge($output->files, $header_files), $native,
			array_values(array_unique($links)), $emitted);
	}

	public static function empty_unit(): string
	{
		return "// Generated empty secondary source unit; no executable statements.\n";
	}

	/**
	 * Assemble source-unit text into a deterministically ordered program bundle.
	 *
	 * @param string[] $bodies
	 */
	public static function assemble(string $entry_path, array $bodies /** array<string,string> */): cpp_program_output
	{
		$units = [];
		$files = [];
		$paths = array_map('strval', array_keys($bodies));
		sort($paths, \SORT_STRING);
		foreach ($paths as $path) {
			$unit = 'units/' . hash('sha256', $path) . '.cpp';
			$units[$path] = $unit;
			$files[$unit] = $bodies[$path];
		}
		$files['entry.cpp'] = "// Generated adapter for the explicitly selected entry.\n"
			. "int scpp_entry_body();\n\nint main()\n{\n\treturn scpp_entry_body();\n}\n";
		return new cpp_program_output($entry_path, $units, $files);
	}
}
