<?php
declare(strict_types=1);

/*
 * Role: Accept token buffers against their exact source snapshots.
 * Used by: Tokenizer::finalize()
 * Call map:
 *   Token_Join::join()
 *     -> [action] validate selected results and assemble Token_Set
 */

namespace tokenize;
// <scpp-imports>
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


/** @compiler-internal Accept tokenization results and retain current token buffers. */
class Token_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<Source_Buffer> $tasks
     */
    public function __construct(
        private readonly \read_sources\Source_Set $sources,
        private readonly Token_Set $previous,
        private readonly array $tasks /** vector<\read_sources\Source_Buffer> */
    )
    {
    }

    /**
     * @compiler-api Accept one result per selected exact source buffer in any arrival order.
     * Require complete current live membership, exclude removed files, and retain unchanged
     * buffers. Missing, duplicate or stale batches throw; previous sets remain intact.
     * Coordinator only. Validate completeness before retaining a new token set.
     * @param list<Token_Buffer> $results
     */
    public function join(array $results /** vector<\tokenize\Token_Buffer> */): Token_Set
    {
        // Validate selected snapshots before accepting worker results.
        $selected /** hash<\read_sources\Source_Buffer,int> */ = [];
        foreach ($this->tasks as $source)
        {
            $id = $source->source_file_id;
            $file = $this->sources->file_by_id($id);
            if ((isset($selected[$id])) || ($file->change_state === \read_sources\file_change::deleted)
                || ($file->buffer !== $source)) {
                throw new \Exception('Duplicate or stale tokenization task');
            }
            $selected[$id] = $source;
        }

        // Index exactly one result per selected source, regardless of completion order.
        $by_file /** hash<\tokenize\Token_Buffer,int> */ = [];
        foreach ($results as $buffer) {
            $id = $buffer->source->source_file_id;
            if (!isset($selected[$id])) {
                throw new \Exception('Unexpected, duplicate or stale tokenization result');
            }
            if ((isset($by_file[$id])) || ($buffer->source !== $selected[$id])) {
                throw new \Exception('Unexpected, duplicate or stale tokenization result');
            }
            $by_file[$id] = $buffer;
        }
        if (count($by_file) !== count($selected)) {
            throw new \Exception('Incomplete tokenization batch');
        }

        // Rebuild live membership from replacements and unchanged snapshots; deleted files drop out.
        $buffers /** vector<\tokenize\Token_Buffer> */ = [];
        foreach ($this->sources->files as $file)
        {
            if ($file->change_state === \read_sources\file_change::deleted) {
                continue;
            }
            $buffer = isset($by_file[$file->id]) ? $by_file[$file->id] : $this->previous->for_file($file->id);
            if ($buffer === null) {
                throw new \Exception('Missing or stale retained token buffer');
            }
            if ($buffer->source !== $file->buffer) {
                throw new \Exception('Missing or stale retained token buffer');
            }
            $buffers[] = $buffer;
        }
        return new Token_Set($buffers);
    }
}
