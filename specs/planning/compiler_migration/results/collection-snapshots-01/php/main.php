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
$first = new \snapshots\Row();
$first->value = 10;
$first->label = "original";
$first->tags[] = 3;
$second = new \snapshots\Row();
$second->value = 20;
$base = new \snapshots\Store();
$base->rows[] = $first;
$base->rows[] = $second;
$next = $base->with_value(0, 11);
$latest = $next->with_value(0, 12);
echo $base->rows[0]->value, ":", $next->rows[0]->value, ":", $latest->rows[0]->value, "\n";
echo $base !== $next ? "owner-distinct" : "bad", ":", $base->rows[0] !== $next->rows[0] ? "changed-distinct" : "bad", ":", $base->rows[1] === $next->rows[1] ? "unchanged-shared" : "bad", "\n";
$next->rows[0]->tags[0] = 8;
$next->rows[0]->tags[] = 9;
$next->rows[0]->label = "edited";
echo $base->rows[0]->tags[0], ":", $next->rows[0]->tags[0], ":", $latest->rows[0]->tags[0], ":", count($base->rows[0]->tags), ":", count($next->rows[0]->tags), ":", $base->rows[0]->label, ":", $latest->rows[0]->label, "\n";
$next->rows[] = new \snapshots\Row();
echo count($base->rows), ":", count($next->rows), ":", count($latest->rows), "\n";
// Separate witness: shallow membership copies do not freeze shared rows.
$shallow = $base->shallow_copy();
$shallow->rows[1]->value = 99;
echo $base->rows[1]->value, ":", $latest->rows[1]->value, "\n";

