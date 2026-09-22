<?php
declare(strict_types=1);
namespace manifest_test;
// <scpp-imports>
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
    public static function read(string $path): void {
        try {
            $result = \read_manifest\Manifest_Reader::read($path);
            echo '{"status":"ok","single":', $result->single_source ? 'true' : 'false';
            echo ',"entry":', json_quote($result->entry), ',"directory":', json_quote($result->directory);
            echo ',"path":', json_quote($result->path), ',"content":', json_quote($result->content);
            echo ',"folders":[';
            $first /** bool */ = true;
            foreach ($result->source_folders as $folder) {
                if (!$first) { echo ','; }
                echo json_quote($folder);
                $first = false;
            }
            echo '],"files":[';
            $first = true;
            foreach ($result->source_files as $file) {
                if (!$first) { echo ','; }
                echo json_quote($file);
                $first = false;
            }
            echo "]}\n";
        } catch (\RuntimeException $error) {
            echo "{\"status\":\"io\"}\n";
        } catch (\Exception $error) {
            echo "{\"status\":\"invalid\"}\n";
        }
    }
    public static function retention(string $path): void {
        $first = \read_manifest\Manifest_Reader::read($path);
        $second = \read_manifest\Manifest_Reader::read($path);
        $second->entry = 'changed';
        echo '{"retained":', json_quote($first->entry), "}\n";
    }
    public static function json_view(): void {
        $root = json_read('{"0":null,"01":false,"text":"old","0":true}');
        $retained = $root->member('text');
        echo '{"key":', json_quote($root->key(0)), ',"key2":', json_quote($root->key(1));
        echo ',"kind":', json_quote($root->member('0')->kind()), ',"count":', $root->size();
        echo ',"missing":', $root->has('absent') ? 'false' : 'true';
        $root = json_read('[]');
        echo ',"retained":', json_quote($retained->text()), "}\n";
        try { $wrong = $root->at(0); }
        catch (\RuntimeException $error) { echo "{\"adapter_error\":\"index\"}\n"; }
        try { $wrong_text = $root->text(); }
        catch (\RuntimeException $error) { echo "{\"adapter_error\":\"kind\"}\n"; }
        $object = json_read('{}');
        try { $wrong = $object->member('absent'); }
        catch (\RuntimeException $error) { echo "{\"adapter_error\":\"missing\"}\n"; }
    }
}
