<?php
declare(strict_types=1);
namespace read_sources;
// <scpp-imports>
use function scpp\fs_read_snapshot as fs_read_snapshot;
use function scpp\fs_is_windows as fs_is_windows;
use function scpp\fs_basename as fs_basename;
use function scpp\fs_dirname as fs_dirname;
use function scpp\fs_read_text as fs_read_text;
use function scpp\fs_require_realpath as fs_require_realpath;
use function scpp\json_read as json_read;
use function scpp\sequence_require_strings as sequence_require_strings;
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

/** Filesystem observations only; no file contents or shared publication. */
final class Source_Scanner {
    public static function file(string $path, string $relative, int $root_index): Source_File {
        if (fs_is_link($path)) { throw new \RuntimeException('Symbolic links inside source roots are unsupported: ' . $path); }
        if (!fs_is_file($path)) { throw new \RuntimeException('Source path is not a regular file: ' . $path); }
        $mtime /** int */ = 0;
        $size /** int */ = 0;
        if (!take_false($mtime, fs_mtime($path))) { throw new \RuntimeException('Cannot read source mtime: ' . $path); }
        if (!take_false($size, fs_size($path))) { throw new \RuntimeException('Cannot read source size: ' . $path); }
        $file = new Source_File();
        $file->path = $path;
        $file->relative_path = $relative;
        $file->root_index = $root_index;
        $file->mtime = $mtime;
        $file->size = $size;
        return $file;
    }
    public static function scan(Source_Listing $listing): void {
        $queue /** vector<Scan_Directory> */ = [];
        foreach ($listing->roots as $index => $root) {
            $task = new Scan_Directory();
            $task->root_index = $index;
            $queue[] = $task;
        }
        for ($head /** int */ = 0; $head < count($queue); ++$head) {
            $task = $queue[$head];
            $root = $listing->roots[$task->root_index];
            $directory = $task->relative_path === '' ? $root : Source_Paths::join($root, $task->relative_path);
            if (fs_is_link($directory)) { throw new \RuntimeException('Symbolic source directory: ' . $directory); }
            $entries /** vector<string> */ = [];
            if (!take_false($entries, fs_scan($directory))) { throw new \RuntimeException('Cannot scan source directory: ' . $directory); }
            foreach ($entries as $name) {
                $relative = $task->relative_path === '' ? $name : Source_Paths::join($task->relative_path, $name);
                $path = Source_Paths::join($root, $relative);
                if (fs_is_link($path)) { throw new \RuntimeException('Symbolic links inside source roots are unsupported: ' . $path); }
                if (fs_is_dir($path)) {
                    $child = new Scan_Directory();
                    $child->root_index = $task->root_index;
                    $child->relative_path = $relative;
                    $queue[] = $child;
                } else if (Source_Paths::is_source($path)) {
                    $listing->files[] = Source_Scanner::file($path, $relative, $task->root_index);
                }
            }
        }
    }
}
