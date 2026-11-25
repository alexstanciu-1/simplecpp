<?php
declare(strict_types=1);

/*
 * Role: Coordinate updates and retain observed/published snapshots.
 * Used by: main.php; Simulation::run()
 * Call map:
 *   Compiler_Session::compile()
 *     -> [action] prepare/reuse stages; finish()
 */

namespace compile;

use collect_symbols\Declaration_Collector;
use read_manifest\Manifest_Reader;
use read_sources\Source_Reader;

/**
 * @compiler-api Resident compilation entry point and owner of accepted snapshots.
 * Call compile(); read observed, published and generation after it returns.
 * Those public properties are observation APIs, not caller-writable session state.
 *
 * Convention: @compiler-api marks cross-process access; @compiler-internal marks
 * implementation-only access even where PHP visibility is public. Tags apply to
 * the documented declaration. Record/class comments define readable fields and
 * construction rules; methods document their own narrower access. Private members
 * remain internal. Untagged declarations outside prototype/src are not audited.
 * Shared objects/rows are read-only by agreement after handoff, including nested
 * objects: PHP readonly does not enforce deep immutability. Dataset mutators are restricted
 * to the named owner and a private candidate; numeric IDs retain their stated scope.
 * Comments describe existing guarantees, not extra validation or new capabilities.
 */
class Compiler_Session
{
    // Optional borrowed reservation for a larger operation, such as a folder swap.
    private ?Project_Lock $project_lock;
    private bool $compiling = false;
    private readonly \prepare_backend\LLVM_Toolchain $toolchain;

    // Observation includes every successful stage, without claiming publication.
    // After publication, builds compare against that published snapshot.
    public ?Compiler_Snapshot $observed = null;
    public ?Compiler_Snapshot $published = null;
    public int $generation = 0;

    /**
     * @compiler-api Create a resident session with optional borrowed project reservation and input paths.
     * runtime_package_path accepts one directory or a nonempty list; null disables runtime import.
     * family_declarations is a fixed normalized semantic input. An optional family_preparer
     * services accepted concrete demands at coordinator boundaries, outside semantic workers.
     * @param list<\type_model\family_declaration> $family_declarations
     * A borrowed lock must remain active for every compile request using it.
     */
    public function __construct(?Project_Lock $project_lock = null,
        private readonly ?string $type_catalog_path = null, ?string $backend_toolchain_path = null,
        private readonly string|array|null $runtime_package_path = null, private readonly ?string $instantiation_policy_path = null,
        private readonly array $family_declarations = [], private readonly ?\load_runtime\Family_Preparer $family_preparer = null,
        private readonly ?native_project $native_project = null)
    {
        $this->toolchain = new \prepare_backend\LLVM_Toolchain($backend_toolchain_path);
        $this->project_lock = $project_lock;
    }

    /**
     * @compiler-api Run the common pipeline under the project reservation; no reentrant call.
     * The input path accepts a JSON manifest or a .phs file (virtual project).
     * Null output_path means inspect unless default_output requests target-aware naming. A native
     * request prepares and publishes the executable, then adopts published/observed
     * and advances generation. The executable is never run here.
     * Failure throws and preserves accepted snapshots; private candidate work is not
     * published. Input reads, tool probes and temporary files can already have occurred.
     * A borrowed lock stays with its owner; a lock acquired here is released on exit.
     */
    public function compile(string $manifest_path, ?string $output_path = null, bool $default_output = false): Compile_Result
    {
        if ($this->compiling) {
            throw new \Exception('This compiler session is already compiling');
        }
        $lock = $this->project_lock ?? Project_Lock::acquire($manifest_path);
        $this->compiling = true;
        $runtime_lease = null;
        try
        {
            $lock->require_project($manifest_path);

            // Before publication exists, retain observed inputs for discovery comparisons.
            // Once a generation is published, comparisons use that published baseline.
            $previous = $this->published ?? $this->observed;
            $initial = $previous === null;
            $previous_manifest = $previous?->inputs->manifest ?? new \read_manifest\Project_Manifest();
            $previous_sources = $previous?->inputs->sources ?? new \read_sources\Source_Set();
            $previous_tokens = $previous?->inputs->tokens ?? new \tokenize\Token_Set();
            $previous_frontends = $previous?->inputs->frontends ?? new \parse\Frontend_Set();
            $previous_symbols = $previous?->symbols ?? new \collect_symbols\Symbol_Store();
            $previous_resolutions = $previous?->resolutions ?? new \resolve_symbols\Resolution_Set();
            $previous_types = $previous?->types;
            $previous_bodies = $previous?->bodies ?? new \check_bodies\Body_Set();
            $previous_lifetimes = $previous?->lifetimes ?? new \analyze_lifetimes\Lifetime_Set();
            $previous_backend = $previous?->backend;
            $previous_lowered = $previous?->lowered ?? new \lower\Lowered_Set();
            $previous_llvm = $previous?->llvm;

            // 1. Prepare the disk or virtual manifest; configuration changes request a full rebuild.
            $step = new Manifest_Reader($manifest_path);
            $step->init();
            $step->run();
            $step->finalize();
            $manifest = $step->result();
            $update = Input_Selection::select($previous_manifest, $manifest, $initial);

            // Read fixed language definitions and a reserved provider snapshot before collection.
            $step = new \load_runtime\Language_Types($this->type_catalog_path, $previous?->inputs->runtime?->base_catalog ?? $previous_types?->catalog);
            $step->init();
            $step->run();
            $step->finalize();
            $catalog = $step->result();
            $runtime = null;
            if ($this->runtime_package_path !== null) {
                $runtime_lease = \load_runtime\Runtime_Import::open(is_array($this->runtime_package_path) ? $this->runtime_package_path
                    : [$this->runtime_package_path], $catalog, $previous?->inputs->runtime);
                $runtime = $runtime_lease->inputs;
                $catalog = $runtime->catalog;
            }
            \load_runtime\Family_Adapter::validate_exposures($this->family_declarations, $catalog);
            if ($runtime !== $previous?->inputs->runtime) {
                $update->full_rebuild = true;
            }

            // 2. Discover participating files and classify additions, changes and removals.
            $step = new \read_sources\Source_Discovery($manifest, $previous_sources);
            $step->init();
            $step->run();
            $step->finalize();
            $sources = $step->result();
            if (count($sources->removed_file_ids) > 0) {
                $update->full_rebuild = true;
            }

            // Establish cache validity before frontend workers read the full flag.
            $instance_limit = \instantiate\Instantiation_Policy::load($this->instantiation_policy_path);
            $type_context = new \type_model\type_context(
                hash('sha256', json_encode([$manifest->path, $manifest->directory, $manifest->content, $instance_limit], JSON_THROW_ON_ERROR)),
                $catalog->content_key, $catalog->representation_scope);
            if (\resolve_types\Type_Cache::requires_rebuild($previous_types?->types, $type_context)) {
                $update->full_rebuild = true;
            }

            // A published generation already establishes every semantic contract.
            // Skip semantic traversal only after complete input discovery and current
            // language/target checks. Native repair still uses the common finish path.
            // Configured native preparation must check its own inputs before this reuse is safe.
            if (($this->family_preparer === null) && (!$update->full_rebuild) && ($this->published !== null) && (!$sources->has_pending_changes())
                && ($catalog === $previous_types?->catalog)
                && ($this->toolchain->configuration() === $previous_backend?->configuration))
            {
                $inputs = clone $previous->inputs;
                $inputs->context = $update;
                $inputs->manifest = $manifest;
                $inputs->sources = $sources;
                $result = new Compile_Result($inputs, 'build_native',
                    \collect_symbols\Symbol_Refresh::unchanged($previous_symbols), $previous_resolutions,
                    $previous_types, $previous_bodies, $previous_lifetimes, $previous_backend, $previous_lowered);
                $result->llvm = $previous_llvm;
                return $this->finish($result, $output_path, $lock, $default_output);
            }

            // Unsupported contract changes repeat the same frontend stages with full selection.
            do
            {
                // 3. Read selected source snapshots, then tokenize selected snapshots.
                $lexical = Phases::run_tokenization($sources, $previous_tokens, $update);
                $inputs = new Input_Snapshot();
                $inputs->context = $update;
                $inputs->manifest = $manifest;
                $inputs->sources = $lexical->sources;
                $inputs->tokens = $lexical->tokens;

                // Parse selected token snapshots, then join complete file frontends.
                $inputs->frontends = Phases::run_parsing($lexical->sources, $lexical->tokens,
                    $previous_frontends, $update);

                // 4. Collect file declarations, then reconcile the candidate project index.
                $collection = new Declaration_Collector($previous_symbols, $inputs->sources,
                    $inputs->frontends, $update->full_rebuild, $runtime, $this->family_declarations);
                $collection->init();
                $collection->run();
                $collection->finalize();
                $symbols = $collection->result();

                // Select the manifest entry and reject unsupported initializer ordering.
                $entry_step = new \resolve_types\Entry_Resolver($inputs->sources, $symbols->current, $catalog);
                $entry_step->init();
                $entry_step->run();
                $entry_step->finalize();
                $entry = $entry_step->result();

                // Bind declaration and body names against fixed project/catalog inputs.
                $resolutions = Phases::run_symbols($symbols->current, $previous_resolutions, $update, $catalog);

                // Compare logical syntax without changing the current symbols or ASTs.
                $comparison = new \collect_symbols\Symbol_Comparer($symbols, $update->full_rebuild);
                $comparison->init();
                $comparison->run();
                $comparison->finalize();
                $symbols = $comparison->result();
                if (($update->full_rebuild) || Input_Selection::supports_increment($symbols)) {
                    break;
                }
                $update->full_rebuild = true;
            }
            while (true);

            $inputs->runtime = $runtime;
            $families = new \load_runtime\Family_Preparation($this->family_preparer,
                $runtime?->base_catalog ?? $catalog, $update->full_rebuild ? [] : ($previous_types?->families ?? []),
                $runtime?->packages() ?? []);

            // Preparation may replace specialization packages. Release early reads before native work.
            if ($this->family_preparer !== null) {
                $runtime_lease?->release();
                $runtime_lease = null;
            }

            // Resolve return/local annotations, then finish the fixed type snapshot.
            $type_store = \resolve_types\Type_Cache::prepare($previous_types?->types, $type_context, $update->full_rebuild);
            $layouts = new \prepare_backend\Layout_Coordinator($this->toolchain,
                $previous_backend?->layouts ?? [], $update->full_rebuild);
            $exports = new \prepare_backend\Source_Export_Coordinator($this->native_project, $layouts,
                $this->toolchain->configuration(), $runtime?->base_catalog ?? $catalog, $runtime, $update->full_rebuild,
                \prepare_backend\Source_Export_Coordinator::retained($previous_types?->families ?? []));
            $type_resolver = new \resolve_types\Type_Resolver($symbols->current, $catalog, $type_store,
                $previous_types, $update->full_rebuild, $entry, $resolutions, $instance_limit, $families, $layouts, $exports);
            $type_resolver->init();
            $type_resolver->run();
            $type_resolver->finalize();
            $types = $type_resolver->result();

            // Revalidate all accepted packages together and retain readers through native linking.
            if ($this->family_preparer !== null)
            {
                $packages = array_values($runtime?->packages() ?? []);
                foreach ($types->families as $prepared) {
                    $packages[] = $prepared->package;
                }
                if ($packages !== []) {
                    $runtime_lease = \load_runtime\Runtime_Import::reserve($packages, $previous_backend?->runtime);
                    $runtime = $runtime_lease->inputs;
                }
            }

            // Check all resolved callable bodies using one statement/value path.
            $bodies = Phases::run_bodies($symbols->current, $resolutions, $types, $previous_bodies, $update);

            // Analyze reachable value lifetimes against those fixed checked bodies.
            $lifetimes = Phases::run_lifetimes($bodies, $previous_lifetimes, $update, $types->types);

            // Provider preparation reads verified target facts and joins complete
            // bindings. Backend changes invalidate lowering, not language-value types.
            $backend_step = new \prepare_backend\LLVM_Backend($this->toolchain, $types, $previous_backend, $update->full_rebuild, $runtime, $layouts, $bodies, $lifetimes);
            $backend_step->init();
            $backend_step->run();
            $backend_step->finalize();
            $backend = $backend_step->result();

            // Lower selected callable bodies against fixed analysis and backend inputs.
            $lowered = Phases::run_lowering($lifetimes, $backend, $previous_lowered, $update);

            // Adapt the manifest entry to the native ABI, then assemble the emitted program.
            $entry_binding = $backend->binding_for($types->entry->symbol->symbol_id)
                ?? throw new \LogicException('Missing native entry binding');
            $entry_step = new \lower\Native_Entry($entry_binding,
                $this->toolchain->native_return_bits($backend->configuration), $previous_llvm?->entry);
            $entry_step->init();
            $entry_step->run();
            $entry_step->finalize();
            $entry_plan = $entry_step->result();
            $llvm = Phases::run_emission($lowered, $backend, $entry_plan, $previous_llvm, $update);
            $result = new Compile_Result($inputs, 'build_native', $symbols, $resolutions, $types, $bodies, $lifetimes, $backend, $lowered);
            $result->llvm = $llvm;
            return $this->finish($result, $output_path, $lock, $default_output);
        }
        finally {
            $runtime_lease?->release();
            $this->compiling = false;
            if ($this->project_lock === null) {
                $lock->release();
            }
        }
    }

    /**
     * One native selection/repair/publication path after either prepared or reused LLVM.
     */
    private function finish(Compile_Result $result, ?string $output_path, Project_Lock $lock, bool $default_output): Compile_Result
    {
        if (($output_path === null) && ($default_output)) {
            $output_path = \build_native\Native_Paths::default_output($result->inputs->sources, $result->backend->configuration);
        }
        if ($output_path !== null)
        {
            $this->toolchain->link_configuration($result->backend->configuration);
            $destination = \build_native\Native_Paths::destination($output_path, $result->inputs->manifest, $result->inputs->sources,
                [$lock->path(), \load_runtime\Language_Types::input_path($this->type_catalog_path),
                    \instantiate\Instantiation_Policy::input_path($this->instantiation_policy_path), ...$this->toolchain->input_paths(),
                    ...($result->backend->runtime?->protected_paths() ?? [])]);
            $native_step = new \build_native\Native_Builder($result->llvm, $destination, $this->toolchain, $this->published?->native, $result->inputs->context->full_rebuild);
            $native_step->init();
            $native_step->run();
            $native_step->finalize();
            $candidate = $native_step->result();
            $this->publish($result, $candidate);
        }
        else {
            $this->observed = Compiler_Snapshot::from_result($result);
        }
        return $result;
    }

    /** Prepare retained snapshots before publishing the native candidate and advancing the accepted generation. */
    private function publish(Compile_Result $result, \build_native\Native_Candidate $candidate): void
    {
        // Prepare acknowledged file rows before the single external publication.
        // Prior snapshots keep their own flags and removal observations.
        $sources = $result->inputs->sources->acknowledged();
        $inputs = clone $result->inputs;
        $inputs->sources = $sources;
        $snapshot = Compiler_Snapshot::from_result($result, $inputs, $candidate->artifact);
        $observation = Compiler_Snapshot::from_result($result, native: $candidate->artifact);

        // Finish all snapshot preparation before the external publication.
        $result->warnings = $candidate->publish();
        $result->native = $candidate->artifact;
        $result->completed = true;
        $result->stopped_before = null;
        $this->published = $snapshot;
        $this->observed = $observation;
        ++$this->generation;
    }

    // Deferred maintenance boundary, not required for the first real increment.
    private function cleanup(): void
    {
        // TODO: ask each store owner to reclaim rows marked deleted/removed and
        // obsolete payloads after no phase reader needs them. Repair indexes and
        // snapshot-local references if rows move; preserve live logical identities.
        // Removal bookkeeping is acknowledged during publication, independently of
        // this deferred physical reclamation.
        // Logical exclusion from current lookup/work happens during the update;
        // this later cleanup reclaims storage through the same full/selective path.
        throw new \Exception("Not implemented: cleanup_compiler_update");
    }
}

// A coherent retained generation of stage results. This groups shared references;
// it does not retain change catalogs or copy AST/semantic datasets.
/**
 * @compiler-api Coherent retained generation assembled only by Compiler_Session.
 * All readonly fields expose shared stage outputs for observation and the next
 * update baseline. They retain their owners and identity domains; do not mutate
 * nested stores. A snapshot does not contain the per-update symbol change catalog.
 */
class Compiler_Snapshot
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly Input_Snapshot $inputs,
        public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly \resolve_symbols\Resolution_Set $resolutions,
        public readonly \resolve_types\Type_Resolution $types,
        public readonly \check_bodies\Body_Set $bodies,
        public readonly \analyze_lifetimes\Lifetime_Set $lifetimes,
        public readonly \prepare_backend\Backend_Context $backend,
        public readonly \lower\Lowered_Set $lowered,
        public readonly \emit_llvm\Emitted_Program $llvm,
        public readonly ?\build_native\Native_Artifact $native,
    )
    {
    }

    /**
     * @compiler-internal Session assembly from completed LLVM stages; does not publish or clone datasets.
     * Throws if LLVM output is absent; callers must supply coherent accepted stage inputs.
     */
    public static function from_result(Compile_Result $result, ?Input_Snapshot $inputs = null,
        ?\build_native\Native_Artifact $native = null): self
    {
        if ($result->llvm === null) {
            throw new \LogicException('Cannot retain incomplete compiler stages');
        }
        return new self($inputs ?? $result->inputs, $result->symbols->current,
            $result->resolutions, $result->types, $result->bodies, $result->lifetimes,
            $result->backend, $result->lowered, $result->llvm, $native ?? $result->native);
    }
}
