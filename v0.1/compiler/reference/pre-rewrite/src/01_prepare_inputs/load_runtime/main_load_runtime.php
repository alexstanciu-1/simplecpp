<?php
declare(strict_types=1);

/*
 * Role: Language_Types phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] resolve catalog path
 *   run() -> reuse catalog or Catalog_Syntax::parse()
 *   finalize() -> [action] complete Type_Catalog
 * Output: result() returns Type_Catalog after finalize().
 */

namespace load_runtime;

use type_model\Type_Catalog;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Language_Types implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Type_Catalog $output;
    private string $resolved_path;
    private Type_Catalog $candidate;

    public function __construct(
        private readonly ?string $path = null,
        private readonly ?Type_Catalog $previous = null
    )
    {
    }

    /** Resolve the configured language catalog path before ingestion. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try {
            $this->resolved_path = self::input_path($this->path);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Reuse the unchanged catalog or parse a new private catalog from its exact bytes. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            $content = @file_get_contents($this->resolved_path);
            if ($content === false) {
                throw new \RuntimeException('Cannot read language type catalog: ' . $this->resolved_path);
            }
            if (($this->previous !== null) && (hash('sha256', $content) === $this->previous->content_key)) {
                $this->candidate = $this->previous;
            }
            else
            {
                try {
                    $this->candidate = Catalog_Syntax::parse($content);
                }
                catch (\Exception $error) {
                    throw new \RuntimeException('Invalid language type catalog: ' . $this->resolved_path . ': ' . $error->getMessage(), 0, $error);
                }
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Expose the accepted language catalog and finish this phase. */
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

    public function result(): Type_Catalog
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Type_Catalog
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

    /** @compiler-api Read configured/default catalog location for ingestion and native input protection; no I/O. */
    public static function input_path(?string $path = null): string
    {
        return $path ?? __DIR__ . '/../../../language/named_types.json';
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Language_Types::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
