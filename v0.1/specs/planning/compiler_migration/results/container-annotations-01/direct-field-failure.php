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
class Indexes {
    private array $folders /** vector<vector<int>> */ = [];
    private array $positions /** hash<int, int> */ = [];
    protected array $paths /** hash<int> */ = [];
    public function initialize(): void {
        $ids /** vector<int> */ = [];
        $ids[] = 12;
        $ids[] = 37;
        $this->folders[] = $ids;
        $this->positions[12] = 0;
        $this->positions[37] = 1;
        $this->paths["/root/a.phs"] = 12;
    }
    public function row(int $id): int { return $this->positions[$id]; }
    public function file(string $path): int { return $this->paths[$path]; }
    public function first(): int { return $this->folders[0][0]; }
    public function copy_edit(): int {
        $copy = $this->folders;
        $copy[0][0] = 99;
        $copy[0][] = 104;
        return count($copy[0]);
    }
}
