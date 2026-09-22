<?php
declare(strict_types=1);
namespace parse;
// <scpp-imports>
use function scpp\fs_read_snapshot as fs_read_snapshot;
use function scpp\fs_is_windows as fs_is_windows;
use function scpp\fs_basename as fs_basename;
use function scpp\fs_dirname as fs_dirname;
use function scpp\fs_read_text as fs_read_text;
use function scpp\fs_require_realpath as fs_require_realpath;
use function scpp\json_read as json_read;
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

/** Single builder owner; IDs are one-based and zero means no node. */
final class Syntax_Arena {
    private array $rows /** vector<Syntax_Row> */ = [];
    public function size(): int { return count($this->rows); }
    private function require_id(int $id): void {
        if ($id < 1) { throw new \InvalidArgumentException('Invalid syntax node ID'); }
        if ($id > count($this->rows)) { throw new \InvalidArgumentException('Invalid syntax node ID'); }
    }
    /** Explicit copy prevents PHP object identity from defining native record behavior. */
    private static function copy(Syntax_Row $row): Syntax_Row {
        $out = new Syntax_Row();
        $out->kind = $row->kind;
        $out->start = $row->start;
        $out->length = $row->length;
        $out->first_child = $row->first_child;
        $out->last_child = $row->last_child;
        $out->next_sibling = $row->next_sibling;
        return $out;
    }
    public function row(int $id): Syntax_Row {
        $this->require_id($id);
        return Syntax_Arena::copy($this->rows[$id - 1]);
    }
    public function add(int $kind, int $start, int $length): int {
        if (($kind < 1) || ($start < 0) || ($length < 0)) { throw new \InvalidArgumentException('Invalid syntax row'); }
        if (($kind > 4294967295) || ($start > 4294967295) || ($length > 4294967295)) { throw new \InvalidArgumentException('Syntax row exceeds uint32 capacity'); }
        if (count($this->rows) >= 4294967295) { throw new \RuntimeException('Syntax ID capacity exhausted'); }
        $row = new Syntax_Row();
        $row->kind = $kind;
        $row->start = $start;
        $row->length = $length;
        $this->rows[] = $row;
        return count($this->rows);
    }
    public function finish(int $id, int $end): void {
        $row = $this->row($id);
        if ($end < (int)$row->start) { throw new \InvalidArgumentException('Invalid syntax end'); }
        if ($end > 4294967295) { throw new \InvalidArgumentException('Syntax end exceeds uint32 capacity'); }
        $row->length = $end - (int)$row->start;
        $this->rows[$id - 1] = $row;
    }
    /** Builder-only: child must be an unattached subtree, with no ancestor link to parent. */
    public function child(int $parent, int $child): void {
        $owner = $this->row($parent);
        $node = $this->row($child);
        if ($parent === $child) { throw new \InvalidArgumentException('Self-linked syntax node'); }
        if ((int)$node->next_sibling !== 0) { throw new \InvalidArgumentException('Child already has siblings'); }
        if ((int)$owner->last_child === $child) { throw new \InvalidArgumentException('Duplicate syntax child'); }
        if ((int)$owner->last_child === 0) {
            $owner->first_child = $child;
        } else {
            $last_id = (int)$owner->last_child;
            $last = $this->row($last_id);
            $last->next_sibling = $child;
            $this->rows[$last_id - 1] = $last;
        }
        $owner->last_child = $child;
        $this->rows[$parent - 1] = $owner;
    }
}
