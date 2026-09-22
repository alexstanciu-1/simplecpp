<?php
declare(strict_types=1);

/*
 * Role: Accept signature requests and materialize callable types.
 * Used by: Type_Resolver::finalize()
 * Call map:
 *   Signature_Join::join()
 *     -> \resolve_types\Type_Cache::materialize()
 */

namespace resolve_types;

use type_model\Type_Store;
use type_model\representation_kind;

use parse\Syntax_Access;
use collect_symbols\Symbol_Store;
use collect_symbols\symbol_record;
use type_model\Type_Catalog;

/** @compiler-internal Accept signature requests and materialize the private type candidate. */
class Signature_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<symbol_record|\instantiate\instance_context> $tasks
     */
    public function __construct(
        private readonly Symbol_Store $symbols,
        private readonly Type_Catalog|Definition_View $catalog,
        private readonly Type_Store $types,
        private readonly ?Type_Resolution $previous,
        private readonly array $tasks,
        private readonly entry_contract $entry,
        private readonly \resolve_symbols\Resolution_Set $names,
        private readonly ?\instantiate\Instance_Set $instances = null,
        private readonly array $prepared = []
    )
    {
    }

    /**
     * @compiler-api Validate a complete current task/request batch before materializing return/signature
     * representations in the separate candidate. Retain valid unchanged signatures and
     * exclude nonparticipants. Throws on stale/duplicate/missing work; discard the private
     * store if materialization fails. Local-type join must finish before body checking.
     * @param list<signature_request> $results
     * @return list<Callable_Signature> Associations awaiting complete type-stage assembly.
     */
    public function join(array $results): array
    {
        if ((!$this->symbols->contains($this->entry->symbol->symbol_id)) || ($this->symbols->symbol_by_id($this->entry->symbol->symbol_id) !== $this->entry->symbol)
            || ($this->entry->return_type !== $this->catalog->entry_return_type)) {
            throw new \LogicException('Stale entry contract');
        }
        if (($this->types->context->provider_key !== $this->catalog->content_key)
            || ($this->types->context->target_key !== $this->catalog->representation_scope)
            || (($this->previous !== null) && ($this->types === $this->previous->types))) {
            throw new \LogicException('Signature join requires a separate candidate with the current catalog context');
        }

        // Validate the selected owners against the current project and type candidate.
        $selected = [];
        foreach ($this->tasks as $input)
        {
            $task = Callable_Inputs::owner($input);
            $id = Callable_Inputs::id($input);
            if ((isset($selected[$id])) || !Callable_Inputs::is_current($input, $this->symbols, $this->instances) || (!Signature_Resolver::participates($task, $this->entry, Callable_Inputs::instance($input)))) {
                throw new \LogicException('Duplicate or stale signature task');
            }
            $selected[$id] = $input;
        }

        // Accept worker facts only when their annotations and catalog identities still match.
        $requests = [];
        foreach ($results as $result)
        {
            $id = $result->callable_id;
            if (((($selected[$id] ?? null) !== ($result->instance ?? $result->symbol))) || (isset($requests[$id]))) {
                throw new \LogicException('Unexpected or duplicate signature result');
            }
            Source_Lifecycle::validate($result);
            if (!array_is_list($result->parameter_passing)
                || (($result->parameter_passing !== []) && (count($result->parameter_passing) !== count($result->parameter_definitions)))) {
                throw new \LogicException('Stale parameter signature passing count');
            }
            if ($result->symbol === $this->entry->symbol) {
                if (($result->return_annotation_id !== 0) || ($result->definition !== $this->entry->return_type) || ($result->parameter_definitions !== []) || ($result->parameter_passing !== [])) {
                    throw new \LogicException('Stale entry signature result');
                }
                $requests[$id] = $result;
                continue;
            }

            if ($result->symbol->external instanceof \type_model\storage_function) {
                if (!Storage_Definitions::matches($result, $this->catalog)) {
                    throw new \LogicException('Stale storage signature result');
                }
                $requests[$id] = $result;
                continue;
            }
            if ($result->symbol->external !== null)
            {
                $external = Callable_Inputs::external($result->symbol, $result->instance, $this->prepared);
                if (($result->return_annotation_id !== 0) || !self::matches_reference($external->signature->result->type, $result->definition, $this->catalog)
                    || !array_is_list($result->parameter_definitions)
                    || (count($result->parameter_definitions) !== count($external->signature->parameters))) {
                    throw new \LogicException('Stale provider signature result');
                }
                foreach ($result->parameter_definitions as $index => $definition) {
                    if ((($result->parameter_passing[$index] ?? null) !== $external->passing_for($index))
                        || !self::matches_reference($external->signature->parameters[$index]->type, $definition, $this->catalog)) {
                        throw new \LogicException('Provider signature refers to a different language catalog');
                    }
                }
                $requests[$id] = $result;
                continue;
            }

            // Validate provenance without rerunning the worker or allocating a
            // second request. Semantic resolution remains worker-owned.
            $tree = $result->symbol->frontend->syntax;
            $parts = Syntax_Access::function_parts($tree, Syntax_Access::underlying_declaration($tree, $result->symbol->declaration_node_id));
            if (($result->return_annotation_id !== $parts->return_type_id)
                || (!self::matches_annotation($result->symbol, $parts->return_type_id, $result->definition, $this->catalog, $this->names, $result->instance, $this->instances))
                || (!array_is_list($result->parameter_definitions))) {
                throw new \LogicException('Stale signature result');
            }
            $position = 0;
            if ($result->symbol->owner_symbol_id !== 0)
            {
                $receiver = $result->instance?->receiver_type;
                $passing = $result->symbol->receiver_const
                    ? \type_model\argument_passing::borrow_const : \type_model\argument_passing::borrow_mutable;
                if (($receiver === null) || (($result->parameter_definitions[0] ?? null) !== $receiver)
                    || (($result->parameter_passing[0] ?? null) !== $passing)) {
                    throw new \LogicException('Stale receiver signature contract');
                }
                $position = 1;
            }
            for ($parameter = Syntax_Access::first_parameter($tree, $parts->parameters_id); $parameter !== 0;
                $parameter = $tree->nodes[$parameter - 1]->next_sibling_id)
            {
                $annotation = Syntax_Access::parameter_parts($tree, $parameter)->type_syntax_id;
                $passing = Parameter_Contracts::passing($tree, $parameter);
                if (($result->parameter_passing[$position] ?? \type_model\argument_passing::value) !== $passing) {
                    throw new \LogicException('Stale source parameter passing contract');
                }
                $definition = $result->parameter_definitions[$position++] ?? null;
                if (($definition === null) || ($definition->representation->kind === representation_kind::void_type)
                    || (!self::matches_annotation($result->symbol, $annotation, $definition, $this->catalog, $this->names, $result->instance, $this->instances))) {
                    throw new \LogicException('Stale parameter signature result');
                }
                Parameter_Contracts::validate($result->symbol, $annotation, $passing, $definition);
            }
            if ($position !== count($result->parameter_definitions)) {
                throw new \LogicException('Stale parameter signature count');
            }
            $requests[$id] = $result;
        }
        if (count($requests) !== count($selected)) {
            throw new \LogicException('Incomplete signature phase');
        }
        foreach (Callable_Inputs::all($this->symbols, $this->instances) as $input)
        {
            $symbol = Callable_Inputs::owner($input);
            $instance = Callable_Inputs::instance($input);
            $id = Callable_Inputs::id($input);
            if (Signature_Resolver::participates($symbol, $this->entry, $instance) && (!isset($requests[$id]))
                && (!Signature_Validity::is_current($this->previous, $this->types, $symbol, $instance, $this->prepared))) {
                throw new \LogicException('Incomplete or stale signature phase');
            }
        }

        // Only this join writes the candidate. Input/result validation completed
        // first; deterministic symbol order governs type IDs, not worker completion.
        $signatures = [];
        foreach (Callable_Inputs::all($this->symbols, $this->instances) as $input)
        {
            $symbol = Callable_Inputs::owner($input);
            $instance = Callable_Inputs::instance($input);
            $id = Callable_Inputs::id($input);
            if (!Signature_Resolver::participates($symbol, $this->entry, $instance)) {
                continue;
            }
            $request = $requests[$id] ?? null;
            if ($request === null) {
                $signatures[] = $this->previous->for_callable($id);
                continue;
            }
            $return_type = \resolve_types\Type_Cache::materialize($this->types, $request->definition);
            $parameters = [];
            foreach ($request->parameter_definitions as $definition) {
                $parameters[] = \resolve_types\Type_Cache::materialize($this->types, $definition);
            }
            $representation = $this->types->intern_signature($return_type, $parameters, $request->parameter_passing);
            $signatures[] = new Callable_Signature($symbol->symbol_id, $symbol->frontend?->syntax,
                $symbol->declaration_node_id, $request->return_annotation_id, $representation, $symbol->body_node_id, Callable_Inputs::external($symbol, $instance, $this->prepared), $instance,
                $symbol->external instanceof \type_model\storage_function ? $symbol->external : null,
                $symbol->owner_symbol_id === 0 ? null : ($symbol->external instanceof \type_model\family_method ? $symbol->external->operation->receiver : 0));
        }
        return $signatures;
    }

    /** Accept only the exact resolved definition selected by the imported name in this fixed view. */
    private static function matches_reference(\type_model\named_type_reference $reference,
        \type_model\named_type_definition $definition, Type_Catalog|Definition_View $catalog): bool
    {
        return $catalog->find_type($reference->name, $reference->namespace_name) === $definition;
    }

    /** Check the result against the accepted annotation binding in the exact owner snapshot. */
    private static function matches_annotation(symbol_record $owner, int $id, \type_model\named_type_definition $definition,
        Type_Catalog|Definition_View $catalog, \resolve_symbols\Resolution_Set $names, ?\instantiate\instance_context $instance, ?\instantiate\Instance_Set $instances): bool
    {
        return Annotation_Types::definition($owner, $id, $catalog, 'signature', $names, $instance, $instances) === $definition;
    }
}
