<?php
declare(strict_types=1);

/*
 * Role: Prepared callable bindings, validity and context reuse.
 * Used by: LLVM_Backend; Backend_Join; lowering and emission
 * Flow: verified configuration + bindings -> Backend_Context
 */

namespace prepare_backend;

/**
 * @compiler-api Shared backend snapshot produced by backend preparation.
 * Emission's read boundary is configuration plus binding_for(); lowering also
 * uses callable_for() to validate shared language contracts. The private callable
 * index is replaceable storage, not a cross-process API. Retain this exact object
 * for coherence/reuse checks; consumers must not reassemble it from numeric IDs.
 * A constructed context can be incomplete; only the preparation process establishes
 * readiness. Construction/validate check coherence, not toolchain capabilities.
 */
final class Backend_Context implements \compile\Step_Result, \compile\Step_Store
{
    /** @var array<string, abi_target> Exact link names, shared across call and lifecycle consumers. */
    private readonly array $abi_targets;
    /** @var array<int, callable_binding> */
    private readonly array $callables;
    public readonly array $source_operations;
    public readonly array $source_exports;
    public readonly array $source_verifications;

    /**
     * @compiler-internal Producer-only construction; readiness follows the owning process contract.
     * @param list<callable_binding> $callables
     * @param list<abi_target> $lifecycle_targets Complete normalized implicit lifecycle targets.
     * @param array<int, storage_layout> $layouts Accepted physical layouts keyed by canonical type ID.
     */
    public function __construct(

        /** @compiler-api Shared target facts; null means backend is not configured. */
        public readonly ?backend_configuration $configuration = null,
        array $callables = [],
        public readonly ?\load_runtime\Runtime_Input_Set $runtime = null,
        array $lifecycle_targets = [],
        public readonly array $layouts = [],
        public readonly ?\type_model\Type_Store $types = null,
        public readonly array $lifecycle_bodies = [],
        public readonly array $storage_targets = [],
        ?source_linkage $source_linkage = null,
    )
    {
        if (($configuration === null) && ($callables !== [])) {
            throw new \InvalidArgumentException('Backend bindings require an explicit configuration');
        }
        $by_symbol = [];
        $by_link_name = [];
        $abi_targets = ['llvm.trap' => new abi_target('llvm.trap', 'ccc', 'void', [])];
        foreach ($callables as $binding)
        {
            if ($binding->configuration != $configuration) {
                throw new \InvalidArgumentException('Backend binding was prepared for a different configuration');
            }
            if ((isset($by_symbol[$binding->callable_id])) || (isset($by_link_name[$binding->link_name]))) {
                throw new \InvalidArgumentException('Duplicate backend callable symbol or link name');
            }
            if (($binding->external !== null) && ($runtime?->callable_for($binding->external->provider, $binding->external->id) !== $binding->external)) {
                throw new \InvalidArgumentException('Backend callable requires its exact runtime package');
            }
            $by_symbol[$binding->callable_id] = $binding;
            $by_link_name[$binding->link_name] = true;
            $abi_targets[$binding->link_name] = $binding->abi;
        }
        ksort($by_symbol);
        $source_linkage ??= Project_Exports::prepare($types, $runtime);
        if (($source_linkage->types !== $types) || ($source_linkage->runtime !== $runtime)) {
            throw new \LogicException('Stale source link preparation');
        }
        $this->source_operations = $source_linkage->operations;
        $this->source_exports = $source_linkage->entries;
        $this->source_verifications = $source_linkage->verifications;
        $expected = [];
        foreach ([...($runtime?->lifecycle_operations() ?? []), ...$this->source_operations] as $operation) {
            $expected[$operation->link_name] = $operation;
        }
        foreach ($lifecycle_targets as $target)
        {
            if (($configuration === null) || isset($abi_targets[$target->link_name])
                || (($expected[$target->link_name] ?? null) !== $target->lifecycle_operation)
                || ($target->lifecycle_operation === null)
                || !Callable_Contract::lifecycle_matches($target, $configuration)) {
                throw new \InvalidArgumentException('Duplicate, stale or invalid lifecycle ABI target');
            }
            $abi_targets[$target->link_name] = $target;
            unset($expected[$target->link_name]);
        }
        if ($expected !== []) {
            throw new \InvalidArgumentException('Missing lifecycle ABI targets');
        }

        // Body imports are ordinary accepted source call targets, referenced by complete operations.
        $body_keys = [];
        foreach ($types?->lifecycle_operations() ?? [] as $operation)
        {
            if ($operation->body_symbol_id !== 0)
            {
                $target = $lifecycle_bodies[$operation->link_name] ?? null;
                if (($target === null) || (($abi_targets[$target->link_name] ?? null) !== $target)
                    || ($target->return_type !== 'void') || (count($target->parameters) !== LLVM_Types::lifecycle_arity($operation->kind))
                    || ($target->parameters[0]->type !== 'ptr')
                    || ((count($target->parameters) === 2) && ($target->parameters[1]->type !== 'ptr'))) {
                    throw new \InvalidArgumentException('Missing or stale lifecycle body ABI');
                }
                $body_keys[] = $operation->link_name;
            }
        }
        if ($body_keys !== array_keys($lifecycle_bodies)) {
            throw new \InvalidArgumentException('Unexpected lifecycle body imports');
        }
        $primitives = Storage_Preparation::primitives($runtime);
        if (array_keys($storage_targets) !== array_keys($primitives)) {
            throw new \LogicException('Missing storage ABI targets');
        }
        foreach ($storage_targets as $link => $prepared) {
            if (isset($abi_targets[$link]) || ($prepared->task->primitive !== $primitives[$link])
                || ($prepared->task->configuration !== $configuration) || !Storage_Preparation::matches($prepared)) {
                throw new \LogicException('Duplicate or stale storage ABI target');
            }
            $abi_targets[$link] = $prepared->target;
        }
        foreach ($this->source_exports as $symbol => $export)
        {
            $verified = $this->source_verifications[$export->capability->operation->link_name] ?? null;
            if ($verified?->task->operation !== $export->capability->operation) {
                throw new \LogicException('Source export requires verified implementation evidence');
            }
            if (isset($abi_targets[$symbol]) || !isset($abi_targets[$export->implementation->link_name])
                || ($abi_targets[$export->implementation->link_name]->lifecycle_operation !== $export->capability->operation)) {
                throw new \LogicException('Missing or conflicting source export implementation');
            }
            $abi_targets[$symbol] = $export->import;
        }
        $this->abi_targets = $abi_targets;
        $this->callables = $by_symbol;
    }

    // Coordinator canonicalizes equal snapshots before handing them to workers.
    // Object identity is then a conservative, constant-time reuse dependency.
    /**
     * @compiler-internal Backend coordinator canonicalization: return the previous object only when target
     * facts and shared bindings match. Equality here does not verify missing capabilities.
     */
    public static function prepare(self $requested, ?self $previous): self
    {
        if (($previous === null) || ($requested->configuration != $previous->configuration) || ($requested->runtime !== $previous->runtime)
            || (array_keys($requested->callables) !== array_keys($previous->callables)) || ($requested->layouts !== $previous->layouts) || ($requested->storage_targets !== $previous->storage_targets)
            || ($requested->source_operations !== $previous->source_operations) || ($requested->source_exports != $previous->source_exports)
            || ($requested->source_verifications !== $previous->source_verifications)
            || (($requested->types?->lifecycle_operations() ?? []) !== ($previous->types?->lifecycle_operations() ?? []))) {
            return $requested;
        }
        foreach ($requested->callables as $id => $binding)
        {
            $old = $previous->callables[$id];
            if (($binding->signature !== $old->signature) || ($binding->return_definition !== $old->return_definition)
                || ($binding->link_name !== $old->link_name) || ($binding->linkage !== $old->linkage)
                || ($binding->calling_convention !== $old->calling_convention) || ($binding->return_extension !== $old->return_extension) || ($binding->result_passing !== $old->result_passing)
                || ($binding->external !== $old->external) || ($binding->storage !== $old->storage) || (!self::same_parameters($binding, $old))) {
                return $requested;
            }
        }
        return $previous;
    }

    // The coordinator may accept incomplete preparation, but never stale prepared
    // contracts. Missing bindings remain explicit until a real provider supplies them.
    /**
     * @compiler-api Read-only coherence check of supplied bindings against current resolved signatures/types.
     * Throws for stale prepared contracts; missing bindings are permitted, so success
     * does not establish complete backend readiness.
     */
    public function validate(\resolve_types\Type_Resolution $types): void
    {
        if (($this->types?->lifecycle_operations() ?? []) !== $types->types->lifecycle_operations()) {
            throw new \LogicException('Stale source lifecycle contracts');
        }

        // Build one lookup for provenance checks; no per-operation scan of every callable.
        $by_link = [];
        foreach ($this->callables as $binding) {
            $by_link[$binding->link_name] = $binding;
        }
        foreach ($types->types->lifecycle_operations() as $operation)
        {
            if ($operation->body_symbol_id === 0) {
                continue;
            }
            $target = $this->lifecycle_bodies[$operation->link_name];
            $binding = $by_link[$target->link_name] ?? null;
            $callable = $binding === null ? null : $types->for_callable($binding->callable_id);
            if (($callable?->symbol_id !== $operation->body_symbol_id)
                || ($callable->instance?->receiver_type !== $types->definition_for($operation->type_id))) {
                throw new \LogicException('Lifecycle body import has the wrong declaration or receiver');
            }
        }
        $structures = Layout_Preparation::definitions($types->types);
        foreach ($this->layouts as $id => $layout) {
            if (($layout->definition !== ($structures[$id] ?? null))
                || ($layout->configuration !== $this->configuration) || ($layout->lineage !== $this->types->lineage)) {
                throw new \LogicException('Stale backend record layout');
            }
        }
        foreach ($this->callables as $id => $binding) {
            $signature = $types->signature_for($id);
            if (($binding->signature !== $signature) || ($binding->return_definition !== $types->definition_for($signature->return_type))
                || (!self::parameters_match($binding, $types))) {
                throw new \LogicException('Stale backend callable contract for symbol: ' . $id);
            }
        }
    }

    /** @compiler-api Shared binding by concrete callable ID, or null when not prepared. */
    public function binding_for(int $callable_id): ?callable_binding
    {
        return $this->callables[$callable_id] ?? null;
    }

    /**
     * @compiler-api Lowering lookup requiring the checked body's exact type/signature facts.
     * @throws \LogicException Unconfigured, missing or stale callable contract.
     */
    public function callable_for(\check_bodies\Checked_Body $body, int $callable_id): callable_binding
    {
        if ($this->configuration === null) {
            throw new \LogicException('Backend target and providers are not configured');
        }
        $binding = $this->callables[$callable_id]
            ?? throw new \LogicException('Backend callable contract is not prepared for symbol: ' . $callable_id);
        $signature = $body->signature_for($callable_id);
        if (($binding->signature !== $signature) || ($binding->return_definition !== $body->definition_for($signature->return_type))) {
            throw new \LogicException('Stale backend callable contract for symbol: ' . $callable_id);
        }
        foreach ($binding->parameters as $parameter) {
            if ($parameter->definition !== $body->definition_for($parameter->type_id)) {
                throw new \LogicException('Stale backend parameter contract for symbol: ' . $callable_id);
            }
        }
        return $binding;
    }

    /** @compiler-internal Shared prepared-parameter coherence check for backend selection and snapshot validation. */
    public static function parameters_match(callable_binding $binding, \resolve_types\Type_Resolution $types): bool
    {
        if (count($binding->parameters) !== $types->signature_for($binding->callable_id)->count) {
            return false;
        }
        foreach ($binding->parameters as $i => $parameter) {
            $id = $types->parameter_type_for($binding->callable_id, $i + 1);
            if (($parameter->type_id !== $id) || ($parameter->definition !== $types->definition_for($id))
                || ($parameter->passing !== $types->signature_for($binding->callable_id)->parameter_passing[$i])) {
                return false;
            }
        }
        return true;
    }

    /** Compare shared semantic definitions and ABI passing modes before reusing a prepared context. */
    private static function same_parameters(callable_binding $left, callable_binding $right): bool
    {
        if (count($left->parameters) !== count($right->parameters)) {
            return false;
        }
        foreach ($left->parameters as $i => $parameter) {
            if (($parameter->type_id !== $right->parameters[$i]->type_id) || ($parameter->definition !== $right->parameters[$i]->definition)
                || ($parameter->extension !== $right->parameters[$i]->extension) || ($parameter->passing !== $right->parameters[$i]->passing) || ($parameter->span !== $right->parameters[$i]->span)) {
                return false;
            }
        }
        return true;
    }

    /** @compiler-api Exact link lookup in this context, shared by source and implicit runtime calls. */
    public function abi_for(string $link_name): ?abi_target
    {
        return $this->abi_targets[$link_name] ?? null;
    }

    /** @compiler-api On-demand debug view of fixed contracts. */
    public function to_json(): string
    {
        $callables = [];
        foreach ($this->callables as $binding)
        {
            $callables[] = ['callable_id' => $binding->callable_id, 'link_name' => $binding->link_name,
                'linkage' => $binding->linkage, 'calling_convention' => $binding->calling_convention,
                'parameter_type_ids' => array_map(static fn($p) => $p->type_id, $binding->parameters),
                'return_extension' => $binding->return_extension->value, 'result_passing' => $binding->result_passing->value,
                'parameter_passing' => array_map(static fn($p) => $p->passing->value, $binding->parameters),
                'parameter_extensions' => array_map(static fn($p) => $p->extension->value, $binding->parameters),
                'provider_operation' => $binding->external?->id];
        }
        return json_encode(['configuration' => $this->configuration, 'callables' => $callables, 'abi_targets' => array_values($this->abi_targets), 'runtime' => $this->runtime?->to_array(),
                'layouts' => array_map(static fn($layout) => ['llvm_type' => $layout->llvm_type, 'size' => $layout->size,
                    'alignment' => $layout->alignment, 'offsets' => $layout->offsets], $this->layouts),
                'native_entry_adapter' => 'separate_module_plan'], JSON_THROW_ON_ERROR);
    }
}
