<?php
declare(strict_types=1);

/*
 * Role: Bind one source definition or entry against fixed declarations and catalog.
 * Used by: Symbol_Resolver::run()
 * Call map:
 *   Resolution_Worker::run()
 *     -> definition(); [if body] parameters(); statements() [traits]
 */

namespace resolve_symbols;

use collect_symbols\Symbol_Store;
use collect_symbols\symbol_record;
use parse\syntax_kind;

// One source owner, fixed project/catalog/AST inputs and private output. The coordinator
// alone adopts the result. No local identity is shared across workers.
/**
 * @compiler-internal Declaration/body traversal behind Symbol_Resolver::run(). Constructor/run
 * are process-local; scope-name maps and cursors are not shared semantic contracts.
 */
class Resolution_Worker
{
    use Declaration_Resolution;
    use Name_Resolution;
    use Statement_Resolution;
    use Expression_Resolution;

    /** @var list<symbol_binding> */
    private array $calls = [];
    private array $members = [];

    /** @var list<lexical_scope> */
    private array $scopes = [];

    /** @var list<local_record> */
    private array $locals = [];

    /** @var list<local_binding> */
    private array $uses = [];

    /** @var array<int, array<string, int>> Active scope ID -> name -> local ID. */
    private array $names = [];

    /** @var list<name_binding> */
    private array $name_bindings = [];
    /** @var list<template_application_binding> */
    private array $applications = [];
    /** @var list<template_parameter> */
    private array $template_parameters = [];
    /** @var array<string, int> */
    private array $parameter_names = [];
    /** @var list<scoped_constant> */
    private array $constants = [];
    /** @var array<int, array<string, int>> Scope -> spelling -> declaration node. */
    private array $constant_names = [];
    private int $initializing_constant = 0;

    /** @compiler-internal Initialize private state for one task; use the process entry point externally. */
    public function __construct(private readonly Symbol_Store $symbols, private readonly symbol_record $owner,
        private readonly \type_model\Type_Catalog $catalog)
    {
    }

    /** @compiler-internal Compute private bindings for the fixed source owner; adoption belongs to Resolution_Join. */
    public function run(): Symbol_Resolution
    {
        if (($this->owner->frontend === null) || ($this->symbols->symbol_by_id($this->owner->symbol_id) !== $this->owner)) {
            throw new \LogicException('Stale resolution task');
        }
        $tree = $this->owner->frontend->syntax;
        $this->definition();
        if ($this->owner->body_node_id !== 0) {
            $root = $this->enter($this->owner->body_node_id, 0);
            $this->parameters($root->scope_id);
            $this->statements($root);
        }
        return new Symbol_Resolution($this->owner->symbol_id, $tree, $this->calls, $this->scopes, $this->locals,
            $this->uses, $this->name_bindings, $this->template_parameters, $this->constants, $this->applications, $this->members);
    }

    /** Create a lexical scope for the current syntax node after validating its identity. */
    private function enter(int $block_id, int $parent_id): scope_cursor
    {
        $block = $this->owner->frontend->syntax->nodes[$block_id - 1] ?? null;
        if ($block?->kind !== syntax_kind::block) {
            throw new \LogicException('Missing callable block for resolution');
        }
        $this->scopes[] = new lexical_scope($block_id, $parent_id);
        $scope_id = count($this->scopes);
        $this->names[$scope_id] = [];
        $this->constant_names[$scope_id] = [];

        // Follow only children; the root's sibling can be a project definition.
        return new scope_cursor($scope_id, $block->first_child_id);
    }

    /** Allocate a body-local identity after rejecting a duplicate name in the current scope. */
    private function declare_local(int $declaration_id, int $variable_id, int $scope_id): int
    {
        $name = $this->text($variable_id);
        if (isset($this->parameter_names[substr($name, 1)])) {
            $this->fail($variable_id, "Local '$name' conflicts with a template parameter");
        }
        if (isset($this->names[$scope_id][$name])) {
            $this->fail($variable_id, "Duplicate local '$name' in this block");
        }
        $this->locals[] = new local_record($declaration_id, $scope_id);
        return $this->names[$scope_id][$name] = count($this->locals);
    }

    /** Resolve the nearest declaration and reject a read within its own initializer. */
    private function bind_local(int $id, int $scope_id, local_access $access, int $initializing_local_id = 0): void
    {
        $name = $this->text($id);
        $local_id = $this->find_local($name, $scope_id);
        if ($local_id === 0) {
            $this->fail($id, "Unknown local '$name'");
        }
        if ($local_id === $initializing_local_id) {
            $this->fail($id, "Local '$name' cannot read itself in its initializer");
        }
        $this->uses[] = new local_binding($id, $local_id, $access);
    }

    /** Find the nearest exact local name through ancestor scopes, allowing nested declarations to shadow outer ones. */
    private function find_local(string $name, int $scope_id): int
    {
        for ($id = $scope_id; $id !== 0; $id = $this->scopes[$id - 1]->parent_scope_id) {
            $local_id = $this->names[$id][$name] ?? 0;
            if ($local_id !== 0) {
                return $local_id;
            }
        }
        return 0;
    }

    private function text(int $id): string
    {
        $node = $this->owner->frontend->syntax->nodes[$id - 1];
        return substr($this->owner->frontend->tokens->source->content, $node->start, $node->length);
    }

    private function fail(int $id, string $message): never
    {
        $node = $this->owner->frontend->syntax->nodes[$id - 1];
        $source = $this->owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length, $message);
    }
}
