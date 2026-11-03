<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Incremental frontend only: read/tokenize/parse changed sources, then publish the current symbol index.
 * Example: collect($candidate, $previous) shares unchanged AST roots and never runs preparation or backend work.
 */
final class Incremental_Frontend
{
	/** A null previous generation is the normal first/full frontend build: every source is added. */
	public function collect(compilation_session $candidate, ?compilation_session $previous = null): compilation_session
	{
		if (($candidate === $previous) || ($candidate->scope !== null) || ($candidate->previous !== null)) {
			throw new \LogicException('Incremental frontend requires a fresh candidate session');
		}
		$old = [];
		foreach ($previous?->entry->sources ?? [] as $unit) {
			$old[$unit->module->root . '/' . $unit->path] = $unit;
		}
		$units = $candidate->entry->sources;
		foreach ($units as $unit) {
			if (($unit->ast !== null) || (($old[$unit->module->root . '/' . $unit->path] ?? null) === $unit)) {
				throw new \LogicException('Candidate source units must be fresh; reuse occurs at the AST root');
			}
		}
		if (($previous !== null) && (($previous->scope === null) || !$previous->is_current)) {
			throw new \LogicException('Previous frontend generation must be complete and current');
		}
		$candidate->previous = $previous;
		$candidate->removed_sources = [];
		clearstatcache();
		$frontend = new Source_Frontend();
		try
		{
			foreach ($units as $unit)
			{
				$path = $unit->module->root . '/' . $unit->path;
				$stat = self::stat($path);
				$unit->mtime = $stat['mtime'];
				$unit->size = $stat['size'];
				$prior = $old[$path] ?? null;
				unset($old[$path]);
				$unit->status = $prior === null ? change_status::added : change_status::changed;
				if (($prior !== null) && ($prior->ast !== null) && ($prior->mtime === $unit->mtime)
					&& ($prior->size === $unit->size) && ($prior->flavor === $unit->flavor)
					&& ($prior->module->name === $unit->module->name) && ($prior->module->type === $unit->module->type)) {
					$unit->status = change_status::none;
					$unit->ast = $prior->ast;
				}
				else {
					$unit->ast = $frontend->collect(Program_Loader::load($unit));
				}
			}
			foreach ($old as $prior)
			{
				$removed = new source_unit($prior->module, $prior->path, $prior->flavor);
				$removed->status = change_status::removed;
				$removed->mtime = $prior->mtime;
				$removed->size = $prior->size;
				$removed->ast = $prior->ast;
				$candidate->removed_sources[] = $removed;
			}
			clearstatcache();
			$files = [];
			foreach ($units as $unit) {
				$stat = self::stat($unit->module->root . '/' . $unit->path);
				if (($stat['mtime'] !== $unit->mtime) || ($stat['size'] !== $unit->size)) {
					throw new \RuntimeException('Source changed during frontend collection: ' . $unit->path);
				}
				$files[$candidate->source_key($unit)] = $unit->ast;
			}
			if ($previous !== null) { $candidate->function_dependencies = $previous->function_dependencies; }
			$candidate->scope = Symbol_Publication::completed($files, $previous?->scope, $candidate->function_dependencies);
			if ($previous !== null) { $previous->is_current = false; }
			return $candidate;
		}
		catch (\Throwable $error)
		{
			foreach ($units as $unit) {
				$unit->ast = null;
			}
			$candidate->scope = null;
			$candidate->removed_sources = [];
			throw $error;
		}
	}

	private static function stat(string $path): array /** array{mtime:int64,size:uint64} */
	{
		$stat = @stat($path);
		if ($stat === false) {
			throw new \RuntimeException('Cannot stat frontend source: ' . $path);
		}
		return ['mtime' => $stat['mtime'], 'size' => $stat['size']];
	}
}
