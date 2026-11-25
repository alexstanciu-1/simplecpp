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
$row = new \data\Record();
$other = new \data\Record();
$worker = new \workers\Worker();
$alias = $worker->identity($row);
$worker->change($alias, 4);
$returned = \workers\Worker::pass($row);
echo $row->value, ":", $other->value, ":", $returned === $row ? "same" : "different", ":", $worker->same($worker) === $worker ? "same" : "different", ":", $worker->number(2) === $worker->number(2) ? "number" : "bad", "\n";
