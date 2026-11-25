<?php
declare(strict_types=1);

/* Fixed source observations and read requests; discovery metadata stays in structures.php. */
namespace read_sources;
// <scpp-imports>
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

// Observed filesystem facts only. The coordinator decides identity/change state.
/** @compiler-internal Read-only discovery transfer record, scoped to one scan batch; no downstream stage API. */
class scanned_source_file
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly string $relative_path,
        public readonly int $mtime,
        public readonly int $size
    )
    {
    }
}

// Selected before execution; a reader needs no access to the shared dataset.
/**
 * @compiler-api Fixed read task selected during Source_Reader::init() and consumed by Snapshot_Reader.
 * All readonly fields identify one file/version to read; consumers do not construct replacements.
 */
class source_read_task
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $source_file_id,
        public readonly string $path,
        public readonly int $mtime,
        public readonly int $size
    )
    {
    }
}

