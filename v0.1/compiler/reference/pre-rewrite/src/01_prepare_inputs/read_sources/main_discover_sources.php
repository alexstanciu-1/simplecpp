<?php
declare(strict_types=1);

/*
 * Role: Source_Discovery phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] resolve source selections; seed scan tasks
 *   run() -> Source_Scanner::scan(); Source_Scan_Join::join() [each breadth-first batch]
 *   finalize() -> [action] reconcile removals, indexes and entry
 * Output: result() returns Source_Set after finalize().
 */

namespace read_sources;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Source_Discovery implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Source_Set $output;
    private Source_Set $current;
    private array $tasks = [];

    public function __construct(
        private readonly \read_manifest\Project_Manifest $manifest,
        private readonly Source_Set $previous
    )
    {
    }

    /** Validate disjoint selections and seed fixed scan tasks while retaining the file-ID allocation boundary. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            // PHP caches filesystem metadata and realpaths across requests and renames.
            clearstatcache(true);
            $this->current = new Source_Set();
            $this->current->next_file_id = $this->previous->next_file_id;
            $this->current->removed_file_ids = $this->previous->removed_file_ids;

            // Resolve disjoint roots before assigning directory work or reconciling file identities.
            foreach ($this->manifest->source_folder_paths as $configured)
            {
                $root = Source_Paths::resolve($this->manifest->directory, $configured);
                if (!is_dir($root)) {
                    throw new \Exception("Source root is not a directory: " . $root);
                }
                foreach ($this->current->folders as $folder) {
                    $other = $folder->resolved_path;
                    if (($root === $other) || str_starts_with($root, Source_Paths::join($other, ""))
                        || str_starts_with($other, Source_Paths::join($root, ""))) {
                        throw new \Exception("Overlapping source roots: " . $root . " and " . $other);
                    }
                }
                $folder = new source_folder();
                $folder->path = $configured;
                $folder->resolved_path = $root;
                $this->current->folders[] = $folder;
            }

            // Explicit files use their real directory as path context, without recursive membership.
            foreach ($this->manifest->source_file_paths as $configured)
            {
                $path = Source_Paths::resolve($this->manifest->directory, $configured);
                if (!is_file($path)) {
                    throw new \Exception("Source path is not a regular file: " . $path);
                }
                $folder = new source_folder();
                $folder->path = dirname($configured);
                $folder->resolved_path = dirname($path);
                $folder->file_names = [basename($path)];
                $this->current->folders[] = $folder;
            }

            // Seed fixed scan batches in project selection order.
            $this->tasks = [];
            foreach ($this->current->folders as $folder_index => $folder) {
                $this->tasks[] = new source_scan_task(
                    count($this->tasks), $folder_index,
                    $folder->resolved_path, "", $folder->file_names
                );
            }
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Scan each fixed breadth-first batch and join it before selecting child-directory work. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            while ($this->tasks !== []) {
                $results = [];

                // Serial executor today. Future workers receive only their task;
                // every selection in this batch can be scanned independently.
                foreach ($this->tasks as $task) {
                    $results[] = Source_Scanner::scan($task);
                }
                $this->tasks = (new Source_Scan_Join($this->current, $this->previous, $this->tasks))->join($results);
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Reconcile removals only after the complete scan, then resolve the participating entry and publish membership. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try
        {
            // Only a complete successful scan can establish absence.
            $seen = [];
            foreach ($this->current->files as $file) {
                $seen[$file->id] = true;
            }

            // Logical removal now; physical reclamation is deferred. Old inputs stay intact.
            foreach ($this->previous->files as $old_file)
            {
                if (!isset($seen[$old_file->id]))
                {
                    $file = clone $old_file;
                    if ($old_file->change_state !== file_change::deleted) {
                        $this->current->removed_file_ids[] = $old_file->id;
                    }
                    $file->change_state = file_change::deleted;
                    $file->needs_recompile = false;
                    $file->top_folder_index = -1;
                    $file->buffer = null;
                    $this->current->files[] = $file;
                }
            }

            // Resolve the project entry only after complete membership and removals are known.
            $this->current->refresh_indexes();
            $entry = Source_Paths::resolve($this->manifest->directory, $this->manifest->entry_path);
            $entry_id = $this->current->find_file_id($entry);
            if ($entry_id === 0) {
                throw new \Exception("Entry is not a participating source file: " . $entry);
            }
            $this->current->set_entry_file($entry_id);
            $this->output = $this->current;
            $this->tasks = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Source_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Source_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function status(): \compile\step_status
    {
        return $this->state;
    }

    public function supports_run(): bool
    {
        return true;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Source_Discovery::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
