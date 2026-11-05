<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Coordinator-owned retirement after successful output publication; never invalidates live syntax or caches. */
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
		$session->previous = null;
		$session->removed_sources = [];
	}
}
