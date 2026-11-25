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
echo $store->entry(), ":", $store->ready() ? "ready" : "pending", ":", $store->label(), "\n";
$row = new \samples\Row();
$store->initialize($row);
$ids = $store->ids();
$ids[0] = 99;
$original = $store->ids();
$positions = $store->positions();
$positions[37] = 8;
$original_positions = $store->positions();
$groups = $store->groups();
$groups[0][0] = 104;
$original_groups = $store->groups();
echo $original[0], ":", $ids[0], ":", $original_positions[37], ":", $positions[37], ":", $original_groups[0][0], ":", $groups[0][0], "\n";
$rows = $store->rows();
$rows[0]->value = 9;
echo $row->value, ":", $store->entry(), ":", $store->ready() ? "ready" : "bad", ":", $store->label(), "\n";
$empty = \samples\Store::empty_ids();
echo count($empty), "\n";

