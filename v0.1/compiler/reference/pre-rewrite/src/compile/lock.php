<?php
declare(strict_types=1);

/*
 * Role: Own a project reservation across compilation or simulation.
 * Used by: Compiler_Session and Folder_Swap
 * Call map:
 *   Project_Lock::acquire()
 *     -> project_directory(); path_for_project(); [action] acquire OS lock
 */

namespace compile;

// Own the OS lock for one canonical project-input directory, independently of its name
// on the command line. The persistent sibling file stays put during folder swaps.
/**
 * @compiler-api Exclusive reservation for a canonical project directory, shared by session and
 * simulation. This is a stateful OS resource, not a worker snapshot. A lender owns
 * release; borrowers only require_project. The sibling lock file is never removed.
 */
class Project_Lock
{
    private string $project;

    /** @var resource|null */
    private $handle;

    private function __construct(string $project, $handle)
    {
        $this->project = $project;
        $this->handle = $handle;
    }

    /**
     * @compiler-api Acquire a nonblocking exclusive OS lock; creates/reuses the sibling file.
     * Throws on contention or filesystem failure; returned owner must release it.
     */
    public static function acquire(string $manifest_path): self
    {
        $project = self::project_directory($manifest_path);
        $path = self::path_for_project($project);

        // Never truncate or unlink this file: replacing its inode could admit a
        // second owner while another process still holds the original inode's lock.
        // Close on exec: compiler tools must not retain the owner's reservation.
        $handle = @fopen($path, 'ce');
        if ($handle === false) {
            throw new \Exception('Cannot open project lock: ' . $path);
        }
        $would_block = 0;
        if (!@flock($handle, LOCK_EX | LOCK_NB, $would_block)) {
            fclose($handle);
            if ($would_block) {
                throw new \Exception('Project is already being compiled: ' . $project);
            }
            throw new \Exception('Cannot acquire project lock: ' . $path);
        }
        return new self($project, $handle);
    }

    /** @compiler-api Resolve the existing manifest/source input to its canonical directory; throws if missing. */
    public static function project_directory(string $manifest_path): string
    {
        clearstatcache(true);
        $manifest = realpath($manifest_path);
        if ($manifest === false) {
            throw new \Exception('Cannot locate project input: ' . $manifest_path);
        }
        return dirname($manifest);
    }

    private static function path_for_project(string $project): string
    {
        return $project . '.scpp-compile.lock';
    }

    // A broader operation can lend its lock to a session, but cannot authorize a
    // different project or reuse an already released handle.
    /** @compiler-api Read-only check that this unreleased reservation covers the manifest directory; throws otherwise. */
    public function require_project(string $manifest_path): void
    {
        if ((!is_resource($this->handle)) || (self::project_directory($manifest_path) !== $this->project)) {
            throw new \Exception('No active lock for project: ' . $manifest_path);
        }
    }

    /** @compiler-api Canonical path reserved by this owner; supply it to native output protection. */
    public function path(): string
    {
        return self::path_for_project($this->project);
    }

    /** @compiler-api Owner-only, idempotent handle release; borrowers must not call it. */
    public function release(): void
    {
        if (is_resource($this->handle)) {
            // Release even if a just-forked tool has not reached exec yet.
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
        }
        $this->handle = null;
    }

    private function __clone()
    {
    }

    /** @compiler-internal Resource release fallback; use the explicit lifecycle methods for reported failures. */
    public function __destruct()
    {
        $this->release();
    }
}
