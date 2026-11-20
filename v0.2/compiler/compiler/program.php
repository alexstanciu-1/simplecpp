<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Coordinates one complete module graph through source-owned frontends and global preparation. */
final class Program_Frontend
{
	public function prepare(compilation_session $session, entry_target $target): prepared_program
	{
		return $this->run($session, $target, Program_Frontend::load_disk(...), []);
	}

	/**
	 * In-memory callers use the same unit ownership and compilation path without reading disk.
	 *
	 * @param source_file[] $sources
	 */
	public function prepare_sources(string $entry, array $sources /** array<string,source_file> */,
		entry_target $target): prepared_program
	{
		$session = Program_Loader::memory($entry, $sources);
		return $this->run($session, $target, Program_Frontend::load_snapshot(...), $sources);
	}

	/** Disk units already carry their root and relative path. @param source_file[] $snapshots */
	private static function load_disk(source_unit $unit, array $snapshots /** array<string,source_file> */): source_file
	{
		return Program_Loader::load($unit);
	}

	/** Preserve the caller-supplied snapshot identity and its source label. @param source_file[] $snapshots */
	private static function load_snapshot(source_unit $unit, array $snapshots /** array<string,source_file> */): source_file
	{
		return $snapshots[$unit->path];
	}

	/**
	 * Rebuild all source results with the selected loader and its explicit snapshot input.
	 *
	 * @param \Closure(source_unit, array<string,source_file>):source_file $load
	 * @param source_file[] $snapshots
	 */
	private function run(compilation_session $session, entry_target $target, \Closure $load,
		array $snapshots /** array<string,source_file> */): prepared_program
	{
		if ($session->scope !== null) {
			throw new \LogicException('Incremental frontend publications cannot be prepared in place yet');
		}
		$units = array_filter($session->units(), static fn(source_unit $unit): bool => $unit->module->type === 'default');
		foreach ($units as $unit) {
			$unit->ast = null;
		}
		$frontend = new Source_Frontend();
		$publication = new Symbol_Publication();
		try
		{
			foreach ($units as $unit) {
				$unit->ast = $frontend->collect($load($unit, $snapshots), $publication, $session->type_availability);
			}
			$files = $session->files();
			return Program_Preparation::prepare($session->entry_key(), $files, $target,
				$publication->publish($files), $session);
		}
		catch (\Throwable $error) {
			foreach ($units as $unit) {
				$unit->ast = null;
			}
			throw $error;
		}
	}
}
