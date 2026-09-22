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
$index = new \samples\Indexes();
$index->initialize();
echo $index->row(37), ":", $index->file("/root/a.phs"), ":", $index->copy_edit(), ":", $index->first(), "\n";
$positions /** hash<int, int> */ = [];
$positions[42] = 2;
$positions[7] = 5;
$positions_copy = $positions;
$positions_copy[42] = 8;
echo count($positions), ":", $positions[42], ":", $positions_copy[42], ":", $positions[7], "\n";
$groups /** hash<vector<int>, string> */ = [];
$ids /** vector<int> */ = [];
$ids[] = 3;
$groups["folder"] = $ids;
$groups_copy = $groups;
$groups_copy["folder"][0] = 9;
echo $groups["folder"][0], ":", $groups_copy["folder"][0], "\n";

