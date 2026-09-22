<?php
declare(strict_types=1);
namespace parser_test;
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
    public static function angles(string $path): void {
        $source = new \read_sources\Source_Buffer();
        $source->content = fs_read_text($path);
        $tokens = \tokenize\File_Tokenizer::tokenize($source);
        $ends = \parse\Binary_Syntax::angle_ends($tokens);
        echo '[';
        $first = true;
        foreach ($ends as $open => $close) {
            if (!$first) { echo ','; }
            $first = false;
            echo '[', $open, ',', $close, ']';
        }
        echo "]\n";
    }
    public static function arena(): void {
        $arena = new \parse\Syntax_Arena();
        $root = $arena->add(\parse\SYNTAX_BLOCK, 0, 0);
        $first = $arena->add(\parse\SYNTAX_INTEGER_LITERAL, 2, 1);
        $second = $arena->add(\parse\SYNTAX_NAME, 5, 3);
        $arena->child($root, $first);
        $arena->child($root, $second);
        $before = $arena->row($root);
        $arena->finish($root, 8);
        $copy = $arena->row($first);
        $copy->start = 99;
        $stored = $arena->row($first);
        $owner = $arena->row($root);
        echo '{"size":', $arena->size(), ',"first":', $owner->first_child,
            ',"last":', $owner->last_child, ',"next":', $stored->next_sibling,
            ',"length":', $owner->length, ',"old_length":', $before->length,
            ',"stored_start":', $stored->start, "}\n";
        try { $bad = $arena->row(0); echo "false\n"; } catch (\Exception $error) { echo "true\n"; }
        try { $arena->finish($second, 1); echo "false\n"; } catch (\Exception $error) { echo "true\n"; }
        try { $arena->child($root, $root); echo "false\n"; } catch (\Exception $error) { echo "true\n"; }
        try { $arena->child($root, $second); echo "false\n"; } catch (\Exception $error) { echo "true\n"; }
        echo '[', \parse\Binary_Syntax::from_token(\tokenize\TOKEN_PLUS), ',',
            \parse\Binary_Syntax::precedence(\parse\SYNTAX_ADDITION_EXPRESSION), ',',
            \parse\Binary_Syntax::precedence(\parse\SYNTAX_LESS_THAN_EXPRESSION), ',',
            \parse\Binary_Syntax::from_token(\tokenize\TOKEN_INTEGER_LITERAL), "]\n";
    }
}
