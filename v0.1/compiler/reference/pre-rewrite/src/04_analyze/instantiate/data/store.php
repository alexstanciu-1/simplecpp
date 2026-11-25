<?php
declare(strict_types=1);

/*
 * Role: Own the private current registry while concrete preparation accepts batches.
 * Used by: Concrete_Preparation and accepting instance/member joins
 * Flow: fixed batch reads -> validated join writes -> one retained Instance_Set
 * Previous snapshots are never mutated; ordinary consumers receive only snapshot().
 */
namespace instantiate;

final class Instance_Store implements Instance_View, \compile\Step_Store
{
    use Instance_Lookup;

    private array $contexts;
    private array $uses;
    private array $concrete_types;
    private array $constants;
    private array $keys;
    private int $next_id;
    private array $type_contexts = [];
    /** @var list<instance_context> Accepted contexts not yet handed to preparation selection. */
    private array $introduced = [];
    public readonly ?\type_model\type_lineage $lineage;

    /** Copy retained arrays once; later joins append only to this phase's private candidate. */
    public function __construct(private readonly Instance_Set $seed)
    {
        $this->contexts = $seed->contexts;
        $this->uses = $seed->uses;
        $this->concrete_types = $seed->concrete_types;
        $this->constants = $seed->constants;
        $this->keys = $seed->keys;
        $this->next_id = $seed->next_id;
        $this->lineage = $seed->lineage;
        foreach ($this->concrete_types as $id => $definition) {
            $this->type_contexts[self::type_key($definition)] = $id;
        }
    }

    /** Allocate only after batch validation, through the same exact identity owner as all instances. */
    public function allocate(\type_model\Type_Store $types, int $definition, array $arguments): int
    {
        if ($types->lineage !== $this->lineage) {
            throw new \LogicException('Instance allocation requires its fixed type lineage');
        }
        return Instance_Identities::allocate($types, $definition, $arguments, $this->keys, $this->next_id);
    }

    /** Adopt an accepted context once; selection consumes only newly introduced contexts. */
    public function accept(instance_context $context): void
    {
        $old = $this->contexts[$context->context_id] ?? null;
        if (($old !== null) && ($old !== $context)) {
            throw new \LogicException('Conflicting current instance context');
        }
        if ($old === null) {
            $this->contexts[$context->context_id] = $context;
            $this->introduced[] = $context;
        }
    }

    public function bind(instance_context $context, int $node, instance_context $target): void
    {
        $this->uses[$context->context_id][$node] = $target->context_id;
    }

    /** Register only a concrete type accepted by canonical type preparation, incrementally updating its lookup index. */
    public function accept_type(instance_context $context, \type_model\named_type_definition $definition): void
    {
        if ($this->context_for($context->context_id) !== $context) {
            throw new \LogicException('Type requires its accepted instance context');
        }
        $this->concrete_types[$context->instance_id] = $definition;
        $this->type_contexts[self::type_key($definition)] = $context->instance_id;
    }

    /** Transfer the newly accepted work frontier; no full-registry scan is required. */
    public function take_introduced(): array
    {
        $result = $this->introduced;
        $this->introduced = [];
        return $result;
    }

    public function template_checks(): \check_templates\Template_Set
    {
        return $this->seed->template_checks();
    }

    public function count(): int
    {
        return count($this->contexts);
    }

    /** Publish immutable arrays once after all batches, preserving allocation history and reuse provenance.
     * @param array<int, \resolve_symbols\Symbol_Resolution> $bindings */
    public function snapshot(array $bindings = []): Instance_Set
    {
        return new Instance_Set($this->contexts, $this->uses, $this->concrete_types, $this->constants,
            $this->keys, $this->next_id, $this->seed->constant_owners, $bindings, $this->lineage, $this->template_checks());
    }
}
