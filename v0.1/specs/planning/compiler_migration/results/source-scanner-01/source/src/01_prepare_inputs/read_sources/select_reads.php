<?php
declare(strict_types=1);

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

/** @compiler-internal Pure selection of fixed read tasks from a discovered snapshot. */
class Source_Read_Selection
{
    /**
     * @compiler-api Select immutable file/version tasks: all live files on full, otherwise missing buffers.
     * Selection reads metadata only; it does not establish that filesystem bytes are still current.
     * @return list<source_read_task>
     */
    public static function select(Source_Set $sources, bool $full_rebuild): array /** vector<source_read_task> */
    {
        $tasks /** vector<source_read_task> */ = [];
        foreach ($sources->files as $file)
        {
            if ($file->change_state === file_change::deleted) {
                continue;
            }
            if ($file->change_state === file_change::moved) {
                throw new \Exception("Unsupported source change: moved");
            }

            // Source validity is independent of unfinished semantic/backend work.
            if (($full_rebuild) || ($file->buffer === null)) {
                $tasks[] = new source_read_task($file->id,
                    $file->full_path, $file->mtime, $file->size);
            }
        }
        return $tasks;
    }

}
