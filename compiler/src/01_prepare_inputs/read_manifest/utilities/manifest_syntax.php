<?php
declare(strict_types=1);
namespace read_manifest;
// <scpp-imports>
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

/** JSON schema validation only; does not resolve or read configured source paths. */
final class Manifest_Syntax {
    public static function parse(string $path, string $content): Project_Manifest {
        $root = json_read($content);
        if ($root->kind() !== 'object') { throw new \InvalidArgumentException('Manifest must be an object'); }
        if (!$root->has('source_folders')) { throw new \InvalidArgumentException('Missing source_folders'); }
        if (!$root->has('entry')) { throw new \InvalidArgumentException('Missing entry'); }
        for ($i /** int */ = 0; $i < $root->size(); ++$i) {
            $key = $root->key($i);
            if (($key !== 'source_folders') && ($key !== 'entry')) {
                throw new \InvalidArgumentException('Unknown manifest setting: ' . $key);
            }
        }
        $folders = $root->member('source_folders');
        if ($folders->kind() !== 'array') { throw new \InvalidArgumentException('source_folders must be a list'); }
        if ($folders->size() === 0) { throw new \InvalidArgumentException('source_folders must be nonempty'); }
        $manifest = new Project_Manifest();
        $manifest->path = $path;
        $manifest->content = $content;
        $seen /** hash<bool> */ = [];
        for ($i /** int */ = 0; $i < $folders->size(); ++$i) {
            $folder = Manifest_Syntax::path($folders->at($i));
            if (isset($seen[$folder])) { throw new \InvalidArgumentException('Duplicate source folder: ' . $folder); }
            $seen[$folder] = true;
            $manifest->source_folders[] = $folder;
        }
        $manifest->entry = Manifest_Syntax::path($root->member('entry'));
        return $manifest;
    }

    private static function path(\scpp\Json_Node $node): string {
        if ($node->kind() !== 'string') { throw new \InvalidArgumentException('Expected a string path'); }
        $value = $node->text();
        if (string_byte_len($value) === 0) { throw new \InvalidArgumentException('Expected a nonempty path'); }
        for ($i /** int */ = 0; $i < string_byte_len($value); ++$i) {
            if (string_byte_at($value, $i) === 0) { throw new \InvalidArgumentException('Path contains NUL'); }
        }
        return $value;
    }
}
