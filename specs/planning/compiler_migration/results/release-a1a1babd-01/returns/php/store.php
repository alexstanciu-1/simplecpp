<?php
namespace samples;
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
class Row { public int $value = 1; }
trait Exports {
    public function get_ids(): array /** vector<int> */ { return $this->ids; }
    public function get_positions(): array /** hash<int, int> */ { return $this->positions; }
    public function get_groups(): array /** vector<vector<int>> */ { return $this->groups; }
    public function get_rows(): array /** vector<Row> */ { return $this->rows; }
}
class Store {
    use Exports;
    private int $entry = 0;
    protected bool $ready = false;
    private string $label = "initial";
    private array $ids /** vector<int> */ = [];
    private array $positions /** hash<int, int> */ = [];
    private array $groups /** vector<vector<int>> */ = [];
    private array $rows /** vector<Row> */ = [];
    public function initialize(Row $row): void {
        $this->entry = 37;
        $this->ready = true;
        $this->label = "prepared";
        $this->ids[] = 12;
        $this->ids[] = 37;
        $this->positions[37] = 1;
        $this->groups[] = $this->ids;
        $this->rows[] = $row;
    }
    public function get_entry(): int { return $this->entry; }
    public function get_ready(): bool { return $this->ready; }
    public function get_label(): string { return $this->label; }
    public static function empty_ids(): array /** vector<int> */ {
        $empty /** vector<int> */ = [];
        return $empty;
    }
}
