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
$store = new \samples\Store();
echo $store->get_entry(), ":", $store->get_ready() ? "ready" : "pending", ":", $store->get_label(), "\n";
$row = new \samples\Row();
$store->initialize($row);
$ids /** vector<int> */ = $store->get_ids();
$ids[0] = 99;
$original /** vector<int> */ = $store->get_ids();
$positions /** hash<int, int> */ = $store->get_positions();
$positions[37] = 8;
$original_positions /** hash<int, int> */ = $store->get_positions();
$groups /** vector<vector<int>> */ = $store->get_groups();
$groups[0][0] = 104;
$original_groups /** vector<vector<int>> */ = $store->get_groups();
echo $original[0], ":", $ids[0], ":", $original_positions[37], ":", $positions[37], ":", $original_groups[0][0], ":", $groups[0][0], "\n";
$rows /** vector<\samples\Row> */ = $store->get_rows();
$rows[0]->value = 9;
echo $row->value, ":", $store->get_entry(), ":", $store->get_ready() ? "ready" : "bad", ":", $store->get_label(), "\n";
$empty /** vector<int> */ = \samples\Store::empty_ids();
echo count($empty), "\n";

