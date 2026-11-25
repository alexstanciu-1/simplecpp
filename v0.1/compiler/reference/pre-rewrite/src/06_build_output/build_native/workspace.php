<?php
declare(strict_types=1);

/*
 * Role: Own private native staging files.
 * Used by: Native_Builder; Native_Candidate
 * Call map: Native_Workspace::path(); discard(); __destruct() [abandoned staging]
 */

namespace build_native;

// Own this attempt's private files, never the published destination. Cleanup is
// explicit at failure/publication; the destructor also covers abandoned candidates.
/**
 * @compiler-internal Native-attempt file owner used only within build_native; not exposed to compile
 * or semantic workers. Owns private staging files, never the published destination.
 */
class Native_Workspace
{
    private bool $discarded = false;
    private bool $cleanup_attempted = false;

    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(private readonly string $directory)
    {
    }

    /** @compiler-internal Resolve an owner-controlled staging filename; not a general untrusted-path API. */
    public function path(string $name): string
    {
        return $this->directory . '/' . $name;
    }

    /**
     * @compiler-internal Idempotently attempt cleanup of known private outputs and directory; return warnings
     * on failure. Do not recursively delete unknown contents; failed cleanup is retryable.
     * @return list<string> Cleanup warnings are separate from publication success.
     */
    public function discard(): array
    {
        $this->cleanup_attempted = true;
        if ($this->discarded) {
            return [];
        }
        $warnings = [];
        foreach (['program'] as $name) {
            $path = $this->path($name);
            clearstatcache(true, $path);
            if ((file_exists($path) || is_link($path)) && (!@unlink($path))) {
                $warnings[] = 'Native cleanup warning: cannot remove ' . $path;
            }
        }
        clearstatcache(true, $this->directory);
        if ((!file_exists($this->directory)) && (!is_link($this->directory))) {
            $this->discarded = true;
        }
        elseif (@rmdir($this->directory)) {
            $this->discarded = true;
        }
        else {
            $warnings[] = 'Native cleanup warning: cannot remove directory ' . $this->directory;
        }

        // A failed attempt stays retryable. Never recurse into unknown contents.
        return $warnings;
    }

    /** @compiler-internal Resource release fallback; use the explicit lifecycle methods for reported failures. */
    public function __destruct()
    {
        // Explicit callers already report their warnings. Abandoned candidates
        // have no result recipient, so report fallback cleanup through PHP's log.
        if (!$this->cleanup_attempted) {
            foreach ($this->discard() as $warning) {
                error_log($warning);
            }
        }
    }
}
