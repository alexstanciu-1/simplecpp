<?php
declare(strict_types=1);

/*
 * Role: Reconcile a source scan batch and produce next tasks.
 * Used by: Source_Discovery::run()
 * Call map:
 *   Source_Scan_Join::join()
 *     -> [action] validate results, reconcile identities and seed children
 */

namespace read_sources;
// <scpp-imports>
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

/** @compiler-internal Accept source scan batches into the discovery coordinator's private candidate. */
class Source_Scan_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<source_scan_task> $tasks
     */
    public function __construct(
        private readonly Source_Set $current,
        private readonly Source_Set $previous,
        private readonly array $tasks /** vector<source_scan_task> */
    )
    {
    }

    /**
     * @compiler-internal Discovery coordinator only; mutates its private current candidate after batch validation.
     * An allocation/reconciliation failure may leave that candidate partially populated; discard it.
     * Coordinator only: accept a complete batch in any completion order.
     * Reconcile into the private candidate, returning the next directory batch.
     * Task indexes belong to this batch; do not mix results between batches/runs.
     * @param list<Source_Scan_Result> $results
     * @return list<source_scan_task>
     */
    public function join(array $results /** vector<Source_Scan_Result> */): array /** vector<source_scan_task> */
    {
        $by_task /** hash<Source_Scan_Result, int> */ = [];
        foreach ($results as $result) {
            $index = $result->task->index;
            if ((!isset($this->tasks[$index])) || ($this->tasks[$index] !== $result->task) || (isset($by_task[$index]))) {
                throw new \Exception("Unexpected or duplicate source scan result: " . $index);
            }
            $by_task[$index] = $result;
        }
        if (count($by_task) !== count($this->tasks)) {
            throw new \Exception("Incomplete source scan batch");
        }

        $next /** vector<source_scan_task> */ = [];

        // Task order, never completion order, determines new file identities.
        foreach ($this->tasks as $task)
        {
            $result = $by_task[$task->index];
            foreach ($result->files as $observed)
            {
                $path = Source_Path_Syntax::join($task->root, $observed->relative_path);
                $old_id = $this->previous->find_file_id($path);
                $file = ($old_id !== 0) ? $this->previous->file_by_id($old_id)->copy() : new source_file();
                if ($old_id !== 0)
                {
                    $file->change_state = file_change::unchanged;
                    if (($file->mtime !== $observed->mtime) || ($file->size !== $observed->size)) {
                        $file->change_state = file_change::changed;
                        $file->needs_recompile = true;
                        $file->buffer = null;
                    }
                }
                else
                {
                    if (($this->current->next_file_id < 1) || ($this->current->next_file_id > \read_sources\MAX_SOURCE_FILE_ID)) {
                        throw new \Exception("Source file ID space exhausted");
                    }
                    $file->id = $this->current->next_file_id++;
                    $file->change_state = file_change::added;
                }
                $file->top_folder_index = $task->top_folder_index;
                $file->full_path = $path;
                $file->relative_path = $observed->relative_path;
                $file->mtime = $observed->mtime;
                $file->size = $observed->size;
                $this->current->files[] = $file;
            }
            foreach ($result->directories as $relative) {
                $next[] = new source_scan_task(
                    count($next), $task->top_folder_index, $task->root, $relative
                );
            }
        }
        return $next;
    }
}
