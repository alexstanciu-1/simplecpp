<?php
declare(strict_types=1);

/*
 * Role: Per-request stage outputs, input snapshots and debug export.
 * Used by: Compiler_Session; Phases; main.php
 * Flow: stage results -> Compile_Result -> observation/export
 */

namespace compile;

// Reports progress without presenting unfinished compilation as a completed build.
/**
 * @compiler-api Compile request result read by CLI, debug consumers and session publication.
 * All public fields are readable after compile returns; only compile writes them.
 * completed means native publication; inspection has LLVM but stops before native
 * building. Shared stage references keep their own contracts and identity domains.
 * The result/change catalog is not itself the retained generation.
 */
class Compile_Result
{
    public bool $completed = false;

    /** @var list<string> Nonfatal native cleanup warnings. */
    public array $warnings = [];
    public ?string $stopped_before;
    public ?\emit_llvm\Emitted_Program $llvm = null;
    public ?\build_native\Native_Artifact $native = null;
    public Input_Snapshot $inputs;
    public \collect_symbols\Symbol_Refresh $symbols;
    public \resolve_symbols\Resolution_Set $resolutions;
    public \resolve_types\Type_Resolution $types;
    public \check_bodies\Body_Set $bodies;
    public \analyze_lifetimes\Lifetime_Set $lifetimes;
    public \prepare_backend\Backend_Context $backend;
    public \lower\Lowered_Set $lowered;

    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(Input_Snapshot $inputs, string $stopped_before,
        \collect_symbols\Symbol_Refresh $symbols, \resolve_symbols\Resolution_Set $resolutions,
        \resolve_types\Type_Resolution $types, \check_bodies\Body_Set $bodies, \analyze_lifetimes\Lifetime_Set $lifetimes,
        \prepare_backend\Backend_Context $backend, \lower\Lowered_Set $lowered)
    {
        $this->inputs = $inputs;
        $this->stopped_before = $stopped_before;
        $this->symbols = $symbols;
        $this->resolutions = $resolutions;
        $this->types = $types;
        $this->bodies = $bodies;
        $this->lifetimes = $lifetimes;
        $this->backend = $backend;
        $this->lowered = $lowered;
    }

    /**
     * @compiler-api Inspection helper returning a task view for an analyzed symbol; throws when absent.
     * Does not select work, validate backend readiness or run lowering.
     */
    public function lowering_input_for(int $callable_id): \lower\lowering_input
    {
        $analysis = $this->lifetimes->for_callable($callable_id)
            ?? throw new \OutOfBoundsException('No analyzed body for lowering: ' . $callable_id);
        return new \lower\lowering_input($analysis, $this->backend);
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        return '{"completed":' . json_encode($this->completed, JSON_THROW_ON_ERROR)
            . ',"stopped_before":' . json_encode($this->stopped_before, JSON_THROW_ON_ERROR)
            . ',"inputs":' . $this->inputs->to_json()
            . ',"symbols":' . $this->symbols->to_json()
            . ',"resolutions":' . $this->resolutions->to_json()
            . ',"types":' . $this->types->to_json()
            . ',"bodies":' . $this->bodies->to_json()
            . ',"lifetimes":' . $this->lifetimes->to_json()
            . ',"backend":' . $this->backend->to_json()
            . ',"lowered":' . $this->lowered->to_json()
            . ',"llvm":' . json_encode($this->llvm?->to_array(), JSON_THROW_ON_ERROR)
            . ',"native":' . json_encode($this->native?->to_array(), JSON_THROW_ON_ERROR)
            . ',"warnings":' . json_encode($this->warnings, JSON_THROW_ON_ERROR) . '}';
    }
}

// Observed input state only; this is not a published compiler generation.
/**
 * @compiler-api Shared frontend input bundle: context, manifest, sources, tokens, frontends.
 * Compile assembles it and fills all fields before handoff. Consumers only read;
 * context records the update decision, not a reusable command to start another stage.
 * Construction supplies empty token/frontend sets, not a complete input snapshot.
 */
class Input_Snapshot
{
    /** Configured runtime inputs exclude dynamically demanded specialization packages. */
    public ?\load_runtime\Runtime_Input_Set $runtime = null;
    public Update_Context $context;
    public \read_manifest\Project_Manifest $manifest;
    public \read_sources\Source_Set $sources;
    public \tokenize\Token_Set $tokens;
    public \parse\Frontend_Set $frontends;

    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct()
    {
        $this->tokens = new \tokenize\Token_Set();
        $this->frontends = new \parse\Frontend_Set();
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $full = "false";
        if ($this->context->full_rebuild) {
            $full = "true";
        }
        return "{\"full_rebuild\":" . $full . ",\"manifest\":" . $this->manifest->to_json()
            . ",\"sources\":" . $this->sources->to_json()
            . ",\"tokens\":" . $this->tokens->to_json()
            . ',"configured_runtime":' . json_encode($this->runtime?->to_array(), JSON_THROW_ON_ERROR)
            . ",\"frontends\":" . $this->frontends->to_json() . "}";
    }
}

/**
 * @compiler-internal Compile-local transfer of matching sources and tokens from run_tokenization.
 * Both fields reference completed stage outputs; no consumer may mutate them.
 */
class Tokenization_Phase_Result
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public \read_sources\Source_Set $sources,
        public \tokenize\Token_Set $tokens
    )
    {
    }
}
