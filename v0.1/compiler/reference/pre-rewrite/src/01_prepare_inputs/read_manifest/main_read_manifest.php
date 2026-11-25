<?php
declare(strict_types=1);

/*
 * Role: Prepare a disk or virtual project manifest; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] resolve project input path
 *   run() -> [action] select a single source, or read JSON through Manifest_Syntax::parse()
 *   finalize() -> [action] complete Project_Manifest
 * Output: result() returns Project_Manifest after finalize().
 */

namespace read_manifest;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Manifest_Reader implements \compile\Step, \compile\Runnable_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Project_Manifest $output;
    private string $resolved;
    private Project_Manifest $candidate;

    public function __construct(
        private readonly string $path
    )
    {
    }

    /** Resolve the project input path before reading a manifest or single source. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            clearstatcache(true);
            $resolved = realpath($this->path);
            if ($resolved === false) {
                throw new \Exception("Cannot locate project input: " . $this->path);
            }
            $this->resolved = $resolved;
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Build a private manifest from the selected source path or validated JSON. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            if (\read_sources\Source_Paths::is_source($this->path))
            {
                if (!is_file($this->resolved)) {
                    throw new \Exception("Source path is not a regular file: " . $this->path);
                }
                $manifest = new Project_Manifest();
                $manifest->path = $this->path;
                $manifest->content = null;
                $manifest->source_file_paths = [basename($this->resolved)];
                $manifest->entry_path = basename($this->resolved);
            }
            else
            {
                $content = is_file($this->resolved) ? @file_get_contents($this->resolved) : false;
                if ($content === false) {
                    throw new \Exception("Cannot read project manifest: " . $this->path);
                }
                try {
                    $manifest = Manifest_Syntax::parse($this->path, $content);
                }
                catch (\Exception $exception) {
                    throw new \Exception($this->path . ": " . $exception->getMessage(), 0, $exception);
                }
            }
            $manifest->directory = dirname($this->resolved);
            $this->candidate = $manifest;
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Expose the successfully prepared manifest and finish this phase. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = $this->candidate;
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Project_Manifest
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
            throw new \LogicException('Manifest_Reader::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
