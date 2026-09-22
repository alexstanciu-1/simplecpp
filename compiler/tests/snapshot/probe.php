<?php
declare(strict_types=1);
namespace snapshot_test;
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
final class Probe {
    public static function file(string $path, int $mtime, int $size): void {
        $file = new \read_sources\Source_File();
        $file->path = $path;
        $file->mtime = $mtime;
        $file->size = $size;
        try {
            $buffer = \read_sources\Snapshot_Reader::read($file);
            echo '{"path":', json_quote($buffer->path), ',"mtime":', $buffer->mtime, ',"bytes":[';
            for ($i /** int */ = 0; $i < string_byte_len($buffer->content); ++$i) {
                if ($i !== 0) { echo ','; }
                echo string_byte_at($buffer->content, $i);
            }
            echo "]}\n";
        } catch (\Exception $error) { echo "{\"error\":true}\n"; }
    }
    public static function project(string $path): void {
        try {
            $manifest = \read_manifest\Manifest_Reader::read($path);
            $listing = \read_sources\Source_Discovery::discover($manifest);
            $texts = \read_sources\Source_Reader::read($listing);
            echo '{"entry":', $texts->entry_index, ',"contents":[';
            $first = true;
            foreach ($texts->buffers as $buffer) {
                if (!$first) { echo ','; }
                $first = false;
                echo json_quote($buffer->content);
            }
            // Returned bytes stay owned when a second result is changed.
            $second = \read_sources\Source_Reader::read($listing);
            $other = $second->buffers[0];
            $other->content = 'changed';
            $retained = $texts->buffers[0];
            echo '],"retained":', json_quote($retained->content), "}\n";
        } catch (\Exception $error) { echo "{\"error\":true}\n"; }
    }
    public static function invalid_listing(int $entry): void {
        $listing = new \read_sources\Source_Listing();
        $listing->entry_index = $entry;
        try {
            $texts = \read_sources\Source_Reader::read($listing);
            echo "{\"unexpected\":true}\n";
        } catch (\Exception $error) { echo "{\"error\":true}\n"; }
    }
    public static function partial(string $path, int $mtime): void {
        $listing = new \read_sources\Source_Listing();
        $good = new \read_sources\Source_File();
        $good->path = $path;
        $good->mtime = $mtime;
        $good->size = 3;
        $bad = new \read_sources\Source_File();
        $bad->path = $path . '.missing';
        $listing->files[] = $good;
        $listing->files[] = $bad;
        try {
            $texts = \read_sources\Source_Reader::read($listing);
            echo "{\"unexpected\":true}\n";
        } catch (\Exception $error) {
            echo '{"partial_failed":true,"input_count":', count($listing->files), ',"input_size":', $good->size, "}\n";
        }
    }
}
