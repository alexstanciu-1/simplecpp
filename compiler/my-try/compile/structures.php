<?php
namespace scpp\compiler;

enum work_state {
	case queued;
	case running;
	case published;
	case failed;
}

/** Transient compiler work; never retained by Model. */
final class source_work {
	/** @storage.reference module.files */
	public file $source;
	/** Existing snapshot for parse-only work, or the private scan result. */
	public ?token_list $tokens = null;
	/** Candidate owned until publication; then shared with Model. @ownership owner */
	public ?parsed_file $result = null;
	/** Position in the transient queue, used to validate completion ownership. */
	public int $position;
	public work_state $state = work_state::queued;
}

/** A per-file operation; publication policy stays with the coordinator's caller. */
enum frontend_operation {
	case scan;
	case parse;
	case synchronize;
}
