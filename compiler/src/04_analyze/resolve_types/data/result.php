<?php
declare(strict_types=1);

/*
 * Role: Completed callable signatures, local types and type snapshot.
 * Used by: Type_Resolver; body checking; backend preparation
 * Flow: accepted requests -> Type_Resolution -> semantic/backend inputs
 */

namespace resolve_types;

use type_model\Type_Store;
use type_model\representation_kind;
use type_model\signature_representation;

/**
 * @compiler-api Read-only local/type associations from local join, consumed by checking.
 * Readable names and type_ids retain the exact binding owner and local-ID order.
 * Type IDs resolve only in the containing Type_Resolution; no copied syntax/scopes.
 */
class Local_Types
{
    public readonly int $callable_id;
    /**
     * @compiler-internal Producer-only construction; readiness follows the owning process contract.
     * @param list<int> $type_ids One type ID for each resolved local.
     */
    public function __construct(
        public readonly \resolve_symbols\Symbol_Resolution $names,
        public readonly array $type_ids,
        public readonly ?\instantiate\instance_context $instance = null,
    )
    {
        $this->callable_id = $instance?->context_id ?? $names->symbol_id;
        if ((!array_is_list($type_ids)) || (count($type_ids) !== count($names->locals))) {
            throw new \LogicException('Incomplete local type associations');
        }
        foreach ($type_ids as $type_id) {
            if ((!is_int($type_id)) || ($type_id <= 0)) {
                throw new \LogicException('Invalid local type ID');
            }
        }
    }

    /** @compiler-api Read the type ID for a one-based local ID in names; throws when absent. */
    public function type_for(int $local_id): int
    {
        return $this->type_ids[$local_id - 1] ?? throw new \OutOfBoundsException('Missing resolved local type: ' . $local_id);
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_array(): array
    {
        $rows = [];
        foreach ($this->type_ids as $row => $type_id) {
            $rows[] = ['local_id' => $row + 1, 'type_id' => $type_id];
        }
        return ['symbol_id' => $this->names->symbol_id, 'callable_id' => $this->callable_id, 'source_file_id' => $this->names->syntax->source_file_id, 'locals' => $rows];
    }
}

/**
 * @compiler-api Read-only callable association from signature join; all readonly fields readable.
 * AST IDs refer to syntax; representation_id resolves in the containing Type_Resolution.
 * Source entries have zero declaration/annotation IDs. Provider declarations have
 * external set, null syntax and zero AST IDs; they participate in signatures only.
 */
class Callable_Signature
{
    public readonly int $callable_id;
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $symbol_id,
        public readonly ?\parse\Syntax_Tree $syntax,
        public readonly int $declaration_node_id,
        public readonly int $return_annotation_id,
        public readonly int $representation_id,
        public readonly int $body_node_id,
        public readonly ?\type_model\runtime_callable $external = null,
        public readonly ?\instantiate\instance_context $instance = null,
        public readonly ?\type_model\storage_function $storage = null,
        public readonly ?int $receiver_index = null,
    )
    {
        $this->callable_id = $instance?->context_id ?? $symbol_id;
        if (((int)($syntax !== null) + (int)($external !== null) + (int)($storage !== null) !== 1)
            || ((($external !== null) || ($storage !== null))
                && (($declaration_node_id !== 0) || ($return_annotation_id !== 0) || ($body_node_id !== 0)))
            || (($storage !== null) && ($instance?->definition->external !== $storage))) {
            throw new \InvalidArgumentException('Callable signature requires exactly one source or provider origin');
        }
    }
}

/**
 * @compiler-api Completed type-stage snapshot read by checking/backend preparation and compile.
 * Readable fields: types, catalog, entry; lookup methods expose signatures/local types.
 * All type/representation IDs must be interpreted using this same types lineage.
 * The private candidate remains mutable while signature/local joins run; consumers
 * receive it only after Type_Resolver::finalize completes. Do not mutate nested stores.
 * Construction checks associations/entry consistency, not every stage obligation.
 */
class Type_Resolution implements \compile\Step_Result
{
    /** @var array<int, Callable_Signature> */
    private array $by_callable = [];

    private readonly Definition_View $definitions;

    /** @var array<int, Local_Types> Only callables containing local declarations. */
    private array $locals_by_callable = [];

    /** @var array<string, array<int, int>> Language role and canonical operand/result type to callable ID. */
    private array $language_calls = [];
    private int $default_literal = 0;

    /** @var array<string, array<int, array<int, int>>> Purpose/source/destination to an accepted callable ID. */
    private array $conversion_calls = [];

    /**
     * @compiler-internal Type-stage assembly from signature/local joins and their candidate store.
     * Type_Resolver combines both associations here; semantic workers must not
     * construct snapshots or observe this store before all type joins finish.
     * Checks association/entry coherence, not completion of every worker task.
     * @param list<Callable_Signature> $signatures
     * @param list<Local_Types> $locals
     * @param array<int, \load_runtime\family_preparation_result> $families Accepted native instance packages.
     */
    public function __construct(
        public readonly Type_Store $types,
        public readonly \type_model\Type_Catalog $catalog,
        public readonly entry_contract $entry,
        array $signatures,
        array $locals,
        public readonly \resolve_symbols\Resolution_Set $names,
        public readonly ?\instantiate\Instance_Set $instances = null,
        public readonly array $families = [],
    )
    {
        $this->definitions = new Definition_View($catalog, $types);
        foreach ($families as $id => $prepared) {
            if (($id !== $prepared->task->context->instance_id)
                || ($prepared->task->context !== ($instances?->contexts[$prepared->task->context->context_id] ?? null))
                || ($prepared->package->type_for($prepared->type_id)->language_type !== ($instances?->concrete_types[$id] ?? null))) {
                throw new \LogicException('Prepared family package differs from its accepted instance');
            }
        }

        foreach ($signatures as $signature)
        {
            if ((($signature->instance !== null) && (($instances?->contexts[$signature->callable_id] ?? null) !== $signature->instance))
                || ($signature->callable_id <= 0) || (isset($this->by_callable[$signature->callable_id]))) {
                throw new \LogicException('Invalid or duplicate signature owner');
            }
            $this->by_callable[$signature->callable_id] = $signature;
            $external = $signature->external;
            if ($external?->conversion_purpose !== null)
            {
                $shape = $types->representation_by_id($signature->representation_id)->payload;
                $source = $types->member_at($shape->first)->type_id;
                $purpose = $external->conversion_purpose->value;
                if (($shape->count !== 1) || ($source === $shape->return_type)
                    || isset($this->conversion_calls[$purpose][$source][$shape->return_type])) {
                    throw new \LogicException('Invalid or duplicate conversion binding');
                }
                $this->conversion_calls[$purpose][$source][$shape->return_type] = $signature->callable_id;
            }
            if ($external?->language_binding !== null)
            {
                $shape = $types->representation_by_id($signature->representation_id)->payload;
                $role = $external->language_binding;
                $type = $role === \type_model\language_binding::byte_literal ? $shape->return_type
                    : $types->member_at($shape->first)->type_id;
                if (isset($this->language_calls[$role->value][$type])) {
                    throw new \LogicException('Duplicate language operation binding for a type');
                }
                $this->language_calls[$role->value][$type] = $signature->callable_id;
                if ($external->default_literal) {
                    if ($this->default_literal !== 0) {
                        throw new \LogicException('Duplicate default byte literal binding');
                    }
                    $this->default_literal = $signature->callable_id;
                }
            }
        }

        // Local types must agree with both the body bindings and the completed signature.
        foreach ($locals as $result)
        {
            $id = $result->callable_id;
            $signature = $this->by_callable[$id] ?? null;
            if (($signature === null) || ($signature->instance !== $result->instance) || ($signature->syntax !== $result->names->syntax)
                || (($result->names->scopes[0] ?? null)?->block_node_id !== $signature->body_node_id)
                || (isset($this->locals_by_callable[$id])) || ($result->type_ids === [])) {
                throw new \LogicException('Invalid or duplicate local type owner');
            }
            $shape = $types->representation_by_id($signature->representation_id);
            if (($shape->kind !== representation_kind::function_signature) || ($shape->payload->count !== $result->names->parameter_count)) {
                throw new \LogicException('Local parameter count differs from signature');
            }
            for ($i = 0; $i < $result->names->parameter_count; ++$i) {
                if ($result->type_for($i + 1) !== $types->member_at($shape->payload->first + $i)->type_id) {
                    throw new \LogicException('Local parameter type differs from signature');
                }
            }
            foreach ($result->type_ids as $type_id) {
                $types->definition_for_type($type_id);
            }
            $this->locals_by_callable[$id] = $result;
        }

        // The implicit entry has no declaration node but still requires its language return contract.
        $entry_signature = $this->by_callable[$entry->symbol->symbol_id] ?? null;
        if (($entry_signature === null) || ($entry_signature->syntax !== $entry->symbol->frontend->syntax)
            || ($entry_signature->body_node_id !== $entry->symbol->body_node_id)
            || ($entry_signature->declaration_node_id !== 0) || ($entry_signature->return_annotation_id !== 0)) {
            throw new \LogicException('Missing or stale implicit entry signature');
        }
        $shape = $types->representation_by_id($entry_signature->representation_id);
        if (($shape->kind !== representation_kind::function_signature)
            || ($types->definition_for_type($shape->payload->return_type) !== $entry->return_type)) {
            throw new \LogicException('Entry signature does not match the language return contract');
        }
    }

    /** @compiler-api Select a metadata-bound language operation without inspecting provider names. */
    public function language_callable(\type_model\language_binding $role, int $type = 0): int
    {
        return ($role === \type_model\language_binding::byte_literal) && ($type === 0)
            ? $this->default_literal : ($this->language_calls[$role->value][$type] ?? 0);
    }

    /** @compiler-api Query exact accepted conversion identities in this fixed type snapshot. */
    public function conversion_callable(\type_model\conversion_purpose $purpose, int $source, int $destination): int
    {
        return $this->conversion_calls[$purpose->value][$source][$destination] ?? 0;
    }

    /** @compiler-api Read an ordinary callable association by source symbol ID, or null if nonparticipating. */
    public function for_symbol(int $id): ?Callable_Signature
    {
        return $id <= \collect_symbols\MAX_SYMBOL_ID ? $this->for_callable($id) : null;
    }

    public function for_callable(int $id): ?Callable_Signature
    {
        return $this->by_callable[$id] ?? null;
    }

    /** @compiler-api Read the shared local type associations, or null for an owner without them. */
    public function locals_for(int $callable_id): ?Local_Types
    {
        return $this->locals_by_callable[$callable_id] ?? null;
    }

    /** @compiler-api Read the shared signature payload for a concrete callable; throws when absent or malformed. */
    public function signature_for(int $callable_id): signature_representation
    {
        $signature = $this->for_callable($callable_id) ?? throw new \OutOfBoundsException('Missing resolved callable: ' . $callable_id);
        $shape = $this->types->representation_by_id($signature->representation_id);
        if ($shape->kind !== representation_kind::function_signature) {
            throw new \LogicException('Expected callable signature representation');
        }
        return $shape->payload;
    }

    /** @compiler-api Read a parameter type by one-based declaration position in this snapshot. */
    public function parameter_type_for(int $callable_id, int $position): int
    {
        $signature = $this->signature_for($callable_id);
        if (($position < 1) || ($position > $signature->count)) {
            throw new \OutOfBoundsException('Missing signature parameter: ' . $position);
        }
        return $this->types->member_at($signature->first + $position - 1)->type_id;
    }

    /** Resolve a construction occurrence without materializing types or changing the accepted snapshot. */
    public function construction_type(\collect_symbols\symbol_record $owner, int $name_node, ?\instantiate\instance_context $instance = null): int
    {
        $definition = Annotation_Types::definition($owner, $name_node,
            $this->definitions, 'construction', $this->names, $instance, $this->instances);
        $id = $this->types->find_type($definition->name, $definition->namespace_name);
        if (($id === 0) || ($this->definition_for($id) !== $definition)) {
            $node = $owner->frontend->syntax->nodes[$name_node - 1];
            $source = $owner->frontend->tokens->source;
            throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length,
                'Construction type has no prepared value contract');
        }
        return $id;
    }

    /** @compiler-api Read the shared language definition for a type ID; throws when absent or unresolved. */
    public function definition_for(int $type_id): \type_model\named_type_definition
    {
        return $this->types->definition_for_type($type_id);
    }

    /** A missing boolean role leaves comparison unsupported; never infer it from a type spelling. */
    public function boolean_type(): int
    {
        $definition = $this->catalog->boolean_type;
        return $definition === null ? 0 : $this->types->find_type($definition->name, $definition->namespace_name);
    }

    /** @compiler-api Read the materialized language integer-literal type ID; throws if not prepared. */
    public function integer_literal_type(): int
    {
        $definition = $this->catalog->integer_literal_type;
        $id = $this->types->find_type($definition->name, $definition->namespace_name);
        if (($id === 0) || ($this->definition_for($id) !== $definition)) {
            throw new \LogicException('Integer literal type is not prepared');
        }
        return $id;
    }

    /**
     * @compiler-api Iterate shared callable associations keyed by concrete callable ID; no mutation or copied records.
     * @return array<int, Callable_Signature> All callable contracts in this snapshot.
     */
    public function signatures(): array
    {
        return $this->by_callable;
    }

    /** @compiler-api Signatures whose implementation is a source body in this compilation. */
    public function body_signatures(): array
    {
        return array_filter($this->by_callable, static fn(Callable_Signature $signature): bool => $signature->syntax !== null);
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $rows = [];
        foreach ($this->by_callable as $signature)
        {
            $shape = $this->types->representation_by_id($signature->representation_id)->payload;
            $parameters = [];
            for ($i = 1; $i <= $shape->count; ++$i) {
                $parameters[] = $this->parameter_type_for($signature->callable_id, $i);
            }
            $rows[] = ['symbol_id' => $signature->symbol_id, 'callable_id' => $signature->callable_id, 'source_file_id' => $signature->syntax?->source_file_id,
                'provider_operation' => $signature->external?->id, 'receiver_index' => $signature->receiver_index,
                'parameter_passing' => array_map(static fn($mode) => $mode->value, $shape->parameter_passing),
                'declaration_node_id' => $signature->declaration_node_id, 'return_annotation_id' => $signature->return_annotation_id,
                'body_node_id' => $signature->body_node_id, 'representation_id' => $signature->representation_id, 'return_type_id' => $shape->return_type, 'parameter_type_ids' => $parameters];
        }
        $locals = [];
        foreach ($this->locals_by_callable as $local) {
            $locals[] = $local->to_array();
        }
        $families = [];
        foreach ($this->families as $id => $prepared) {
            $families[] = ['instance_id' => $id, 'provider' => $prepared->task->context->definition->external->provider,
                'family' => $prepared->task->context->definition->external->id, 'type_id' => $prepared->type_id,
                'operations' => array_keys($prepared->operations),
                'package_directory' => $prepared->package->directory];
        }
        return '{"catalog":' . $this->catalog->to_json() . ',"types":' . $this->types->to_json()
            . ',"signatures":' . json_encode($rows, JSON_THROW_ON_ERROR)
            . ',"local_types":' . json_encode($locals, JSON_THROW_ON_ERROR)
            . ',"instances":' . json_encode(($this->instances ?? new \instantiate\Instance_Set())->to_array(), JSON_THROW_ON_ERROR)
            . ',"prepared_families":' . json_encode($families, JSON_THROW_ON_ERROR)
            . ',"entry_symbol_id":' . $this->entry->symbol->symbol_id . '}';
    }
}
