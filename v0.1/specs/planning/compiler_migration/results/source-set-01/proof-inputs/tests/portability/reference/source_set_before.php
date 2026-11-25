<?php
declare(strict_types=1);

/*
 * Role: Source bytes, metadata snapshots and lookup indexes.
 * Used by: Source_Reader; source joins; downstream frontend
 * Flow: Source_Buffer / source_file -> Source_Set -> consumers
 */

namespace read_sources;

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
    public array $folders = [];

    /** @var list<source_file> */
    public array $files = [];

    /** @var list<int> */
    public array $removed_file_ids = [];
    public int $next_file_id = 1;
    private int $entry_file_id = 0;

    /** @compiler-api After a complete discovery join, report pending file work/removals; no mutation. */
    public function has_pending_changes(): bool
    {
        if ($this->removed_file_ids !== []) {
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
    private array $by_top_folder = [];

    /** @var array<int, int> */
    private array $row_by_file_id = [];

    /** @var array<string, int> */
    private array $id_by_path = [];

    /**
     * @compiler-api Prepare an acknowledged snapshot for successful publication.
     * Clear pending work/removal observations in a new owner; retain unchanged rows
     * and immutable buffers. Never mutate this snapshot or reclaim tombstones.
     * The caller must adopt the result only after publication succeeds.
     */
    public function acknowledged(): self
    {
        $result = clone $this;
        foreach ($this->files as $index => $file)
        {
            if (!$file->needs_recompile) {
                continue;
            }
            $row = clone $file;
            $row->needs_recompile = false;
            $result->files[$index] = $row;
        }
        $result->removed_file_ids = [];
        return $result;
    }

    /**
     * @compiler-internal Rebuild private indexes from a candidate dataset; throws on invalid identity/membership.
     * Never call on a shared phase input.
     */
    public function refresh_indexes(): void
    {
        $by_top_folder = [];
        $row_by_file_id = [];
        $id_by_path = [];
        foreach ($this->folders as $folder) {
            $ids = [];
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
    public function file_ids_in_folder(int $index): array
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
        $folders = [];
        foreach ($this->folders as $index => $folder)
        {
            $row = [];
            $row["path"] = $folder->path;
            $row["resolved_path"] = $folder->resolved_path;
            if ($folder->file_names !== null) {
                $row["file_names"] = $folder->file_names;
            }
            $folders[$index] = $row;
        }
        $files = [];
        foreach ($this->files as $index => $file)
        {
            $row = [];
            $row["id"] = (int)$file->id;
            $row["top_folder_index"] = (int)$file->top_folder_index;
            $row["path"] = $file->full_path;
            $row["relative_path"] = $file->relative_path;
            $row["mtime"] = (int)$file->mtime;
            $row["size"] = (int)$file->size;
            $row["change_state"] = $file->change_state->name;
            $row["needs_recompile"] = $file->needs_recompile;
            $files[$index] = $row;
        }
        $removed = [];
        foreach ($this->removed_file_ids as $index => $id) {
            $removed[$index] = (int)$id;
        }
        $result = [];
        $result["folders"] = $folders;
        $result["files"] = $files;
        $result["removed_file_ids"] = $removed;
        $result["entry_file_id"] = $this->entry_file_id;
        try {
            return json_encode($result, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $exception) {
            throw new \Exception("Cannot export sources: " . $exception->getMessage(), 0, $exception);
        }
    }
}
