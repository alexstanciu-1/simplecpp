<?php
// <scpp-imports>
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
$map /** hash<int, int> */ = [];
$map[12] = 0;
$map[37] = 5;
echo isset($map[12]) ? "zero-present" : "bad", ":", isset($map[91]) ? "bad" : "missing", ":", count($map), "\n";
$sum = 0;
foreach ($map as $id => $position) { $sum = $sum + $id + $position; }
echo $sum, ":", count($map), "\n";
$paths /** hash<int> */ = [];
$paths["/a"] = 12;
$paths["/b"] = 37;
$path_sum = 0;
foreach ($paths as $path => $id) {
    if (isset($paths[$path])) { $path_sum = $path_sum + $id; }
}
echo $path_sum, ":", isset($paths["/missing"]) ? "bad" : "missing", ":", count($paths), "\n";
$values /** vector<int> */ = [];
$values[] = 4;
$values[] = 7;
$values[] = 9;
foreach ($values as $value) { $value = 100; }
$weighted = 0;
foreach ($values as $index => $value) {
    if ($index === 1) { continue; }
    $weighted = $weighted + $value;
    if ($index === 2) { break; }
}
echo $values[0], ":", $values[1], ":", $weighted, "\n";
$rows /** vector<\samples\Row> */ = [];
$row = new \samples\Row();
$row->id = 12;
$rows[] = $row;
foreach ($rows as $record) { $record->id = 37; }
$lookup = new \samples\Lookup();
$lookup->put(37, 0);
echo $row->id, ":", $lookup->contains($row) ? "found" : "bad", "\n";
$flags /** hash<bool> */ = [];
$flags["false"] = false;
echo isset($flags["false"]) ? "false-present\n" : "bad\n";
$empty /** vector<int> */ = [];
$visits = 0;
foreach ($empty as $item) { $visits = $visits + 1; }
echo $visits, "\n";

