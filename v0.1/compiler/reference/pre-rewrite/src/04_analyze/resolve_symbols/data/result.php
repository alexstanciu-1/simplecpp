<?php
declare(strict_types=1);

/*
 * Role: One source-owner name-resolution result and binding queries.
 * Used by: Resolution_Worker; Resolution_Join; type/body checking
 * Flow: fixed syntax + bindings -> Symbol_Resolution
 */

namespace resolve_symbols;

/**
 * @compiler-api Joined declaration/body bindings consumed by type resolution and body checking.
 * Readable fields: symbol_id, syntax, bindings, scopes, locals, local_bindings,
 * parameter_count, name_bindings, template_parameters, constants and applications. Local/scope IDs are separate one-based domains in this exact
 * result; AST IDs belong to syntax; call targets use project symbol IDs.
 * Runtime parameters occupy the ordered local prefix in scope 1. Template parameter
 * positions are zero-based under symbol_id; they are not runtime locals or concrete types. Read shared rows through the
 * accessors for lookup; never depend on private index keys. No consumer mutation.
 */
class Symbol_Resolution
{
    /** @var array<int, name_binding> Annotation and constant occurrences in this snapshot. */
    private readonly array $names_by_node;

    // Parameters form the ordered prefix of locals; no second binding dataset.
    public readonly int $parameter_count;

    /** @var array<int, int> Use node ID -> position in the binding dataset. */
    private readonly array $by_node;

    /** @var array<int, int> Declaration node ID -> local ID. */
    private readonly array $by_declaration;

    /** @var array<int, int> Variable use node ID -> binding row. */
    private readonly array $local_by_node;

    /** @var array<int, int> Block node ID -> scope ID. */
    private readonly array $by_block;

    // One source owner's bindings, all anchored in this exact file AST snapshot.
    // Shared after resolution; never resolve node IDs against a newer AST.
    /**
     * @compiler-internal Producer-only construction; readiness follows the owning process contract.
     * @param list<symbol_binding> $bindings
     * @param list<lexical_scope> $scopes
     * @param list<local_record> $locals
     * @param list<local_binding> $local_bindings
     * @param list<name_binding> $name_bindings
     * @param list<template_parameter> $template_parameters
     * @param list<scoped_constant> $constants
     * @param list<template_application_binding> $applications
     */
    public function __construct(
        public readonly int $symbol_id,
        public readonly \parse\Syntax_Tree $syntax,
        public readonly array $bindings = [],
        public readonly array $scopes = [],
        public readonly array $locals = [],
        public readonly array $local_bindings = [],
        public readonly array $name_bindings = [],
        public readonly array $template_parameters = [],
        public readonly array $constants = [],
        public readonly array $applications = [],
        public readonly array $members = [],
    )
    {
        if (!array_is_list($name_bindings) || !array_is_list($template_parameters)
            || !array_is_list($constants) || !array_is_list($applications) || !array_is_list($members)) {
            throw new \LogicException('Declaration bindings require ordered row lists');
        }
        $index = [];
        foreach ($bindings as $row => $binding) {
            if (($binding->use_node_id <= 0) || ($binding->target_symbol_id <= 0) || (isset($index[$binding->use_node_id]))) {
                throw new \LogicException('Invalid or duplicate resolved binding');
            }
            $index[$binding->use_node_id] = $row;
        }
        $this->by_node = $index;

        // Scope IDs follow traversal order, so every parent must already exist.
        $blocks = [];
        foreach ($scopes as $row => $scope)
        {
            if ((($syntax->nodes[$scope->block_node_id - 1] ?? null)?->kind !== \parse\syntax_kind::block)
                || (isset($blocks[$scope->block_node_id])) || ($scope->parent_scope_id < 0)
                || ($scope->parent_scope_id > $row) || (($row > 0) && ($scope->parent_scope_id === 0))) {
                throw new \LogicException('Invalid resolved scope');
            }
            $blocks[$scope->block_node_id] = $row + 1;
        }

        // Index declarations while enforcing the ordered root-parameter prefix.
        $declarations = [];
        $parameter_count = 0;
        foreach ($locals as $row => $local)
        {
            if (($local->scope_id <= 0) || (!isset($scopes[$local->scope_id - 1]))
                || (isset($declarations[$local->declaration_node_id]))) {
                throw new \LogicException('Invalid or duplicate resolved local');
            }
            if ($local->receiver)
            {
                if (($row !== 0) || ($local->scope_id !== 1)
                    || (($syntax->nodes[$local->declaration_node_id - 1] ?? null)?->kind !== \parse\syntax_kind::method_declaration)) {
                    throw new \LogicException('Invalid implicit receiver binding');
                }
                ++$parameter_count;
            }
            elseif (($syntax->nodes[$local->declaration_node_id - 1] ?? null)?->kind === \parse\syntax_kind::parameter_declaration) {
                if (($row !== $parameter_count) || ($local->scope_id !== 1)) {
                    throw new \LogicException('Resolved parameters must precede body locals in the root scope');
                }
                \parse\Syntax_Access::parameter_parts($syntax, $local->declaration_node_id);
                ++$parameter_count;
            }
            else {
                \parse\Syntax_Access::local_declaration_parts($syntax, $local->declaration_node_id);
            }
            $declarations[$local->declaration_node_id] = $row + 1;
        }

        // Keep variable-use lookups separate from declaration and project-call identities.
        $uses = [];
        foreach ($local_bindings as $row => $binding)
        {
            if ((($syntax->nodes[$binding->use_node_id - 1] ?? null)?->kind !== \parse\syntax_kind::variable_name)
                || ($binding->local_id <= 0) || (!isset($locals[$binding->local_id - 1]))
                || (isset($uses[$binding->use_node_id]))) {
                throw new \LogicException('Invalid or duplicate local binding');
            }
            $uses[$binding->use_node_id] = $row;
        }
        $this->by_declaration = $declarations;
        $this->local_by_node = $uses;
        $this->by_block = $blocks;
        $this->parameter_count = $parameter_count;

        $names = [];
        foreach ($name_bindings as $binding) {
            if (($binding->use_node_id <= 0) || isset($names[$binding->use_node_id])
                || (($syntax->nodes[$binding->use_node_id - 1]->kind ?? null) !== \parse\syntax_kind::name)) {
                throw new \LogicException('Invalid or duplicate declaration binding');
            }
            $names[$binding->use_node_id] = $binding;
        }
        $this->names_by_node = $names;
        foreach ($template_parameters as $parameter) {
            $parts = \parse\Syntax_Access::template_parameter_parts($syntax, $parameter->declaration_node_id);
            if (($parts->name_id !== $parameter->name_node_id) || ($parts->type_syntax_id !== $parameter->type_syntax_id)) {
                throw new \LogicException('Invalid template parameter binding');
            }
        }
        foreach ($applications as $application) {
            \parse\Syntax_Access::template_application_parts($syntax, $application->use_node_id);
            if (!$application->definition->is_template()) {
                throw new \LogicException('Application binding requires a template definition');
            }
        }
        $member_nodes = [];
        foreach ($members as $member)
        {
            $node = $syntax->nodes[$member->use_node_id - 1] ?? null;
            if (isset($member_nodes[$member->use_node_id]) || ($node?->kind !== \parse\syntax_kind::field_expression)
                || ($node->first_child_id !== $member->receiver_node_id)
                || !isset($uses[$member->receiver_node_id])) {
                throw new \LogicException('Invalid or duplicate member occurrence');
            }
            $member_nodes[$member->use_node_id] = true;
        }
        foreach ($constants as $constant) {
            \parse\Syntax_Access::constant_parts($syntax, $constant->declaration_node_id);
            if (!isset($scopes[$constant->scope_id - 1])) {
                throw new \LogicException('Invalid constant scope');
            }
        }
    }

    /** Read an annotation/constant reference without interpreting it as a canonical type. */
    public function name_for(int $node): name_binding
    {
        return $this->names_by_node[$node] ?? throw new \OutOfBoundsException('Missing declaration binding: ' . $node);
    }

    /** @compiler-api Read a bound project call target for a callee-name AST node; throws when missing. */
    public function target_for(int $use_node_id): int
    {
        $row = $this->by_node[$use_node_id] ?? throw new \OutOfBoundsException('Missing resolved call binding: ' . $use_node_id);
        return $this->bindings[$row]->target_symbol_id;
    }

    /** @compiler-api Read the callable-local ID assigned to a declaration AST node; throws when missing. */
    public function local_for_declaration(int $node_id): int
    {
        return $this->by_declaration[$node_id] ?? throw new \OutOfBoundsException('Missing resolved local declaration: ' . $node_id);
    }

    /** @compiler-api Read a shared local record by this result's one-based local ID; throws when missing. */
    public function local_for(int $local_id): local_record
    {
        return $this->locals[$local_id - 1] ?? throw new \OutOfBoundsException('Missing resolved local: ' . $local_id);
    }

    // Parameter positions are one-based and also identify their callable-local ID.
    /** @compiler-api Read a parameter by one-based position, also its local ID; throws outside parameter_count. */
    public function parameter_for(int $position): local_record
    {
        if (($position <= 0) || ($position > $this->parameter_count)) {
            throw new \OutOfBoundsException('Missing resolved parameter: ' . $position);
        }
        return $this->locals[$position - 1];
    }

    /** @compiler-api Read a shared lexical scope by one-based scope ID; throws when missing. */
    public function scope_for(int $scope_id): lexical_scope
    {
        return $this->scopes[$scope_id - 1] ?? throw new \OutOfBoundsException('Missing resolved scope: ' . $scope_id);
    }

    /** @compiler-api Read the scope ID assigned to a block AST node; throws when missing. */
    public function scope_for_block(int $node_id): int
    {
        return $this->by_block[$node_id] ?? throw new \OutOfBoundsException('Missing resolved block scope: ' . $node_id);
    }

    /** @compiler-api Read the local use/access binding for a variable AST node; throws when missing. */
    public function binding_for(int $use_node_id): local_binding
    {
        $row = $this->local_by_node[$use_node_id] ?? throw new \OutOfBoundsException('Missing local binding: ' . $use_node_id);
        return $this->local_bindings[$row];
    }

    // Resolution facts are separate from the coordinator's incremental decision.
    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_array(): array
    {
        $bindings = [];
        foreach ($this->bindings as $binding) {
            $bindings[] = ['use_node_id' => $binding->use_node_id, 'target_symbol_id' => $binding->target_symbol_id];
        }
        $scopes = [];
        foreach ($this->scopes as $row => $scope) {
            $scopes[] = ['scope_id' => $row + 1, 'block_node_id' => $scope->block_node_id, 'parent_scope_id' => $scope->parent_scope_id];
        }
        $locals = [];
        foreach ($this->locals as $row => $local) {
            $locals[] = ['local_id' => $row + 1, 'declaration_node_id' => $local->declaration_node_id, 'scope_id' => $local->scope_id];
        }
        $uses = [];
        foreach ($this->local_bindings as $binding) {
            $uses[] = ['use_node_id' => $binding->use_node_id, 'local_id' => $binding->local_id, 'access' => $binding->access->name];
        }
        $names = [];
        foreach ($this->name_bindings as $binding) {
            $target = is_int($binding->target) ? $binding->target
                : ['name' => $binding->target->name, 'namespace' => $binding->target->namespace_name];
            $names[] = ['use_node_id' => $binding->use_node_id, 'role' => $binding->role->name,
                'kind' => $binding->kind->name, 'target' => $target];
        }
        return ['symbol_id' => $this->symbol_id, 'source_file_id' => $this->syntax->source_file_id, 'bindings' => $bindings,
            'name_bindings' => $names, 'template_parameters' => $this->template_parameters,
            'applications' => array_map(static fn($use) => ['use_node_id' => $use->use_node_id,
                'definition_id' => $use->definition->symbol_id], $this->applications),
            'members' => array_map(static fn($use) => ['use_node_id' => $use->use_node_id,
                'receiver_node_id' => $use->receiver_node_id], $this->members),
            'constants' => $this->constants, 'scopes' => $scopes, 'parameter_count' => $this->parameter_count, 'locals' => $locals, 'local_bindings' => $uses];
    }
}
