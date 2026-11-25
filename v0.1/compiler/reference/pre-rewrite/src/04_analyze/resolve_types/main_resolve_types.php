<?php
declare(strict_types=1);

/*
 * Role: Type_Resolver phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] validate candidate; select ordinary record tasks
 *   run() -> Concrete_Preparation lifecycle; Family_Preparation::prepare_methods(); [action] fix tasks/view
 *          -> Signature_Resolver::resolve(); Local_Type_Resolver::resolve() [each task]
 *   finalize() -> Signature_Join::join(); Local_Type_Join::join()
 * Output: result() returns Type_Resolution / Type_Store after finalize().
 */

namespace resolve_types;

use type_model\Type_Store;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Type_Resolver implements \compile\Step, \compile\Runnable_Step, \compile\Store_Providing_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Type_Resolution $output;
    private \instantiate\Instance_Set $instances;
    private array $tasks = [];
    private array $prepared_calls = [];
    private Definition_View $definitions;
    private array $record_tasks = [];
    private array $local_tasks = [];
    private array $results = [];
    private array $local_results = [];

    public function __construct(
        private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \type_model\Type_Catalog $catalog,
        private readonly Type_Store $types,
        private readonly ?Type_Resolution $previous,
        private readonly bool $full_rebuild,
        private readonly entry_contract $entry,
        private readonly \resolve_symbols\Resolution_Set $names,
        private readonly ?int $instance_limit = null,
        private readonly ?\load_runtime\Family_Preparation $families = null,
        private readonly ?\prepare_backend\Layout_Coordinator $layouts = null,
        private readonly ?\prepare_backend\Source_Export_Coordinator $exports = null
    )
    {
    }

    /** Select ordinary record work against the unchanged phase inputs. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            if ((($this->previous !== null) && ($this->types === $this->previous->types))
                || ($this->types->context->provider_key !== $this->catalog->content_key)
                || ($this->types->context->target_key !== $this->catalog->representation_scope)) {
                throw new \LogicException('Type phase requires a separate candidate with the current catalog context');
            }
            $this->record_tasks = Record_Preparation::select($this->symbols, $this->catalog, $this->types, $this->full_rebuild, $this->names);
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Accept records and demanded instances, then resolve concrete annotations against one fixed view. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            $preparation = new Concrete_Preparation($this->symbols, $this->names, $this->catalog,
                $this->types, $this->previous?->instances, $this->full_rebuild, $this->instance_limit, $this->record_tasks, $this->families, $this->layouts, $this->exports);
            $preparation->init();
            $preparation->run();
            $preparation->finalize();
            $this->instances = $preparation->result();
            $this->prepared_calls = $this->families?->prepare_methods($this->instances) ?? [];
            foreach ($this->instances->constants as $constant) {
                Type_Cache::materialize($this->types, $constant->type);
            }
            $this->tasks = Signature_Resolver::select($this->symbols, $this->previous, $this->types, $this->full_rebuild, $this->entry, $this->instances, $this->prepared_calls);
            $this->local_tasks = Local_Type_Resolver::select($this->symbols, $this->names, $this->types, $this->previous, $this->full_rebuild, $this->entry, $this->instances);
            $this->definitions = new Definition_View($this->catalog, $this->types);
            // Materialization starts only after supported task inputs are established.
            Type_Cache::materialize($this->types, $this->catalog->integer_literal_type);
            if ($this->catalog->boolean_type !== null) {
                Type_Cache::materialize($this->types, $this->catalog->boolean_type);
            }
            $this->results = [];
            foreach ($this->tasks as $task) {
                $this->results[] = Signature_Resolver::resolve($this->symbols, $this->definitions, $task, $this->entry, $this->names, $this->instances, $this->prepared_calls);
            }
            $this->local_results = [];
            foreach ($this->local_tasks as $task) {
                $this->local_results[] = Local_Type_Resolver::resolve($this->symbols, $this->names, $this->definitions, $task, $this->instances);
            }
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Join annotations into the private type store and release selected preparation batches. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try
        {
            $signatures = (new \resolve_types\Signature_Join($this->symbols, $this->definitions, $this->types, $this->previous, $this->tasks, $this->entry, $this->names, $this->instances, $this->prepared_calls))->join($this->results);
            $locals = (new \resolve_types\Local_Type_Join($this->symbols, $this->names, $this->definitions, $this->types, $this->previous, $this->local_tasks, $this->entry, $signatures, $this->instances))->join($this->local_results);
            $this->output = new Type_Resolution($this->types, $this->catalog, $this->entry, $signatures, $locals, $this->names, $this->instances, $this->families?->result() ?? []);
            $this->tasks = [];
            $this->prepared_calls = [];
            $this->results = [];
            $this->local_tasks = [];
            $this->local_results = [];
            $this->record_tasks = [];
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): Type_Resolution
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function store(): Type_Store
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output->types;
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
            throw new \LogicException('Type_Resolver::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
