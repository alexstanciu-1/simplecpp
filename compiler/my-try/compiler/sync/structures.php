<?php

/* Role: shared change states and combinable source/declaration change flags. */
namespace scpp\compiler;

/** Mutually exclusive lifecycle states, shared by synchronized record kinds. */
enum change_state {
	case unchanged;
	case added;
	case changed;
	case deleted;
}

// Source/declaration flags still allow combined declaration and body changes.
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
