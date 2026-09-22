<?php
declare(strict_types=1);

/*
 * Role: Native artifacts, object lifetimes and candidate publication.
 * Used by: Native_Builder; Compiler_Session::publish()
 * Call map:
 *   Native_Candidate::publish()
 *     -> [action] rename prepared executable, clean staging, return warnings
 */
namespace build_native;

/**
 * @compiler-api Read-only artifact: program, path, content_key, shared objects and link contract, produced by native build.
 * The path denotes the destination; during preparation bytes may still be staged.
 * Construction alone does not prove publication or file existence. Compile retains
 * this object only after candidate publication; consumers must not edit it.
 */
class Native_Artifact
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly \emit_llvm\Emitted_Program $program,
        public readonly string $path,
        public readonly string $content_key,
        public readonly array $objects,
        public readonly \prepare_backend\link_configuration $link,
    )
    {
        $by_file = [];
        foreach ($objects as $object) {
            $id = $object->module->source_file_id;
            if ((isset($by_file[$id])) || ($program->module_for($id) !== $object->module)) {
                throw new \LogicException('Stale or duplicate artifact object');
            }
            $by_file[$id] = $object;
        }
        if (count($by_file) !== count($program->modules)) {
            throw new \LogicException('Incomplete artifact object set');
        }
        $this->by_file = $by_file;
    }
    private readonly array $by_file;

    /** @compiler-api Shared object owner by source-file ID; null means absent. */
    public function object_for(int $id): ?Native_Object
    {
        return $this->by_file[$id] ?? null;
    }

    /**
     * @compiler-api Read filesystem state and verify exact program identity, link contract, destination, executable file
     * and content hash. False means rebuild is needed, not permission to reuse stale output.
     */
    public function is_current(\emit_llvm\Emitted_Program $program, string $path, \prepare_backend\link_configuration $link): bool
    {
        clearstatcache(true, $path);
        return ($this->program === $program) && ($this->path === $path) && ($this->link == $link) && is_file($path) && is_executable($path)
            && (hash_file('sha256', $path) === $this->content_key);
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_array(): array
    {
        return ['path' => $this->path, 'sha256' => $this->content_key, 'linker' => $this->link, 'objects' => array_map(static fn($o) => $o->to_array(), $this->objects)];
    }
}

// A verified artifact plus the private workspace awaiting publication.
/**
 * @compiler-api Native_Builder output read by compile: artifact is the candidate description.
 * Owns a private workspace until publication/abandonment; not a reusable worker input.
 * Only the session publication coordinator may publish while holding its reservation.
 */
class Native_Candidate implements \compile\Step_Result
{
    private bool $finished = false;

    /**
     * @compiler-internal Native builder only; owns rollback until successful publication.
     * @param list<Native_Object> $new_objects Only this candidate's new files, never reused ones.
     */
    public function __construct(public readonly Native_Artifact $artifact, private readonly ?Native_Workspace $workspace,
        private readonly array $new_objects = [])
    {
    }

    /**
     * @compiler-api Single publication step: rename the staged executable to artifact.path, then clean
     * private files. A reused artifact needs no rename. Failure before rename throws;
     * cleanup warnings after success do not roll back publication. Caller prepares all
     * in-memory snapshots before this operation; do not publish a candidate twice.
     * @return list<string> Warnings after successful publication; never a false rollback.
     */
    public function publish(): array
    {
        if ($this->finished) {
            throw new \LogicException('Native candidate already finished');
        }
        if ($this->workspace === null) {
            $this->finished = true;
            return [];
        }
        try {
            if (!@rename($this->workspace->path('program'), $this->artifact->path)) {
                throw new \RuntimeException('Cannot publish native executable: ' . $this->artifact->path);
            }
        }
        catch (\Throwable $error)
        {
            $warnings = $this->workspace->discard();
            foreach ($this->new_objects as $object) {
                array_push($warnings, ...$object->discard());
            }
            $this->finished = true;
            if ($warnings !== []) {
                throw new \RuntimeException($error->getMessage() . "\n" . implode("\n", $warnings), 0, $error);
            }
            throw $error;
        }
        $this->finished = true;
        return $this->workspace->discard();
    }

    /** @compiler-internal Abandoned candidates release only their new objects; workspace owns executable cleanup. */
    public function __destruct()
    {
        if (!$this->finished) {
            foreach ($this->new_objects as $object) {
                foreach ($object->discard() as $warning) {
                    error_log($warning);
                }
            }
        }
    }
}

/**
 * @compiler-api Shared owner of one immutable object file and its exact emitted
 * input. Native build creates/seals it; consumers may read module/path and query
 * validity. Accepted snapshots share the owner, so the last reference releases
 * its file. No persistent cache, cycle, or session back-reference is introduced.
 */
final class Native_Object
{
    /** @compiler-internal Native worker only: reserve a private path before construction. */
    public function __construct(public readonly \emit_llvm\Emitted_Module $module, public readonly string $path)
    {
    }
    private string $content_key = '';
    private bool $discarded = false;

    /** @compiler-internal Worker completion; called once after successful object compilation. */
    public function seal(): void
    {
        if (($this->content_key !== '') || ($this->discarded)) {
            throw new \LogicException('Native object already finished');
        }
        $key = hash_file('sha256', $this->path);
        if ($key === false) {
            throw new \RuntimeException('Cannot fingerprint native object');
        }
        $this->content_key = $key;
    }

    /** @compiler-api Exact module identity and owned-file integrity; filesystem reads only. */
    public function is_current(\emit_llvm\Emitted_Module $module): bool
    {
        clearstatcache(true, $this->path);
        return (!$this->discarded) && ($this->module === $module) && ($this->content_key !== '')
            && is_file($this->path) && (!is_link($this->path)) && (hash_file('sha256', $this->path) === $this->content_key);
    }

    /** @compiler-internal Explicit rollback for unaccepted files; never discard a shared accepted object. */
    public function discard(): array
    {
        if ($this->discarded) {
            return [];
        }
        clearstatcache(true, $this->path);
        if ((file_exists($this->path) || is_link($this->path)) && (!@unlink($this->path))) {
            return ['Native cleanup warning: cannot remove ' . $this->path];
        }
        $this->discarded = true;
        return [];
    }

    /** @compiler-api On-demand debug description; paths live only as long as their owning snapshots. */
    public function to_array(): array
    {
        return ['source_file_id' => $this->module->source_file_id, 'path' => $this->path, 'sha256' => $this->content_key];
    }

    private function __clone()
    {
    }

    /** @compiler-internal Last-reader file cleanup, without ownership cycles. */
    public function __destruct()
    {
        foreach ($this->discard() as $warning) {
            error_log($warning);
        }
    }
}
