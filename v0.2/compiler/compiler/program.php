<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Coordinates one complete module graph through source-owned frontends and global preparation. */
final class Program_Frontend
{
	public function prepare(compilation_session $session, entry_target $target): prepared_program
	{
		return $this->run($session, $target, static fn(source_unit $unit): source_file => Program_Loader::load($unit));
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
		return $this->run($session, $target, static fn(source_unit $unit): source_file => $sources[$unit->path]);
	}

	/** Rebuild all source results; this establishes no incremental reuse or module visibility policy. */
	private function run(compilation_session $session, entry_target $target, \Closure $load): prepared_program
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
				$unit->ast = $frontend->collect($load($unit), $publication, $session->type_availability);
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
