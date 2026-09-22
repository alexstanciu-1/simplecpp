<?php
declare(strict_types=1);

/*
 * Role: Interpret bound declarations as symbolic types, without concrete instantiation.
 * Used by: Template_Worker
 * Call map: Terms::annotation() -> declaration(); bindings(); arguments()
 *   field(); method() -> [action] follow source or provider member declarations
 *   default_construction() -> [action] check declared provider constructor or reject dependent default
 */
namespace check_templates;

use parse\Syntax_Access;
use parse\syntax_kind;
use resolve_symbols\reference_kind;
use collect_symbols\symbol_record;

final class Terms
{
    /** @var array<int, symbol_record> Exact declarations read by this worker. */
    public array $dependencies = [];
    /** @var array<int, \resolve_symbols\Symbol_Resolution> Exact binding snapshots read by this worker. */
    public array $binding_dependencies = [];

    public function __construct(public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly \resolve_symbols\Resolution_Set $names, public readonly \type_model\Type_Catalog $catalog)
    {
    }

    /** Capture exact declarations used by symbolic signatures and field projections. */
    public function declaration(int $id): symbol_record
    {
        return $this->dependencies[$id] = $this->symbols->symbol_by_id($id);
    }

    public function bindings(symbol_record $owner): \resolve_symbols\Symbol_Resolution
    {
        return $this->binding_dependencies[$owner->symbol_id] = $this->names->for_symbol($owner->symbol_id)
            ?? throw new \LogicException('Missing symbolic declaration bindings');
    }

    /** Interpret annotation trees iteratively; formal identity survives substitutions through calls.
     * @param list<type_term> $substitutions Ordered actual terms in the caller’s symbolic scope. */
    public function annotation(symbol_record $owner, int $root, array $substitutions = []): type_term
    {
        $tree = $owner->frontend->syntax;
        $names = $this->bindings($owner);
        $pending = [[$root, false]];
        $terms = [];
        while ($pending !== [])
        {
            [$id, $finish] = array_pop($pending);
            $node = $tree->nodes[$id - 1];
            if ($node->kind === syntax_kind::template_application)
            {
                $parts = Syntax_Access::template_application_parts($tree, $id);
                $arguments = $this->arguments($owner, $parts->first_argument_id);
                if (!$finish)
                {
                    $pending[] = [$id, true];
                    foreach (array_reverse($arguments) as $argument) {
                        $pending[] = [$argument, false];
                    }
                    continue;
                }
                $target = $this->declaration($names->name_for($parts->name_id)->target);
                $values = array_map(static fn($argument) => $terms[$argument], $arguments);
                foreach ($values as $value)
                {
                    $this->forwarded_type($value, $owner, $id);
                    if ($target->external instanceof \type_model\family_declaration) {
                        $this->family_argument($value, $owner, $id);
                    }
                    elseif (($target->external !== null) && ($value->dependent)) {
                        self::fail($owner, $id, 'Generic contract does not guarantee the provider storage family element requirements');
                    }
                }
                $terms[$id] = new type_term(term_kind::application, $target->symbol_id, $values);
            }
            elseif ($node->kind === syntax_kind::integer_literal) {
                $terms[$id] = new type_term(term_kind::constant, 'literal:' . self::text($owner, $id));
            }
            elseif ($node->kind === syntax_kind::name)
            {
                $binding = $names->name_for($id);
                if ($binding->kind === reference_kind::template_parameter)
                {
                    $base = $owner->owner_symbol_id ?: $owner->symbol_id;
                    $terms[$id] = $substitutions[$binding->target]
                        ?? new type_term(term_kind::parameter, $base . ':' . $binding->target);
                }
                elseif (in_array($binding->kind, [reference_kind::project_constant, reference_kind::local_constant], true)) {
                    $terms[$id] = new type_term(term_kind::constant, $binding->kind->name . ':' . $binding->target);
                }
                else
                {
                    if (is_int($binding->target)) {
                        $this->declaration($binding->target);
                    }
                    $terms[$id] = new type_term(term_kind::named, $binding->target);
                }
            }
            else {
                self::fail($owner, $id, 'Unsupported symbolic template argument; constant evaluation is not implemented');
            }
        }
        return $terms[$root];
    }

    /** Ordered syntax children are temporary IDs, never a second retained AST. */
    public function arguments(symbol_record $owner, int $first): array
    {
        $ids = [];
        for ($id = $first; $id !== 0; $id = $owner->frontend->syntax->nodes[$id - 1]->next_sibling_id) {
            $ids[] = $id;
        }
        return $ids;
    }

    /** A method receiver denotes its declaring family with the same formal slots. */
    public function receiver(symbol_record $method): type_term
    {
        $owner = $this->declaration($method->owner_symbol_id);
        $arguments = [];
        foreach ($this->bindings($owner)->template_parameters as $index => $parameter) {
            $arguments[] = new type_term(term_kind::parameter, $owner->symbol_id . ':' . $index);
        }
        return new type_term($arguments === [] ? term_kind::named : term_kind::application,
            $owner->symbol_id, $arguments);
    }

    /** Project only declared fields; a bare parameter has no structural interface. */
    public function field(type_term $receiver, string $name, symbol_record $use_owner, int $use): type_term
    {
        $owner = $this->struct_owner($receiver, $use_owner, $use);
        if ($owner->frontend === null) {
            self::fail($use_owner, $use, 'Provider family does not expose structural fields');
        }
        $member_cursor = Syntax_Access::struct_members($owner->frontend->syntax, $owner->declaration_node_id, syntax_kind::field_declaration);
        while ($member_cursor->advance())
        {
            $field = $member_cursor->current();
            $parts = Syntax_Access::field_declaration_parts($owner->frontend->syntax, $field);
            if (ltrim(self::text($owner, $parts->variable_id), '$') === $name) {
                $type = $this->annotation($owner, $parts->type_syntax_id, $receiver->arguments);
                return $parts->extent_id === 0 ? $type : new type_term(term_kind::array_type, 'array', [$type]);
            }
        }
        self::fail($use_owner, $use, 'Unknown field in declared generic receiver: ' . $name);
    }

    /** Resolve declared methods by receiver owner; no lookup on a concrete substituted argument. */
    public function method(type_term $receiver, string $name, symbol_record $use_owner, int $use): symbol_record
    {
        $owner = $this->struct_owner($receiver, $use_owner, $use);
        $id = $this->symbols->find_symbol($name, $owner->namespace_name,
            \collect_symbols\symbol_kind::function_symbol, $owner->symbol_id);
        if ($id === 0) {
            $id = $this->symbols->find_symbol($name, $owner->namespace_name,
                \collect_symbols\symbol_kind::template_function, $owner->symbol_id);
        }
        if ($id === 0) {
            self::fail($use_owner, $use, 'Unknown method in declared generic receiver: ' . $name);
        }
        return $this->declaration($id);
    }

    /** A source declaration grants its own structure; substituted parameter members never do. */
    private function struct_owner(type_term $type, symbol_record $use_owner, int $use): symbol_record
    {
        if (($type->kind === term_kind::parameter) || !is_int($type->target)) {
            self::fail($use_owner, $use, 'Generic contract does not permit member access on this type');
        }
        $owner = $this->declaration($type->target);
        if (!in_array($owner->kind, [\collect_symbols\symbol_kind::struct_symbol, \collect_symbols\symbol_kind::template_struct], true)
            || (($owner->frontend === null) && !($owner->external instanceof \type_model\family_declaration))) {
            self::fail($use_owner, $use, 'Unsupported symbolic member receiver');
        }
        return $owner;
    }

    /** Family element guarantees do not imply copy/assignment guarantees for the container itself. */
    public function forwarded_type(type_term $type, symbol_record $use_owner, int $use): void
    {
        if (($type->kind === term_kind::application) && ($type->dependent)
            && ($this->declaration($type->target)->external instanceof \type_model\family_declaration)) {
            self::fail($use_owner, $use, 'Declared generic baseline is not established for this provider application');
        }
    }

    /** An explicit empty family constructor grants construction of the container, not of a bare element T. */
    public function default_construction(?type_term $type, symbol_record $use_owner, int $use): void
    {
        if (($type?->kind === term_kind::application) && is_int($type->target))
        {
            $family = $this->declaration($type->target)->external;
            if ($family instanceof \type_model\family_declaration)
            {
                $operation = $family->definition->operations[$family->definition->lifecycle['construct'] ?? ''] ?? null;
                if (($operation === null) || ($operation->signature->parameters !== [])) {
                    self::fail($use_owner, $use, 'Family has no declared empty constructor');
                }
                return;
            }
        }
        if ($type?->dependent ?? false) {
            self::fail($use_owner, $use, 'Generic contract does not permit default construction');
        }
    }

    /** Whole-container value operations need their own lifecycle contract, not the element baseline. */
    public function provider_value_use(?type_term $type, symbol_record $use_owner, int $use): void
    {
        if (($type?->kind === term_kind::application) && ($type->dependent)
            && ($this->declaration($type->target)->external instanceof \type_model\family_declaration)) {
            self::fail($use_owner, $use, 'Whole provider value lifecycle is not implemented');
        }
    }

    /** A formal provides the default baseline; dependent aggregate guarantees cannot be inferred from its arguments. */
    public function family_argument(type_term $type, symbol_record $use_owner, int $use): void
    {
        if (($type->dependent) && ($type->kind !== term_kind::parameter)) {
            self::fail($use_owner, $use, 'Declared generic baseline is not established for this dependent type');
        }
        if ($type->target instanceof \type_model\named_type_definition) {
            $missing = \type_model\Generic_Contracts::missing($type->target, \type_model\generic_contract::copyable_value);
            if ($missing !== null) {
                self::fail($use_owner, $use, 'Default generic contract requires supported ' . $missing);
            }
        }
    }

    /** Substitute a semantic signature under the receiver's symbolic arguments, never concrete implementations. */
    public function provider_type(\type_model\type_reference $reference, \type_model\family_declaration $family,
        type_term $receiver): type_term
    {
        if ($reference instanceof \type_model\parameter_type_reference) {
            return $receiver->arguments[$reference->slot];
        }
        if ($reference instanceof \type_model\family_type_reference) {
            return $receiver;
        }
        if ($reference instanceof \type_model\provider_type_reference)
        {
            $name = $family->language_types[json_encode([$reference->provider, $reference->id], JSON_THROW_ON_ERROR)];
            $definition = $this->catalog->find_type($name->name, $name->namespace_name)
                ?? $this->catalog->find_record($name->name, $name->namespace_name)
                ?? throw new \LogicException('Family source mapping was not validated');
            return new type_term(term_kind::named, $definition);
        }
        throw new \LogicException('Unsupported validated family signature reference');
    }

    /** Compare full symbolic identities iteratively, including distinct formal slots and family arguments. */
    public static function same(type_term $left, type_term $right): bool
    {
        $pending = [[$left, $right]];
        while ($pending !== [])
        {
            [$a, $b] = array_pop($pending);
            if (($a->kind !== $b->kind) || ($a->target !== $b->target) || (count($a->arguments) !== count($b->arguments))) {
                return false;
            }
            foreach ($a->arguments as $index => $argument) {
                $pending[] = [$argument, $b->arguments[$index]];
            }
        }
        return true;
    }

    public static function text(symbol_record $owner, int $id): string
    {
        $node = $owner->frontend->syntax->nodes[$id - 1];
        return substr($owner->frontend->tokens->source->content, $node->start, $node->length);
    }

    public static function fail(symbol_record $owner, int $id, string $message): never
    {
        $node = $owner->frontend->syntax->nodes[$id - 1];
        $source = $owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length, $message);
    }
}
