<?php
// <scpp-imports>
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

