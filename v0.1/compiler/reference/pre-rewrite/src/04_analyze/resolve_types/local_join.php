<?php
declare(strict_types=1);

/*
 * Role: Accept local requests and materialize their types.
 * Used by: Type_Resolver::finalize()
 * Call map:
 *   Local_Type_Join::join()
 *     -> \resolve_types\Type_Cache::materialize()
 */

namespace resolve_types;

use type_model\Type_Store;
use type_model\representation_kind;

use collect_symbols\Symbol_Store;
use collect_symbols\symbol_record;
use type_model\Type_Catalog;
use parse\Syntax_Access;
use resolve_symbols\Resolution_Set;

/** @compiler-internal Accept local type requests after the completed signature join. */
class Local_Type_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<symbol_record|\instantiate\instance_context> $tasks
     * @param list<Callable_Signature> $signatures
     */
    public function __construct(
        private readonly Symbol_Store $symbols,
        private readonly Resolution_Set $names,
        private readonly Type_Catalog|Definition_View $catalog,
        private readonly Type_Store $types,
        private readonly ?Type_Resolution $previous,
        private readonly array $tasks,
        private readonly entry_contract $entry,
        private readonly array $signatures,
        private readonly ?\instantiate\Instance_Set $instances = null
    )
    {
    }

    /**
     * @compiler-api Validate the complete owner/name/catalog request batch, then materialize in the
     * private candidate. Return current nonempty Local_Types associations, reusing valid
     * ones. Throws on stale/duplicate/incomplete work; discard candidate on failure.
     * Final type-assembly join. Only the coordinator writes this private candidate;
     * no body worker can observe it until all type joins have finished.
     * @param list<local_type_request> $results
     * @return list<Local_Types>
     */
    public function join(array $results): array
    {
        if ((!$this->symbols->contains($this->entry->symbol->symbol_id)) || ($this->symbols->symbol_by_id($this->entry->symbol->symbol_id) !== $this->entry->symbol)
            || ($this->entry->return_type !== $this->catalog->entry_return_type)) {
            throw new \LogicException('Stale local type entry contract');
        }
        if ((($this->previous !== null) && ($this->types === $this->previous->types))
            || ($this->types->context->provider_key !== $this->catalog->content_key)
            || ($this->types->context->target_key !== $this->catalog->representation_scope)) {
            throw new \LogicException('Local type join requires a separate candidate with the current catalog context');
        }

        // Validate the selected owners against the current project and type candidate.
        $selected = [];
        foreach ($this->tasks as $input)
        {
            $owner = Callable_Inputs::owner($input);
            $instance = Callable_Inputs::instance($input);
            $id = Callable_Inputs::id($input);
            if ((isset($selected[$id])) || !Callable_Inputs::is_current($input, $this->symbols, $this->instances) || (!Signature_Resolver::body_participates($owner, $this->entry, $instance))
                || (Local_Type_Validity::names_for($owner, $this->names)->locals === [])) {
                throw new \LogicException('Duplicate or stale local type task');
            }
            $selected[$id] = $input;
        }

        // Accept worker facts only when their annotations and catalog identities still match.
        $requests = [];
        foreach ($results as $result)
        {
            $id = $result->callable_id;
            if ((($selected[$id] ?? null) !== ($result->instance ?? $result->owner)) || (isset($requests[$id]))
                || ($result->names !== $this->names->for_symbol($result->owner->symbol_id)) || (!array_is_list($result->definitions))
                || (count($result->definitions) !== (count($result->names->locals) - $result->names->parameter_count))) {
                throw new \LogicException('Unexpected, duplicate or stale local type result');
            }
            foreach ($result->definitions as $row => $definition)
            {
                $local = $result->names->locals[$result->names->parameter_count + $row];
                $annotation = Syntax_Access::local_declaration_parts($result->names->syntax, $local->declaration_node_id)->type_syntax_id;
                if ((Annotation_Types::definition($result->owner, $annotation, $this->catalog, 'local', $this->names, $result->instance, $this->instances) !== $definition)
                    || ($definition->representation->kind === representation_kind::void_type)) {
                    throw new \LogicException('Stale local type definition');
                }
            }
            $requests[$id] = $result;
        }
        if (count($requests) !== count($selected)) {
            throw new \LogicException('Incomplete local type phase');
        }
        foreach (Callable_Inputs::all($this->symbols, $this->instances) as $input)
        {
            $owner = Callable_Inputs::owner($input);
            $instance = Callable_Inputs::instance($input);
            $id = Callable_Inputs::id($input);
            if (!Signature_Resolver::body_participates($owner, $this->entry, $instance)) {
                continue;
            }
            $bindings = Local_Type_Validity::names_for($owner, $this->names);
            if (($bindings->locals !== []) && (!isset($requests[$id])) && (!Local_Type_Validity::is_current($this->previous, $this->types, $bindings, $instance))) {
                throw new \LogicException('Incomplete or stale local type phase');
            }
        }

        // Signatures are complete fixed associations from the preceding type join.
        // Reuse their parameter IDs; no second annotation lookup/materialization.
        $by_callable = [];
        if ($requests !== [])
        {
            foreach ($this->signatures as $signature)
            {
                if (!isset($requests[$signature->callable_id])) {
                    continue;
                }
                if (isset($by_callable[$signature->callable_id])) {
                    throw new \LogicException('Duplicate local type signature');
                }
                $by_callable[$signature->callable_id] = $signature;
            }
        }
        foreach ($requests as $id => $request)
        {
            $signature = $by_callable[$id] ?? null;
            if (($signature === null) || ($signature->instance !== $request->instance) || ($signature->syntax !== $request->names->syntax)
                || ($signature->declaration_node_id !== $request->owner->declaration_node_id)
                || ($signature->body_node_id !== $request->owner->body_node_id)) {
                throw new \LogicException('Missing or stale local type signature');
            }
            $shape = $this->types->representation_by_id($signature->representation_id);
            if (($shape->kind !== representation_kind::function_signature) || ($shape->payload->count !== $request->names->parameter_count)) {
                throw new \LogicException('Local parameter count differs from signature');
            }
        }

        // Complete validation precedes materialization. Deterministic owner/local
        // order assigns IDs; worker completion order has no effect.
        $locals = [];
        foreach (Callable_Inputs::all($this->symbols, $this->instances) as $input)
        {
            $owner = Callable_Inputs::owner($input);
            $instance = Callable_Inputs::instance($input);
            $id = Callable_Inputs::id($input);
            if (!Signature_Resolver::body_participates($owner, $this->entry, $instance)) {
                continue;
            }
            $bindings = Local_Type_Validity::names_for($owner, $this->names);
            if ($bindings->locals === []) {
                continue;
            }
            $request = $requests[$id] ?? null;
            if ($request === null) {
                $locals[] = $this->previous->locals_for($id);
                continue;
            }

            // Keep signature parameter types as the prefix, then append body-local types in declaration order.
            $ids = [];
            $shape = $this->types->representation_by_id($by_callable[$id]->representation_id)->payload;
            for ($i = 0; $i < $shape->count; ++$i) {
                $ids[] = $this->types->member_at($shape->first + $i)->type_id;
            }
            foreach ($request->definitions as $definition) {
                $ids[] = \resolve_types\Type_Cache::materialize($this->types, $definition);
            }
            $locals[] = new Local_Types($bindings, $ids, $instance);
        }
        return $locals;
    }
}
