<?php
namespace scpp\compiler;

/** Hashes authorized by the committed manifest or an interrupted publication journal. */
final class output_file_ownership
{
	public string $before = '';
	public string $after = '';
}

enum work_state {
	case queued;
	case running;
	case published;
	case failed;
}

/** Transient compiler work; never retained by Model. */
final class source_work
{
	/** Prior published parse. */
	public ?parsed_file $previous = null;
	/** Existing snapshot for parse-only work, or the private scan result. */
	public ?token_list $tokens = null;
	/** Candidate owned until publication; then shared with Model. @ownership owner */
	public ?parsed_file $result = null;
	public work_state $state = work_state::queued;
	/** A file failure is reported after all independent parsing work has joined. */
	public string $error = '';

	/** Own the candidate snapshot, its publication destination and queue position before dispatch. */
	public function __construct(public file $source, public source_record $record, public int $position) {}
}

/** A per-file operation; publication policy stays with the coordinator's caller. */
enum frontend_operation {
	case scan;
	case parse;
}
