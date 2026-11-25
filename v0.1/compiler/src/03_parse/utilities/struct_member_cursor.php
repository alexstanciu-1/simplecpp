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

/** @compiler-api Forward-only traversal; construction does not validate or copy syntax. */
final class Struct_Member_Cursor
{
    private bool $started = false;
    private bool $finished = false;
    private int $member_id = 0;

    public function __construct(
        private readonly Syntax_Arena $tree,
        private readonly int $declaration,
        private readonly int $kind,
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
            $id = (int)$parts->first_member_id;
        }
        else {
            $id = (int)$this->tree->row($previous)->next_sibling;
        }
        while ($id !== 0)
        {
            $node = $this->tree->row($id);
            if ((int)$node->kind === (int)$this->kind)
            {
                $this->member_id = $id;
                $this->finished = false;
                return true;
            }
            $id = (int)$node->next_sibling;
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
