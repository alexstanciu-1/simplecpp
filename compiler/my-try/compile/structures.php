<?php
namespace scpp\compiler;

enum work_state {
	case queued;
	case running;
	case published;
	case failed;
}

/** Transient compiler work; never retained by Model. */
final class parse_work {
	/** @storage.reference model.tokens */
	public token_list $tokens;
	/** Candidate owned until publication; then shared with Model. @ownership owner */
	public ?parsed_file $result = null;
	/** Position in the transient queue, used to validate completion ownership. */
	public int $position;
	public work_state $state = work_state::queued;
}
