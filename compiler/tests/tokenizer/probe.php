<?php
declare(strict_types=1);
namespace tokenizer_test;
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
    public static function run(string $path): void {
        $source = new \read_sources\Source_Buffer();
        $source->path = $path;
        $source->content = fs_read_text($path);
        $buffer = \tokenize\File_Tokenizer::tokenize($source);
        if (!$buffer->valid) {
            echo '{"error":[', $buffer->error_start, ',', $buffer->error_length, '],"path":', json_quote($buffer->source->path), ',"rows":', count($buffer->rows), "}\n";
            return;
        }
        echo '{"rows":[';
        $first = true;
        foreach ($buffer->rows as $row) {
            if (!$first) { echo ','; }
            $first = false;
            echo '[', json_quote(\tokenize\Token_Kinds::name((int)$row->kind)), ',', $row->start, ',', $row->length, ']';
        }
        echo '],"pure":', $source->content === fs_read_text($path) ? 'true' : 'false', "}\n";
    }
    public static function batch(string $good, string $bad): void {
        $sources = new \read_sources\Source_Texts();
        $first = new \read_sources\Source_Buffer();
        $first->path = $good;
        $first->content = fs_read_text($good);
        $second = new \read_sources\Source_Buffer();
        $second->path = $bad;
        $second->content = fs_read_text($bad);
        $sources->buffers[] = $first;
        $sources->buffers[] = $second;
        $sources->entry_index = 1;
        $project = \tokenize\Tokenizer::tokenize($sources);
        $buffer = $project->buffers[0];
        echo '{"batch_valid":', $project->valid ? 'true' : 'false', ',"entry":', $project->entry_index,
            ',"buffers":', count($project->buffers), ',"first_valid":', $buffer->valid ? 'true' : 'false', "}\n";
    }
}
