<?php
namespace samples;
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
class Row { public int $value = 1; }
trait Exports {
    public function ids(): array /** vector<int> */ { return $this->ids; }
    public function positions(): array /** hash<int, int> */ { return $this->positions; }
    public function groups(): array /** vector<vector<int>> */ { return $this->groups; }
    public function rows(): array /** vector<Row> */ { return $this->rows; }
}
interface Export_Contract { public function ids(): array /** vector<int> */; }
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
    public function entry(): int { return $this->entry; }
    public function ready(): bool { return $this->ready; }
    public function label(): string { return $this->label; }
    public static function empty_ids(): array /** vector<int> */ {
        $empty /** vector<int> */ = [];
        return $empty;
    }
}
