<?php

/* Semantic lexical lifetime checks, independent of lookup scopes and backend storage. */
namespace scpp\compiler;

final class Storage_Lifetimes
{
	/** A region ends before its parent, but not before an unrelated sibling region. */
	public static function outlives(storage_lifetime $source, storage_lifetime $destination): bool
	{
		$current = $destination->region;
		$order = $destination->order;
		for (;;) {
			if ($current === $source->region) { return $source->order <= $order; }
			if ($current->parent === null) { return false; }
			$order = $current->entered_at;
			$current = $current->parent;
		}
	}

	/** Projections/aliases observe their root; owned captures are bounded by this invocation. */
	public static function place(prepared_place $place, preparation_context $context): storage_lifetime
	{
		$storage = $place->root_storage();
		if ($storage instanceof prepared_capture) {
			$capture = object_cast($storage, prepared_capture::class);
			if ($capture->borrow !== null) { return $capture->borrow->lifetime; }
		}
		if (!($storage instanceof prepared_source_storage)) {
			throw new \RuntimeException('Property storage has no invocation-local lifetime');
		}
		return $context->locals->lifetime_of(object_cast($storage, prepared_source_storage::class));
	}

	/** Guarantees justify invocation-local use, never writes into external borrowed storage. */
	public static function require_destination(Storage $origins /** Storage<storage_lifetime> */,
		storage_lifetime $destination, preparation_context $context): void
	{
		$root = $destination->region;
		while ($root->parent !== null) { $root = $root->parent; }
		foreach ($origins as $origin) {
			if (self::outlives($origin, $destination)) { continue; }
			$guaranteed = false;
			if ($root === $context->invocation_lifetime) {
				$guarantees /** Storage<storage_lifetime> */ = $context->borrowed_lifetimes;
				foreach ($guarantees as $guarantee) {
					if (self::outlives($origin, $guarantee)) { $guaranteed = true; break; }
				}
			}
			if (!$guaranteed) { throw new \RuntimeException('Borrowing closure destination must not outlive its captured storage'); }
		}
	}
}
