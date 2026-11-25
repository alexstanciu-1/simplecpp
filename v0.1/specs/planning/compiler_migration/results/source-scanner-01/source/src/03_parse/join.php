<?php
declare(strict_types=1);

/*
 * Role: Accumulate parser results and finish the current frontend set.
 * Used by: Parser::init(); finalize()
 * Call map:
 *   Frontend_Join::join() -> merge(); finish()
 *     -> [action] validate segments, preserve order and exclude removed files
 */

namespace parse;
// <scpp-imports>
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


// One coordinator owns preparation for fixed phase inputs. Workers never see
// this mutable candidate. Only finish() exposes a complete Frontend_Set.
/**
 * @compiler-api Stateful coordinator for fixed sources/tokens/previous frontends; used by compile.
 * Workers never access this mutable accumulator. finish exposes current file membership;
 * no project symbols or incremental eligibility decisions are made here.
 */
class Frontend_Join implements \compile\Join
{
    /** @var array<int, \tokenize\Token_Buffer> Fixed selected tasks, adopted after validation. */
    private array $selected /** hash<\tokenize\Token_Buffer,int> */ = [];
    private bool $prepared = false;

    /** @var array<int, File_Frontend> Selected replacements, shared by reference. */
    private array $replacements /** hash<File_Frontend,int> */ = [];

    /**
     * @compiler-api Create one coordinator for fixed phase snapshots and the exact selected batch.
     * Capture only; task validation starts on join(), merge() or finish().
     * @param list<\tokenize\Token_Buffer> $tasks
     */
    public function __construct(
        private readonly Frontend_Set $previous,
        private readonly \read_sources\Source_Set $sources,
        private readonly \tokenize\Token_Set $tokens,
        private readonly array $tasks /** vector<\tokenize\Token_Buffer> */
    )
    {
    }

    /**
     * @compiler-api Accept the supplied batch and finish the selected frontend set.
     * May complete earlier accepted segments. merge() validates a whole segment
     * before adoption; finish() rejects incomplete selection without discarding it.
     * @param list<File_Frontend> $results
     */
    public function join(array $results /** vector<File_Frontend> */): Frontend_Set
    {
        $this->merge($results, 0, count($results));
        return $this->finish();
    }

    /**
     * @compiler-api Accept the zero-based index/count segment, retaining exact frontend references.
     * Validate the entire segment before changing the accumulator; reject duplicate IDs
     * within/across segments, unselected results and stale source/token identities.
     * Caller supplies completed worker outputs; failed segments adopt nothing.
     * @param list<File_Frontend> $results
     */
    public function merge(array $results /** vector<File_Frontend> */, int $index, int $count): void
    {
        $this->prepare();
        if (($index < 0) || ($count < 0) || ($index > count($results)) || ($count > (count($results) - $index))) {
            throw new \Exception('Invalid frontend result segment');
        }
        $segment /** hash<File_Frontend,int> */ = [];
        for ($offset = $index; $offset < ($index + $count); $offset++)
        {
            $result = $results[$offset];
            $id = $result->source_file_id;
            $file = $this->sources->file_by_id($id);
            if ((isset($segment[$id])) || (isset($this->replacements[$id])) || ($file->change_state === \read_sources\file_change::deleted)
                || ($result->tokens !== $this->tokens->for_file($id)) || ($result->tokens->source !== $file->buffer)) {
                throw new \Exception('Duplicate, removed or stale frontend result');
            }
            if (!isset($this->selected[$id])) {
                throw new \Exception('Unexpected unselected frontend result');
            }
            if ($this->selected[$id] !== $result->tokens) {
                throw new \Exception('Unexpected unselected frontend result');
            }
            $result->validate();
            $segment[$id] = $result;
        }

        // Validate the entire segment before editing even private preparation.
        foreach ($segment as $id => $result) {
            $this->replacements[$id] = $result;
        }
    }

    /**
     * @compiler-api Read retained/replacement entries and return a current Frontend_Set, excluding removals.
     * Require one result per selected task, including forced reparses of retained
     * token snapshots. Then validate current membership/reuse and exclude removals.
     * Reject missing/stale frontends; repeated finish calls do not change inputs.
     */
    public function finish(): Frontend_Set
    {
        $this->prepare();
        if (count($this->replacements) !== count($this->selected)) {
            throw new \Exception('Incomplete frontend task batch');
        }
        $files /** vector<File_Frontend> */ = [];

        // Membership/order and completeness are established once at the boundary.
        foreach ($this->sources->files as $file)
        {
            if ($file->change_state === \read_sources\file_change::deleted) {
                continue;
            }
            $result = isset($this->replacements[$file->id]) ? $this->replacements[$file->id] : $this->previous->for_file($file->id);
            if ($result === null) {
                throw new \Exception('Incomplete or stale frontend phase');
            }
            if (($result->tokens !== $this->tokens->for_file($file->id))
                || ($result->tokens->source !== $file->buffer)) {
                throw new \Exception('Incomplete or stale frontend phase');
            }
            $files[] = $result;
        }
        return new Frontend_Set($files);
    }

    /**
     * Index selected parse tasks lazily, validating identities and current source/token inputs before adoption.
     * Construction has no processing side effects.
     */
    private function prepare(): void
    {
        if ($this->prepared) {
            return;
        }
        $selected /** hash<\tokenize\Token_Buffer,int> */ = [];
        foreach ($this->tasks as $task)
        {
            $id = $task->source->source_file_id;
            $file = $this->sources->file_by_id($id);
            if ((isset($selected[$id])) || ($file->change_state === \read_sources\file_change::deleted)
                || ($this->tokens->for_file($id) !== $task) || ($file->buffer !== $task->source)) {
                throw new \Exception('Duplicate, removed or stale frontend task');
            }
            $selected[$id] = $task;
        }
        $this->selected = $selected;
        $this->prepared = true;
    }
}
