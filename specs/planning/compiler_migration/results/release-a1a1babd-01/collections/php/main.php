<?php
declare(strict_types=1);
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
$input /** vector<int> */ = [10, 20, 30];
$selected /** vector<int> */ = sequence_filter($input, static function (int $x): bool { return $x > 10; });
foreach ($selected as $k => $v) { echo $k, ":", $v, "\n"; }
$keyed /** hash<int, int> */ = [];
$keyed[0] = 10; $keyed[1] = 20; $keyed[2] = 30;
$kept /** hash<int, int> */ = keyed_filter($keyed, function (int $x): bool { return $x > 10; });
foreach ($kept as $k => $v) { echo $k, ":", $v, "\n"; }
$suffix = "!";
$labels /** vector<string> */ = sequence_map($input, function (int $x) use ($suffix): string { return "item" . $suffix; });
echo $labels[0], ":", count($labels), "\n";
$words /** hash<string> */ = [];
$words["b"] = "two"; $words["04"] = "four";
$mapped /** hash<int> */ = keyed_map($words, function (string $x): int { return strlen($x); });
foreach ($mapped as $k => $v) { echo $k, ":", $v, "\n"; }
$empty /** vector<int> */ = [];
$none /** vector<int> */ = sequence_filter($input, function (int $x): bool { return false; });
$all /** vector<int> */ = sequence_filter($input, function (int $x): bool { return true; });
$zero /** vector<int> */ = sequence_map($empty, function (int $x): int { throw new \Exception("must not run"); });
echo count($none), ":", count($all), ":", count($zero), "\n";
$nested /** vector<int> */ = sequence_map(sequence_filter($input, function (int $x): bool { return $x > 10; }), function (int $x): int { return $x + 1; });
echo $nested[0], ":", $nested[1], "\n";
try { sequence_map($input, function (int $x): int { throw new \Exception("callback error"); }); }
catch (\Exception $error) { echo $error->getMessage(), "\n"; }
class Groups {
    private array $items /** vector<vector<int>> */ = [];
    public function initialize(): void { $row /** vector<int> */ = [42]; $this->items[] = $row; }
    public function first(): int { return $this->items[0][0]; }
}
$groups = new Groups(); $groups->initialize(); echo $groups->first(), "\n";
class Row { public int $value = 7; }
$row = new Row();
$rows /** vector<Row> */ = []; $rows[] = $row;
$kept_rows /** vector<Row> */ = sequence_filter($rows, function (Row $item): bool { return $item->value > 0; });
echo $kept_rows[0] === $row ? "shared" : "bad", "\n";
$kept_rows[] = new Row(); echo count($rows), ":", count($kept_rows), "\n";
$empty_hash /** hash<int,int> */ = [];
$empty_mapped /** hash<string,int> */ = keyed_map($empty_hash, function (int $x): string { throw new \Exception("must not run"); });
$all_keys /** hash<int,int> */ = keyed_filter($keyed, function (int $x): bool { return true; });
$no_keys /** hash<int,int> */ = keyed_filter($keyed, function (int $x): bool { return false; });
echo count($empty_mapped), ":", count($all_keys), ":", count($no_keys), "\n";

