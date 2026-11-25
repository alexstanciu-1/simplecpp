<?php
declare(strict_types=1);

/*
 * Role: Source identities, metadata and fixed scan/read tasks.
 * Used by: Source_Discovery; Source_Reader; scan/snapshot workers and joins
 * Flow: discovery -> selected tasks -> worker results
 */

namespace read_sources;
// <scpp-imports>
use function scpp\fs_is_link as fs_is_link;
use function scpp\fs_is_dir as fs_is_dir;
use function scpp\fs_is_file as fs_is_file;
use function scpp\fs_size as fs_size;
use function scpp\fs_mtime as fs_mtime;
use function scpp\fs_scan as fs_scan;
use function scpp\json_quote as json_quote;
use function scpp\string_byte_from_int as string_byte_from_int;
use function scpp\enum_name as enum_name;
use function scpp\lock_empty as lock_empty;
use function scpp\lock_try as lock_try;
use function scpp\lock_release as lock_release;
use function scpp\lock_transfer as lock_transfer;
use function scpp\process_spawn as process_spawn;
use function scpp\process_poll as process_poll;
use function scpp\process_output as process_output;
use function scpp\process_stop as process_stop;
use function scpp\process_close as process_close;
use function scpp\sequence_map as sequence_map;
use function scpp\sequence_filter as sequence_filter;
use function scpp\keyed_map as keyed_map;
use function scpp\keyed_filter as keyed_filter;
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>

// Own file discovery, source identities, versioned snapshots, and source buffers.

/**
 * @compiler-api Readable source directory context: configured path, canonical resolved_path, file_names selection.
 * Source_Discovery fills it before handoff; null file_names includes recursive descendants.
 */
class source_folder
{
    public string $path = "";
    public string $resolved_path = "";

    /** @var list<string>|null null scans recursively; a list selects only these immediate files. */
    public ?array $file_names /** vector<string> */ = null;
}

// Immutable worker input; no reference to shared source tables or indexes.
/** @compiler-internal Read-only discovery transfer record, scoped to one scan batch; no downstream stage API. */
class source_scan_task
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $index,
        public readonly int $top_folder_index,
        public readonly string $root,
        public readonly string $relative_directory,

        /** @var list<string>|null Fixed immediate file selection, or null for directory discovery. */
        public readonly ?array $file_names /** vector<string> */ = null
    )
    {
    }
}

/**
 * @compiler-api File observation classification; moved is reserved, currently delete plus add.
 * A change value does not itself specify downstream work.
 */
enum file_change: int {
    case unchanged = 0;
    case added = 1;
    case changed = 2;
    case deleted = 3;
    case moved = 4; // Reserved; discovery currently reports deletion + addition.
}

// Preserve the eventual uint32 file identity limit on PHP's wider integers.
const MAX_SOURCE_FILE_ID = 0xffffffff;

/**
 * @compiler-api Readable metadata row owned by Source_Reader; all declared fields describe this
 * source snapshot. id is logical project identity, top_folder_index is snapshot-local
 * (-1 for deleted). buffer is shared or null; change_state and needs_recompile differ.
 * Clone before owner-authorized changes; consumers must not edit a retained row.
 */
class source_file
{
    public int $id = 0;

    // Snapshot-local folder position; -1 when the file has been deleted.
    public int $top_folder_index = 0;

    // Keep the canonical path for identity and origin tracking after removal.
    public string $full_path = "";
    public string $relative_path = "";
    public int $mtime = 0;
    public int $size = 0;

    // Immutable snapshot shared with tokens; null means bytes need reading.
    public ?Source_Buffer $buffer = null;

    // File-change classification is independent of unfinished stage work.
    public file_change $change_state = file_change::unchanged;
    public bool $needs_recompile = true;
    /** Explicit shallow record copy; immutable buffers retain their identity. */
    public function copy(): source_file
    {
        $out = new source_file();
        $out->id = $this->id;
        $out->top_folder_index = $this->top_folder_index;
        $out->full_path = $this->full_path;
        $out->relative_path = $this->relative_path;
        $out->mtime = $this->mtime;
        $out->size = $this->size;
        $out->buffer = $this->buffer;
        $out->change_state = $this->change_state;
        $out->needs_recompile = $this->needs_recompile;
        return $out;
    }

}
