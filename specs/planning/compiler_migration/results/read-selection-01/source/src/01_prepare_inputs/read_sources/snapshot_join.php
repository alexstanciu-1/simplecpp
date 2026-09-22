<?php
declare(strict_types=1);

/*
 * Role: Accept snapshots into replacement source state.
 * Used by: Source_Reader::finalize()
 * Call map:
 *   Snapshot_Join::join()
 *     -> [action] validate membership and copy changed source rows
 */

namespace read_sources;
// <scpp-imports>
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

/** @compiler-internal Accept source-read results and retain current source snapshots. */
class Snapshot_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<source_read_task> $tasks
     */
    public function __construct(
        private readonly Source_Set $current,
        private readonly array $tasks /** vector<source_read_task> */
    )
    {
    }

    /**
     * @compiler-api Accept one matching buffer per selected file and validate retained live buffers.
     * Return a separate Source_Set; preserve shared rows unless their buffer changes.
     * Reject duplicate, missing or stale input/result batches before returning a set.
     * Coordinator only. Join the complete selected batch in any arrival order.
     * File records hold immutable buffers directly. Earlier snapshots retain
     * their own references when the candidate replaces or removes a buffer.
     * @param list<Source_Buffer> $results
     */
    public function join(array $results /** vector<Source_Buffer> */): Source_Set
    {
        // Validate task versions against the discovered source candidate.
        $selected /** hash<source_read_task,int> */ = [];
        foreach ($this->tasks as $task)
        {
            if (isset($selected[$task->source_file_id])) {
                throw new \Exception("Duplicate source read task");
            }
            $file = $this->current->file_by_id($task->source_file_id);
            if (($file->change_state === file_change::deleted) || ($task->mtime !== $file->mtime)
                || ($task->size !== $file->size) || ($task->path !== $file->full_path)) {
                throw new \Exception("Stale source read task");
            }
            $selected[$task->source_file_id] = $task;
        }

        // Accept exactly one matching immutable snapshot for every selected read.
        $by_file /** hash<Source_Buffer,int> */ = [];
        foreach ($results as $buffer)
        {
            $id = $buffer->source_file_id;
            if (!isset($selected[$id])) {
                throw new \Exception("Unexpected, duplicate or stale source snapshot");
            }
            $task = $selected[$id];
            if ((isset($by_file[$id])) || ($buffer->path !== $task->path)
                || ($buffer->mtime !== $task->mtime) || (string_byte_len($buffer->content) !== $task->size)) {
                throw new \Exception("Unexpected, duplicate or stale source snapshot");
            }
            $by_file[$id] = $buffer;
        }
        if (count($by_file) !== count($selected)) {
            throw new \Exception("Incomplete source read batch");
        }

        // Replace or clear buffers in a private candidate while retained rows remain untouched.
        $joined = $this->current->copy();
        $empty_files /** vector<source_file> */ = [];
        $joined->files = $empty_files;
        foreach ($this->current->files as $old)
        {
            $buffer = $old->buffer;
            if ($old->change_state !== file_change::deleted)
            {
                if (isset($by_file[$old->id])) { $buffer = $by_file[$old->id]; }
                if ($buffer === null) {
                    throw new \Exception("Stale retained source snapshot");
                }
                if (($buffer->source_file_id !== $old->id) || ($buffer->mtime !== $old->mtime)
                    || (string_byte_len($buffer->content) !== $old->size)
                    || ($buffer->path !== $old->full_path)) {
                    throw new \Exception("Stale retained source snapshot");
                }
            }
            else {
                $buffer = null;
            }

            // Share retained rows; only buffer replacement requires a private record.
            $file = $old;
            if ($old->buffer !== $buffer) {
                $file = $old->copy();
                $file->buffer = $buffer;
            }
            $joined->files[] = $file;
        }

        // File IDs, row positions and membership are unchanged; indexes remain valid.
        return $joined;
    }
}
