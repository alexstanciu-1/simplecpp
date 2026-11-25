<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Coordinator-owned retirement at completed success/failure gates; never acknowledges unfinished work. */
final class Generation_Cleanup
{
	/**
	 * Release this generation's history roots after all readers finish and emitted work is acknowledged.
	 * The caller owns the successful-publication gate; empty queues alone do not prove that publication succeeded.
	 * External clients may retain old sessions. Do not mutate their history or break AST/reference cycles manually.
	 */
	public static function close(compilation_session $session): void
	{
		if (!$session->is_current || ($session->scope === null)
			|| ($session->preparation_work->pending_count() !== 0)
			|| ($session->preparation_work->emission_sources() !== [])) {
			throw new \LogicException('Generation cleanup requires completed current output');
		}
		self::release_history($session);
	}

	/** After frontend publication and stopped jobs, keep current facts, watches and retry queues intact. */
	public static function failed(compilation_session $session): void
	{
		if (!$session->is_current || ($session->scope === null)) {
			throw new \LogicException('Failure cleanup requires the current published generation');
		}
		self::release_history($session);
	}

	/** Release coordinator roots only; external sessions/diagnostics retain their own lifetimes. */
	private static function release_history(compilation_session $session): void
	{
		$session->previous = null;
		$session->removed_sources = [];
	}
}
