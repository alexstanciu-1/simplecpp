<?php
declare(strict_types=1);

/*
 * Role: Native_Builder phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] validate retained paths; select objects
 *   run() -> build_candidate() -> Native_Compiler::compile_batch(); Native_Join::join(); LLVM_Toolchain::link_objects()
 *   finalize() -> [action] complete unpublished Native_Candidate
 * Output: result() returns Native_Candidate after finalize().
 */

namespace build_native;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Native_Builder implements \compile\Step, \compile\Runnable_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Native_Candidate $output;
    private array $tasks = [];
    private \prepare_backend\link_configuration $link;
    private Native_Candidate $candidate;

    public function __construct(
        private readonly \emit_llvm\Emitted_Program $program,
        private readonly string $path,
        private readonly \prepare_backend\LLVM_Toolchain $toolchain,
        private readonly ?Native_Artifact $previous,
        private readonly bool $full_rebuild
    )
    {
    }

    /** Verify link configuration and select file modules requiring native object work. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            foreach ($this->previous?->objects ?? [] as $object) {
                if ($this->path === $object->path) {
                    throw new \InvalidArgumentException('Native output would replace retained object');
                }
            }
            $this->link = $this->toolchain->link_configuration($this->program->backend->configuration);
            $this->tasks = self::select($this->program, $this->previous, $this->full_rebuild);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Build or reuse the native candidate under the selected update inputs. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            $this->candidate = $this->build_candidate();
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Complete the native candidate handoff; executable publication remains session-owned. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = $this->candidate;
            $this->tasks = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Native_Candidate
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

    /** Reuse a valid executable or stage a replacement from joined objects, discarding private work on failure. */
    private function build_candidate(): Native_Candidate
    {
        // Skip linking only when both object inputs and the published executable remain valid.
        if ((!$this->full_rebuild) && ($this->tasks === []) && $this->previous?->is_current($this->program, $this->path, $this->link)) {
            return new Native_Candidate($this->previous, null);
        }

        // Stage a replacement beside the destination so publication can use one rename.
        $directory = dirname($this->path) . '/.scpp-native-' . bin2hex(random_bytes(12));
        if (!mkdir($directory, 0700)) {
            throw new \RuntimeException('Cannot create native staging directory');
        }
        $workspace = new Native_Workspace($directory);
        $results = [];
        try
        {
            $results = Native_Compiler::compile_batch($this->tasks, $this->toolchain);
            $objects = (new Native_Join($this->program, $this->previous, $this->tasks))->join($results);

            // Link the complete object set while retained objects remain owned by prior snapshots.
            $object_paths = array_map(static fn($o) => $o->path, $objects);
            $this->toolchain->link_objects($object_paths, $workspace->path('program'), $this->program->backend->configuration,
                $this->link, $this->program->backend->runtime);

            // Fingerprint the candidate before handing publication control back to the session.
            $key = hash_file('sha256', $workspace->path('program'));
            if ($key === false) {
                throw new \RuntimeException('Cannot fingerprint native executable');
            }
            return new Native_Candidate(new Native_Artifact($this->program, $this->path, $key, $objects, $this->link), $workspace, $results);
        }
        catch (\Throwable $error)
        {
            $warnings = $workspace->discard();
            foreach ($results as $object) {
                array_push($warnings, ...$object->discard());
            }
            if ($warnings !== []) {
                throw new \RuntimeException($error->getMessage() . "\n" . implode("\n", $warnings), 0, $error);
            }
            throw $error;
        }
    }

    /**
     * @compiler-internal Select file modules requiring real object work. Fixed program
     * and prior artifact are read-only; missing/tampered objects are stale too.
     * @return list<\emit_llvm\Emitted_Module>
     */
    private static function select(\emit_llvm\Emitted_Program $program, ?Native_Artifact $previous, bool $full): array
    {
        $tasks = [];
        foreach ($program->modules as $module) {
            if (($full) || (!$previous?->object_for($module->source_file_id)?->is_current($module))) {
                $tasks[] = $module;
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Native_Builder::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
