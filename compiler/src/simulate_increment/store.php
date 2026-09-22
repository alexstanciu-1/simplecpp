<?php
declare(strict_types=1);

/*
 * Role: Own swap paths, project locks and recovery journal.
 * Used by: Simulation::run()
 * Call map:
 *   Folder_Swap::begin(); park_original(); install_edited(); finish()
 *     -> [action] journal transitions and restore trees on exit
 */

namespace simulate_increment;

use compile\Project_Lock;

// Own a reversible directory swap and its process-crash recovery journal.
// Use one simulation at a time; these directories require exclusive user access.
/**
 * @compiler-internal Simulation-owned mutable paths, reservation and recovery journal state.
 * Simulation prepares public fields before begin; compiler steps must not read/write
 * these paths or drive this lifecycle. Only the borrowed Project_Lock crosses into
 * Compiler_Session; this owner retains responsibility for its release.
 */
class Folder_Swap
{
    private ?Project_Lock $project_lock = null;
    private ?Project_Lock $edited_lock = null;
    public string $project = "";
    public string $edited = "";
    public string $backup = "";
    public string $journal_directory = "";
    public string $journal = "";
    public string $manifest_path = "";
    private bool $original_parked = false;
    private bool $edited_installed = false;

    /** @compiler-internal Reserve both projects and create the recovery journal; throw on conflicting state/I/O failure. */
    public function begin(): void
    {
        if ($this->project_lock !== null) {
            throw new \Exception('Simulation has already begun');
        }
        $this->project_lock = Project_Lock::acquire($this->manifest_path);
        try
        {
            $this->edited_lock = Project_Lock::acquire($this->edited . '/' . basename($this->manifest_path));
            clearstatcache(true);

            // Exclusive creation also refuses a journal left by an earlier process.
            if (!@mkdir($this->journal_directory)) {
                throw new \Exception("Cannot reserve simulation journal; inspect: " . $this->journal_directory);
            }
            $note = [];
            $note["stage"] = "prepared";
            $note["project"] = $this->project;
            $note["edited"] = $this->edited;
            $note["backup"] = $this->backup;
            $note["recovery"] = "Stop the simulation process before recovery. Inspect actual paths: an intent record may precede or follow its rename. If backup and project exist and edited is absent, rename project back to edited first. If backup and edited exist and project is absent, rename backup back to project. Never overwrite an existing path. If project and edited exist and backup is absent, restoration is complete. Otherwise stop and inspect. Remove this journal and its empty directory only after restoration. A partial final line does not invalidate earlier complete records.";
            $this->append_record($note);
        }
        catch (\Throwable $exception) {
            $this->release_locks();
            throw $exception;
        }
    }

    /** @compiler-internal Lend the active project reservation to Simulation's compiler session; borrower must not release it. */
    public function project_lock(): Project_Lock
    {
        if ($this->project_lock === null) {
            throw new \Exception('Simulation has no active project lock');
        }
        return $this->project_lock;
    }

    /** @compiler-internal Append/flush a stage note to this swap journal; throws on I/O failure. */
    public function record(string $stage): void
    {
        $entry = [];
        $entry["stage"] = $stage;
        $this->append_record($entry);
    }

    /** @compiler-internal Journal and rename original into backup; throw rather than overwrite. */
    public function park_original(): void
    {
        $this->rename_folder($this->project, $this->backup, "before_park_original");
        $this->original_parked = true;
        $this->record("original_parked");
    }

    /** @compiler-internal Journal and rename edited tree into project; throw rather than overwrite. */
    public function install_edited(): void
    {
        $this->rename_folder($this->edited, $this->project, "before_install_edited");
        $this->edited_installed = true;
        $this->record("edited_installed");
    }

    /** @compiler-internal Journal and rename installed edited tree back to its original path. */
    public function return_edited(): void
    {
        $this->rename_folder($this->project, $this->edited, "before_return_edited");
        $this->edited_installed = false;
        $this->record("edited_returned");
    }

    /** @compiler-internal Journal and restore the parked original tree; throw rather than overwrite. */
    public function restore_original(): void
    {
        $this->rename_folder($this->backup, $this->project, "before_restore_original");
        $this->original_parked = false;
        $this->record("original_restored");
    }

    /**
     * @compiler-internal Restore moved trees, remove completed journal and release reservations.
     * On restoration/journal failure throw; leave recovery evidence for manual inspection.
     */
    public function finish(): void
    {
        try
        {
            if ($this->edited_installed) {
                $this->return_edited();
            }
            if ($this->original_parked) {
                $this->restore_original();
            }
            $this->record("restored");
            if ((!@unlink($this->journal)) || (!@rmdir($this->journal_directory))) {
                throw new \Exception("Folders restored; cannot remove simulation journal directory: " . $this->journal_directory);
            }
        }
        finally {
            $this->release_locks();
        }
    }

    private function release_locks(): void
    {
        $this->edited_lock?->release();
        $this->project_lock?->release();
        $this->edited_lock = null;
        $this->project_lock = null;
    }

    /** Append and flush journal bytes, reporting incomplete writes before continuing the filesystem update. */
    private function append(string $text): void
    {
        $handle = @fopen($this->journal, "ab");
        if ($handle === false) {
            throw new \Exception("Cannot open simulation journal: " . $this->journal);
        }
        try {
            $written = @fwrite($handle, $text);
            $flushed = @fflush($handle);
        }
        finally {
            $closed = fclose($handle);
        }
        if (($written !== strlen($text)) || (!$flushed) || (!$closed)) {
            throw new \Exception("Cannot persist simulation stage; inspect: " . $this->journal);
        }
    }

    /** Encode one journal record and append its newline-delimited representation. */
    private function append_record(mixed $entry): void
    {
        try {
            $text = json_encode($entry, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $exception) {
            throw new \Exception("Cannot encode simulation journal record: " . $exception->getMessage(), 0, $exception);
        }
        $this->append($text . "\n");
    }

    /** Reject an occupied destination and journal the rename before changing the filesystem. */
    private function rename_folder(string $from, string $to, string $stage): void
    {
        clearstatcache(true);
        if ((!is_dir($from)) || file_exists($to) || is_link($to)) {
            throw new \Exception("Cannot rename simulation folder without overwriting; inspect: " . $this->journal);
        }
        $this->record($stage);
        if (!@rename($from, $to)) {
            throw new \Exception("Simulation rename failed: " . $from . " -> " . $to . "; recovery note: " . $this->journal);
        }
    }
}
