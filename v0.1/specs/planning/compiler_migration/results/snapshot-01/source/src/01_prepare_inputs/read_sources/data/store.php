<?php
declare(strict_types=1);

/*
 * Role: Source bytes, metadata snapshots and lookup indexes.
 * Used by: Source_Reader; source joins; downstream frontend
 * Flow: Source_Buffer / source_file -> Source_Set -> consumers
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

// Own the folder table, shared file dataset, and indexes for one source snapshot.
// The coordinator assembles a replacement; phase readers never mutate it.
// Row lookups return the stored object; clone before editing a retained row.
/**
 * @compiler-api Current source membership produced by discovery/snapshot joins.
 * Readable fields: folders, files, removed_file_ids. Use lookup methods for identity
 * and folder membership; file IDs are not row offsets. Shared records are read-only.
 * read_sources owns construction/index maintenance and acknowledgment. Compile
 * requests an acknowledged snapshot before publication; it does not edit rows.
 * next_file_id is discovery allocation state, not a downstream API.
 */
class Source_Set implements \compile\Step_Result, \compile\Step_Store
{
    /** @var list<source_folder> */
    public array $folders /** vector<source_folder> */ = [];

    /** @var list<source_file> */
    public array $files /** vector<source_file> */ = [];

    /** @var list<int> */
    public array $removed_file_ids /** vector<int> */ = [];
    public int $next_file_id = 1;
    private int $entry_file_id = 0;

    /** @compiler-api After a complete discovery join, report pending file work/removals; no mutation. */
    public function has_pending_changes(): bool
    {
        if (count($this->removed_file_ids) > 0) {
            return true;
        }
        foreach ($this->files as $file) {
            if ($file->needs_recompile) {
                return true;
            }
        }
        return false;
    }

    /** @compiler-internal Discovery binds the manifest entry after complete membership/index construction. */
    public function set_entry_file(int $id): void
    {
        $file = $this->file_by_id($id);
        if ($file->change_state === file_change::deleted) {
            throw new \LogicException('Entry file is removed');
        }
        $this->entry_file_id = $id;
    }

    /** @compiler-api Read a shared row by logical file ID, including tombstones; throws when unknown. */
    public function file_by_id(int $id): source_file
    {
        if (!isset($this->row_by_file_id[$id])) {
            throw new \Exception("Unknown source file ID");
        }
        return $this->files[(int)$this->row_by_file_id[$id]];
    }

    /** @compiler-api Read the entry row selected during discovery; no filesystem lookup or mutation. */
    public function entry_file(): source_file
    {
        if ($this->entry_file_id === 0) {
            throw new \LogicException('Source entry is not prepared');
        }
        $file = $this->file_by_id($this->entry_file_id);
        if ($file->change_state === file_change::deleted) {
            throw new \LogicException('Source entry is removed');
        }
        return $file;
    }

    // Folder indexes contain logical file IDs, never copies of file records.
    /** @var list<list<int>> */
    private array $by_top_folder /** vector<vector<int>> */ = [];

    /** @var array<int, int> */
    private array $row_by_file_id /** hash<int, int> */ = [];

    /** @var array<string, int> */
    private array $id_by_path /** hash<int> */ = [];

    /** @compiler-internal Independent membership/index storage; shared rows and buffers. */
    public function copy(): Source_Set
    {
        $result = new Source_Set();
        $result->folders = $this->folders;
        $result->files = $this->files;
        $result->removed_file_ids = $this->removed_file_ids;
        $result->next_file_id = $this->next_file_id;
        $result->entry_file_id = $this->entry_file_id;
        $result->by_top_folder = $this->by_top_folder;
        $result->row_by_file_id = $this->row_by_file_id;
        $result->id_by_path = $this->id_by_path;
        return $result;
    }

    /**
     * @compiler-api Prepare an acknowledged snapshot for successful publication.
     * Clear pending work/removal observations in a new owner; retain unchanged rows
     * and immutable buffers. Never mutate this snapshot or reclaim tombstones.
     * The caller must adopt the result only after publication succeeds.
     */
    public function acknowledged(): Source_Set
    {
        $result = $this->copy();
        $empty_removed /** vector<int> */ = [];
        $result->removed_file_ids = $empty_removed;
        foreach ($this->files as $index => $file)
        {
            if (!$file->needs_recompile) {
                continue;
            }
            $row = $file->copy();
            $row->needs_recompile = false;
            $result->files[$index] = $row;
        }
        return $result;
    }

    /**
     * @compiler-internal Rebuild private indexes from a candidate dataset; throws on invalid identity/membership.
     * Never call on a shared phase input.
     */
    public function refresh_indexes(): void
    {
        $by_top_folder /** vector<vector<int>> */ = [];
        $row_by_file_id /** hash<int, int> */ = [];
        $id_by_path /** hash<int> */ = [];
        foreach ($this->folders as $folder) {
            $ids /** vector<int> */ = [];
            $by_top_folder[] = $ids;
        }
        foreach ($this->files as $index => $file)
        {
            if (((int)$file->id === 0) || (isset($row_by_file_id[$file->id]))) {
                throw new \Exception("Invalid or duplicate source file ID");
            }
            $row_by_file_id[$file->id] = $index;
            $path = $file->full_path;
            if ($file->change_state === file_change::deleted) {
                continue;
            }
            if ($file->change_state === file_change::moved) {
                throw new \Exception("Unsupported source change: moved");
            }
            $folder_index = (int)$file->top_folder_index;
            if (($folder_index < 0) || ($folder_index >= count($this->folders))) {
                throw new \Exception("Source file has no current folder owner");
            }
            if (isset($id_by_path[$path])) {
                throw new \Exception("Duplicate source path: " . $path);
            }
            $id_by_path[$path] = $file->id;
            $by_top_folder[$folder_index][] = $file->id;
        }

        // Adopt all indexes together after the candidate membership has been validated.
        $this->by_top_folder = $by_top_folder;
        $this->row_by_file_id = $row_by_file_id;
        $this->id_by_path = $id_by_path;
    }

    /** @compiler-api Read a shared folder by zero-based position in this snapshot; throws when absent. */
    public function folder_by_index(int $index): source_folder
    {
        if (($index < 0) || ($index >= count($this->folders))) {
            throw new \Exception("Invalid source folder index");
        }
        return $this->folders[(int)$index];
    }

    /** @compiler-api Read live logical file IDs for a snapshot-local folder index; throws when absent. */
    public function file_ids_in_folder(int $index): array /** vector<int> */
    {
        if (($index < 0) || ($index >= count($this->by_top_folder))) {
            throw new \Exception("Invalid source folder index");
        }
        return $this->by_top_folder[(int)$index];
    }

    /** @compiler-api Look up a canonical live path; return zero when absent. Deleted rows are excluded. */
    public function find_file_id(string $path): int
    {
        if (!isset($this->id_by_path[$path])) {
            return 0;
        }
        return $this->id_by_path[$path];
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $encoded = '';
        try {
            $encoded = $this->encode_json();
        } catch (\JsonException $exception) {
            throw new \Exception("Cannot export sources: " . $exception->getMessage(), 0, $exception);
        }
        return $encoded;
    }

    private function encode_json(): string {
        $out = '{"folders":[';
        $separator = '';
        foreach ($this->folders as $folder) {
            $out .= $separator . '{"path":' . Source_Json::quote($folder->path)
                . ',"resolved_path":' . Source_Json::quote($folder->resolved_path);
            $names /** vector<string> */ = [];
            if (take_nullable($names, $folder->file_names)) {
                $out .= ',"file_names":[';
                $name_separator = '';
                foreach ($names as $name) {
                    $out .= $name_separator . Source_Json::quote($name);
                    $name_separator = ',';
                }
                $out .= ']';
            }
            $out .= '}';
            $separator = ',';
        }
        $out .= '],"files":[';
        $separator = '';
        foreach ($this->files as $file) {
            $out .= $separator . '{"id":' . $file->id . ',"top_folder_index":' . $file->top_folder_index
                . ',"path":' . Source_Json::quote($file->full_path)
                . ',"relative_path":' . Source_Json::quote($file->relative_path)
                . ',"mtime":' . $file->mtime . ',"size":' . $file->size
                . ',"change_state":' . Source_Json::quote(Source_Json::change_name($file->change_state))
                . ',"needs_recompile":' . ($file->needs_recompile ? 'true' : 'false') . '}';
            $separator = ',';
        }
        $out .= '],"removed_file_ids":[';
        $separator = '';
        foreach ($this->removed_file_ids as $id) {
            $out .= $separator . $id;
            $separator = ',';
        }
        return $out . '],"entry_file_id":' . $this->entry_file_id . '}';
    }
}
