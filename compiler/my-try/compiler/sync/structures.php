<?php

/* Role: shared source and declaration change flags. */
namespace scpp\compiler;

const SYNC_ADDED = 1;
const SYNC_CHANGED = 2;
const SYNC_BODY_CHANGED = 4;
const SYNC_DELETED = 8;

/** Invocation-local comparison evidence, never retained as declaration identity. */
final class declaration_comparison {
	/** @reference.weak collected_file.entries */
	public collected_name $entry;
	public string $key;
	public string $declaration;
	public string $body;
	public bool $paired = false;
}

/** Presence is independent of change flags; a revision avoids a preliminary clearing pass. */
final class sync_presence {
	public int $revision = 0;
}
