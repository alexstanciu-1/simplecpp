<?php

/* Read-only work validity and source-change queries; never schedule or prepare. */
namespace scpp\compiler;

final class Preparation_Status
{
	/** Source changes remain independent of work state until analysis consumes them. */
	public static function source_changed(preparation_owner $owner): bool
	{
		if ($owner instanceof declaration_work) {
			return object_cast($owner, declaration_work::class)->required_declaration()->change_status !== change_state::unchanged;
		}
		if ($owner instanceof body_work) {
			return object_cast($owner, body_work::class)->executable_body()->syntax_changed;
		}
		throw new \LogicException('Unsupported preparation work role');
	}

	/** Reject stale work at process entry; recursive syntax dispatch needs no per-node flag. */
	public static function require_active(preparation_owner $owner): void
	{
		if ($owner->source->deleted || ($owner->change_status === change_state::deleted)) {
			throw new \LogicException('Deleted work cannot be prepared or generated');
		}
		$declaration = $owner->declaration();
		if ($declaration !== null) {
			if ($declaration->change_status === change_state::deleted) {
				throw new \LogicException('Deleted declaration cannot be prepared or generated');
			}
		}
		if (!$owner->source->parse_complete) {
			throw new \LogicException('Semantic work requires a completed parse');
		}
	}

	/** Completed facts must match current source, even before analysis selects new work. */
	public static function complete(preparation_owner $owner): bool
	{
		self::require_active($owner);
		if (($owner->state !== preparation_state::ready) || ($owner->change_status !== change_state::unchanged) || $owner->failed) {
			return false;
		}
		return !self::source_changed($owner);
	}
}
