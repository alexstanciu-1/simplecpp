<?php
declare(strict_types=1);

/*
 * Role: Accept backend bindings and preserve equal context identity.
 * Used by: LLVM_Backend::finalize()
 * Call map:
 *   Backend_Join::join()
 *     -> Backend_Context::validate(); Backend_Context::prepare()
 */

namespace prepare_backend;

use resolve_types\Type_Resolution;
use resolve_types\Callable_Signature;

/** @compiler-internal Accept callable bindings into the prepared backend context. */
class Backend_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<Callable_Signature> $tasks
     * @param list<abi_target> $lifecycle_targets Complete targets already accepted by Lifecycle_Join.
     */
    public function __construct(
        private readonly Type_Resolution $types,
        private readonly backend_configuration $configuration,
        private readonly ?Backend_Context $previous,
        private readonly array $tasks,
        private readonly ?\load_runtime\Runtime_Input_Set $runtime = null,
        private readonly array $lifecycle_targets = [],
        private readonly array $layouts = [],
        private readonly array $storage_targets = [],
        private readonly ?source_linkage $source_linkage = null,
    )
    {
    }

    /**
     * @compiler-api Validate a complete selected binding batch and all current membership. Preserve an
     * equal previous Backend_Context object for reuse, otherwise return the new context.
     * Throws for missing/duplicate/stale facts; does not run capability probes.
     * @param list<callable_binding> $results
     */
    public function join(array $results): Backend_Context
    {
        $selected = [];
        foreach ($this->tasks as $callable) {
            $id = $callable->callable_id;
            if ((isset($selected[$id])) || ($this->types->for_callable($id) !== $callable)) {
                throw new \LogicException('Duplicate or stale backend task');
            }
            $selected[$id] = $callable;
        }
        $replacements = [];
        foreach ($results as $binding) {
            $callable = $selected[$binding->callable_id] ?? null;
            if (($callable === null) || (isset($replacements[$binding->callable_id])) || (!Callable_Contract::is_current($binding, $callable, $this->types, $this->configuration))) {
                throw new \LogicException('Unexpected, duplicate or stale backend result');
            }
            $replacements[$binding->callable_id] = $binding;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete backend phase');
        }
        $bindings = [];
        foreach ($this->types->signatures() as $callable) {
            $binding = $replacements[$callable->callable_id] ?? $this->previous?->binding_for($callable->callable_id);
            if (!Callable_Contract::is_current($binding, $callable, $this->types, $this->configuration)) {
                throw new \LogicException('Incomplete or stale backend phase');
            }
            $bindings[] = $binding;
        }
        $candidate = new Backend_Context($this->configuration, $bindings, $this->runtime, $this->lifecycle_targets, $this->layouts, $this->types->types, $this->lifecycle_bodies($bindings), $this->storage_targets, $this->source_linkage);
        $candidate->validate($this->types);
        return Backend_Context::prepare($candidate, $this->previous);
    }

    /** Resolve complete-operation body imports by source declaration and concrete receiver, never by link spelling. */
    private function lifecycle_bodies(array $bindings): array
    {
        $members = [];
        foreach ($bindings as $binding)
        {
            $callable = $this->types->for_callable($binding->callable_id);
            if ($callable->instance?->receiver_type !== null) {
                $members[$binding->parameters[0]->type_id][$callable->symbol_id] = $binding;
            }
        }
        $bodies = [];
        foreach ($this->types->types->lifecycle_operations() as $operation)
        {
            if ($operation->body_symbol_id === 0) {
                continue;
            }
            $binding = $members[$operation->type_id][$operation->body_symbol_id] ?? null;
            if (($binding === null) || ($binding->signature->count !== LLVM_Types::lifecycle_arity($operation->kind))
                || ($binding->return_definition->representation->kind !== \type_model\representation_kind::void_type)
                || ($binding->parameters[0]->passing !== \type_model\argument_passing::borrow_mutable)
                || ($operation->kind->has_source()
                    && (($binding->parameters[1]->passing !== \type_model\argument_passing::borrow_const)
                        || ($binding->parameters[1]->type_id !== $operation->type_id)))) {
                throw new \LogicException('Missing or incompatible custom lifecycle body');
            }
            $bodies[$operation->link_name] = $binding->abi;
        }
        return $bodies;
    }
}
