<?php
declare(strict_types=1);

/*
 * Role: LLVM_Backend phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> Project_Exports::prepare(); [action] capture target/layout facts; select signature/lifecycle tasks
 *   run() -> Layout_Coordinator::prepare(); Callable_Preparer::prepare_callable(); prepare_lifecycle(); LLVM_Toolchain::verify_signature()
 *   finalize() -> Lifecycle_Join::join(); Backend_Join::join()
 * Output: result() returns Backend_Context after finalize().
 */

namespace prepare_backend;

use resolve_types\Type_Resolution;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class LLVM_Backend implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private array $storage_tasks = [];
    private array $storage_results = [];
    private \compile\step_status $state = \compile\step_status::created;
    private Backend_Context $output;
    public const LINKAGE = 'external';
    public const CALLING_CONVENTION = 'ccc';
    private backend_configuration $configuration;
    private array $tasks = [];
    private array $results = [];
    private Layout_Coordinator $physical;
    private array $layouts = [];
    /** @var list<lifecycle_preparation_task> Fixed selected inputs; never changed by workers. */
    private array $lifecycle_tasks = [];
    /** @var list<lifecycle_preparation_result> Private outputs awaiting acceptance. */
    private array $lifecycle_results = [];
    private source_linkage $source_linkage;

    public function __construct(
        private readonly LLVM_Toolchain $toolchain,
        private readonly \resolve_types\Type_Resolution $types,
        private readonly ?Backend_Context $previous,
        private readonly bool $full_rebuild,
        private readonly ?\load_runtime\Runtime_Input_Set $runtime = null,
        private readonly ?Layout_Coordinator $layout_preparation = null,
        private readonly ?\check_bodies\Body_Set $bodies = null,
        private readonly ?\analyze_lifetimes\Lifetime_Set $lifetimes = null,
    )
    {
    }

    /** Verify backend and optional runtime inputs, then select callable and lifecycle preparation tasks. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            $this->configuration = $this->toolchain->configuration();
            if ($this->runtime !== null) {
                $this->toolchain->verify_runtime($this->runtime, $this->configuration);
            }
            $this->physical = $this->layout_preparation
                ?? new Layout_Coordinator($this->toolchain, $this->previous?->layouts ?? [], $this->full_rebuild);
            $this->storage_tasks = Storage_Preparation::select($this->runtime, $this->configuration, $this->previous, $this->full_rebuild);
            $this->tasks = self::select($this->types, $this->configuration, $this->previous, $this->full_rebuild);
            $this->source_linkage = Project_Exports::prepare($this->types->types, $this->runtime, $this->bodies, $this->lifetimes,
                $this->previous?->source_verifications ?? [], $this->full_rebuild);
            $this->lifecycle_tasks = self::select_lifecycle($this->runtime, $this->source_linkage->operations, $this->configuration, $this->previous, $this->full_rebuild);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Prepare callable and lifecycle ABI targets; verify their physical shapes in the coordinator. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            $this->storage_results = array_map(Storage_Preparation::prepare(...), $this->storage_tasks);
            $this->layouts = $this->physical->prepare($this->types->types, array_keys(Layout_Preparation::definitions($this->types->types)));
            // Selected source/provider signatures produce private callable bindings.
            $this->results = [];
            foreach ($this->tasks as $callable) {
                $this->results[] = Callable_Preparer::prepare_callable($callable, $this->types, $this->configuration);
            }

            // Lifecycle workers receive only selected fixed tasks; reuse is resolved by the join.
            $this->lifecycle_results = [];
            foreach ($this->lifecycle_tasks as $task) {
                $this->lifecycle_results[] = Callable_Preparer::prepare_lifecycle($task);
            }

            // Probe the actual prepared ABI shape; workers consume no tools or mutable configuration.
            $targets = [...array_map(static fn($binding) => $binding->abi, $this->results),
                ...array_map(static fn($result) => $result->target, $this->lifecycle_results),
                ...array_map(static fn($result) => $result->target, $this->storage_results)];
            foreach ($targets as $target) {
                $this->toolchain->verify_signature($target->return_type,
                    array_map(static fn($parameter) => $parameter->type, $target->parameters), $this->configuration);
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Accept prepared targets into the candidate backend context and release temporary batches. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try
        {
            $lifecycle_targets = (new Lifecycle_Join($this->runtime, $this->configuration, $this->previous, $this->lifecycle_tasks, $this->source_linkage->operations))
                ->join($this->lifecycle_results);
            $storage = (new Storage_Join($this->runtime, $this->configuration, $this->previous, $this->storage_tasks))->join($this->storage_results);
            $this->output = (new Backend_Join($this->types, $this->configuration, $this->previous, $this->tasks, $this->runtime, $lifecycle_targets, $this->layouts, $storage, $this->source_linkage))->join($this->results);
            $this->storage_tasks = [];
            $this->storage_results = [];
            $this->tasks = [];
            $this->results = [];
            $this->lifecycle_tasks = [];
            $this->lifecycle_results = [];
            $this->layouts = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Backend_Context
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Backend_Context
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

    /**
     * @compiler-api Select callable associations on full or changed target/policy/shared signature/type
     * facts. Read-only; caller must verify target primitives before dispatching workers.
     * @return list<Callable_Signature>
     */
    private static function select(Type_Resolution $types, backend_configuration $configuration,
        ?Backend_Context $previous, bool $full_rebuild): array
    {
        $tasks = [];
        foreach ($types->signatures() as $callable) {
            if (($full_rebuild) || (!Callable_Contract::is_current($previous?->binding_for($callable->callable_id), $callable, $types, $configuration))) {
                $tasks[] = $callable;
            }
        }
        return $tasks;
    }

    /**
     * Select implicit operations before computation, using the same full/changed policy as source bindings.
     * @return list<lifecycle_preparation_task>
     */
    private static function select_lifecycle(?\load_runtime\Runtime_Input_Set $runtime, array $source_operations, backend_configuration $configuration,
        ?Backend_Context $previous, bool $full_rebuild): array
    {
        $tasks = [];
        foreach ([...($runtime?->lifecycle_operations() ?? []), ...$source_operations] as $operation) {
            if (($full_rebuild) || !Callable_Contract::lifecycle_is_current($previous, $operation, $configuration)) {
                $tasks[] = new lifecycle_preparation_task($operation, $configuration);
            }
        }
        return $tasks;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('LLVM_Backend::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
