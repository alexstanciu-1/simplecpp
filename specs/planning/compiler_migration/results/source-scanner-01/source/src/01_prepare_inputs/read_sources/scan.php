<?php
declare(strict_types=1);

/*
 * Role: Observe one fixed source selection: directory discovery or explicit files.
 * Used by: Source_Discovery::run()
 * Call map:
 *   Source_Scanner::scan()
 *     -> [action] inspect entries and return Source_Scan_Result
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

/**
 * @compiler-internal Discovery worker implementation; invoked by Source_Discovery, not other stages.
 * A scan task is an independently executable source selection; it owns no global IDs.
 */
class Source_Scanner
{
    /**
     * @compiler-internal Observe a fixed file selection or a sorted directory listing, retaining the exact task.
     * No shared writes or recursive scheduling; filesystem failures throw.
     */
    public static function scan(source_scan_task $task): Source_Scan_Result
    {
        // A worker can be reused across refreshes. Discard cached metadata.
        $directory = Source_Path_Syntax::join($task->root, $task->relative_directory);

        // Recheck a queued directory before scanning it, too.
        if (fs_is_link($directory)) {
            throw new \Exception("Symbolic links inside source roots are unsupported: " . $directory);
        }
        $entries /** vector<string> */ = [];
        $explicit /** bool */ = take_nullable($entries, $task->file_names);
        if (!$explicit) {
            if (!take_false($entries, fs_scan($directory))) {
                throw new \Exception("Cannot scan source directory: " . $directory);
            }
        }
        $files /** vector<scanned_source_file> */ = [];
        $directories /** vector<string> */ = [];
        foreach ($entries as $name)
        {
            if (($name === ".") || ($name === "..")) {
                continue;
            }
            $relative = $task->relative_directory === "" ? $name
                : Source_Path_Syntax::join($task->relative_directory, $name);
            $path = Source_Path_Syntax::join($task->root, $relative);
            if (fs_is_link($path)) {
                throw new \Exception("Symbolic links inside source roots are unsupported: " . $path);
            }
            if ((!$explicit) && fs_is_dir($path)) {
                $directories[] = $relative;
            }
            else if (($explicit) || Source_Path_Syntax::is_source($path))
            {
                if (!fs_is_file($path)) {
                    throw new \Exception("Source path is not a regular file: " . $path);
                }
                $mtime /** int */ = 0;
                $size /** int */ = 0;
                $has_mtime /** bool */ = take_false($mtime, fs_mtime($path));
                $has_size /** bool */ = take_false($size, fs_size($path));
                if (!$has_mtime || !$has_size) {
                    throw new \Exception("Cannot read source metadata: " . $path);
                }
                $files[] = new scanned_source_file($relative, $mtime, $size);
            }
        }
        return new Source_Scan_Result($task, $files, $directories);
    }
}
