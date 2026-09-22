<?php
declare(strict_types=1);

/*
 * Role: Stream member IDs from one immutable syntax declaration.
 * Used by: Syntax_Access::struct_members and semantic consumers
 * Call map:
 *   advance() -> Syntax_Access::underlying_declaration(); struct_parts()
 *   current() -> current member ID, only while positioned
 */

namespace parse;
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

/** @compiler-api Forward-only traversal; construction does not validate or copy syntax. */
final class Struct_Member_Cursor
{
    private bool $started = false;
    private bool $finished = false;
    private int $member_id = 0;

    public function __construct(
        private readonly Syntax_Tree $tree,
        private readonly int $declaration,
        private readonly syntax_kind $kind,
    )
    {
    }

    /** Validate lazily, then select the next matching sibling. A failed traversal is terminal. */
    public function advance(): bool
    {
        if ($this->finished) {
            return false;
        }
        // Close first so an exception cannot leave a resumable partial traversal.
        $this->finished = true;
        $previous = $this->member_id;
        $this->member_id = 0;
        $id = 0;
        if (!$this->started)
        {
            $this->started = true;
            $parts = Syntax_Access::struct_parts($this->tree,
                Syntax_Access::underlying_declaration($this->tree, $this->declaration));
            $id = $parts->first_member_id;
        }
        else {
            $id = $this->tree->nodes[$previous - 1]->next_sibling_id;
        }
        while ($id !== 0)
        {
            $node = $this->tree->nodes[$id - 1];
            if ($node->kind === $this->kind)
            {
                $this->member_id = $id;
                $this->finished = false;
                return true;
            }
            $id = $node->next_sibling_id;
        }
        return false;
    }

    /** Repeated reads are stable; an unpositioned cursor has no current member. */
    public function current(): int
    {
        if ($this->member_id === 0) {
            throw new \LogicException('Struct member cursor is not positioned');
        }
        return $this->member_id;
    }
}
