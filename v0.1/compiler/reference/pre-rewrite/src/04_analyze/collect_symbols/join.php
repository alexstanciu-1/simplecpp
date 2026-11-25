<?php
declare(strict_types=1);

/*
 * Role: Reconcile declaration membership, duplicates and identities.
 * Used by: Declaration_Collector::finalize()
 * Call map:
 *   Declaration_Join::join()
 *     -> import(); append()
 */

namespace collect_symbols;

use parse\File_Frontend;
use parse\Frontend_Set;
use read_sources\Source_Set;
use read_sources\file_change;

/** @compiler-internal Reconcile file declarations, project identities and duplicate diagnostics. */
class Declaration_Join implements \compile\Join
{
    /**
     * Capture the fixed context and selected tasks; validation belongs to join().
     * @param list<File_Frontend> $tasks
     */
    public function __construct(
        private readonly Symbol_Store $previous,
        private readonly Source_Set $sources,
        private readonly Frontend_Set $frontends,
        private readonly array $tasks,
        private readonly ?\load_runtime\Runtime_Input_Set $runtime = null,
        private readonly array $families = [],
    )
    {
    }

    /**
     * @compiler-api Accept one result per selected frontend; reject stale/incomplete/duplicate batches.
     * Produce current project membership and initial change pairs; missing old symbols
     * become removals. Duplicate language declarations throw Source_Error. No publication.
     * Coordinator-only join. Identity allocation and duplicate diagnostics follow
     * source order, independently of worker completion order. No baseline writes.
     * @param list<File_Declarations> $results
     */
    public function join(array $results): Symbol_Refresh
    {
        // Match worker results to the selected, current frontend snapshots.
        $selected = [];
        foreach ($this->tasks as $task) {
            $id = $task->source_file_id;
            if ((isset($selected[$id])) || (Frontend_Validation::current_frontend($this->sources, $this->frontends, $id) !== $task)) {
                throw new \LogicException('Duplicate or stale declaration task');
            }
            $selected[$id] = $task;
        }

        // Require one complete declaration list for each selected file.
        $by_file = [];
        foreach ($results as $result)
        {
            $id = $result->frontend->source_file_id;
            if ((isset($by_file[$id])) || (($selected[$id] ?? null) !== $result->frontend)) {
                throw new \LogicException('Unexpected, duplicate or stale declaration result');
            }
            $expected = count($result->frontend->defined_entities) + 1;
            foreach ($result->frontend->defined_entities as $node) {
                $inner = \parse\Syntax_Access::underlying_declaration($result->frontend->syntax, $node);
                if ($result->frontend->syntax->nodes[$inner - 1]->kind === \parse\syntax_kind::struct_declaration) {
                    $member_cursor = \parse\Syntax_Access::struct_members($result->frontend->syntax, $node, \parse\syntax_kind::method_declaration);
                    while ($member_cursor->advance()) {
                        $expected = $expected + 1;
                    }
                }
            }
            if (count($result->declarations) !== $expected) {
                throw new \LogicException('Incomplete file declarations');
            }
            $by_file[$id] = $result;
        }
        if (count($selected) !== count($by_file)) {
            throw new \LogicException('Incomplete declaration phase');
        }

        // Reconcile in source order so worker completion order cannot affect symbol IDs.
        $current = new Symbol_Store($this->previous->next_symbol_id());
        foreach ($this->sources->files as $file)
        {
            if ($file->change_state === file_change::deleted) {
                continue;
            }
            $frontend = Frontend_Validation::current_frontend($this->sources, $this->frontends, $file->id);
            $result = $by_file[$file->id] ?? null;

            // Unselected files retain their exact symbol records and frontend associations.
            if ($result === null)
            {
                $entry_id = $this->previous->entry_symbol_id($file->id);
                if (($entry_id === 0) || ($this->previous->symbol_by_id($entry_id)->frontend !== $frontend)) {
                    throw new \LogicException('Incomplete or stale declaration phase');
                }
                foreach ($this->previous->file_symbol_ids($file->id) as $id) {
                    self::append($current, $this->previous->symbol_by_id($id));
                }
                continue;
            }
            $owners = [];
            foreach ($result->declarations as $definition)
            {
                $owner_id = $definition->owner_declaration_node_id === 0 ? 0
                    : ($owners[$definition->owner_declaration_node_id] ?? throw new \LogicException('Member before its owner'));
                $old_id = $definition->kind === symbol_kind::file_entry
                    ? $this->previous->entry_symbol_id($file->id)
                    : $this->previous->find_symbol($definition->name, '', $definition->kind, $owner_id);
                $id = $old_id !== 0 ? $old_id : $current->allocate_id();
                $symbol = new symbol_record($id, $frontend);
                $owners[$definition->declaration_node_id] = $id;
                $symbol->owner_symbol_id = $owner_id;
                $symbol->template_parameters_node_id = $definition->template_parameters_node_id;
                $symbol->receiver_const = $definition->receiver_const;
                $symbol->kind = $definition->kind;
                $symbol->name = $definition->name;
                $symbol->declaration_node_id = $definition->declaration_node_id;
                $symbol->body_node_id = $definition->body_node_id;
                self::append($current, $symbol);
            }
        }

        $declarations = $this->runtime?->callables() ?? [];
        foreach ($this->runtime?->storage_families ?? [] as $family) {
            $declarations[] = $family;
            foreach ($family->operations as $role => $name) {
                $declarations[] = new \type_model\storage_function($family, \type_model\storage_role::from($role),
                    $name, $family->namespace_name, $family->provider, $family->id . '.' . $role);
            }
        }
        foreach ($declarations as $declaration) {
            $this->import($current, $declaration);
        }
        foreach ($this->families as $family)
        {
            $owner = $this->import($current, $family);
            foreach ($family->definition->operations as $operation) {
                if ($operation->expose_as !== null) {
                    $this->import($current, new \type_model\family_method($family, $operation), $owner->symbol_id);
                }
            }
        }

        // Describe additions, replacements, and removals without comparing syntax here.
        $refresh = new Symbol_Refresh();
        $refresh->current = $current;
        foreach ($current->records() as $symbol) {
            $old = $this->previous->contains($symbol->symbol_id) ? $this->previous->symbol_by_id($symbol->symbol_id) : null;
            if ($old !== $symbol) {
                $refresh->changes[] = new symbol_change($old, $symbol,
                    $old === null ? change_status::added : change_status::uncompared);
            }
        }
        foreach ($this->previous->records() as $old) {
            if (!$current->contains($old->symbol_id)) {
                $refresh->changes[] = new symbol_change($old, null, change_status::removed);
            }
        }
        return $refresh;
    }

    /** Reconcile external declarations by source scope, preserving exact retained contract identity. */
    private function import(Symbol_Store $current, \type_model\runtime_callable|\type_model\storage_family|\type_model\storage_function|\type_model\family_declaration|\type_model\family_method $declaration,
        int $owner = 0): symbol_record
    {
        $kind = match (true) {
            ($declaration instanceof \type_model\storage_family), ($declaration instanceof \type_model\family_declaration) => symbol_kind::template_struct,
            ($declaration instanceof \type_model\storage_function), ($declaration instanceof \type_model\family_method) => symbol_kind::template_function,
            default => symbol_kind::function_symbol,
        };
        $old_id = $this->previous->find_symbol($declaration->name, $declaration->namespace_name, $kind, $owner);
        $old = $old_id === 0 ? null : $this->previous->symbol_by_id($old_id);
        if (($old?->external instanceof \type_model\storage_function)
            && ($declaration instanceof \type_model\storage_function)
            && ($old->external->family === $declaration->family) && ($old->external->role === $declaration->role)) {
            $declaration = $old->external;
        }
        if (($old?->external instanceof \type_model\family_method) && ($declaration instanceof \type_model\family_method)
            && ($old->external->family === $declaration->family) && ($old->external->operation === $declaration->operation)) {
            $declaration = $old->external;
        }
        if (($old !== null) && ($old->external === $declaration)) {
            self::append($current, $old);
            return $old;
        }
        $symbol = new symbol_record($old_id === 0 ? $current->allocate_id() : $old_id, null, $declaration);
        $symbol->kind = $kind;
        $symbol->name = $declaration->name;
        $symbol->namespace_name = $declaration->namespace_name;
        $symbol->owner_symbol_id = $owner;
        if ($declaration instanceof \type_model\family_method) {
            $symbol->receiver_const = $declaration->operation->signature->parameters[$declaration->operation->receiver]->passing
                === \type_model\argument_passing::borrow_const;
        }
        self::append($current, $symbol);
        return $symbol;
    }

    /** Add a unique declaration to the candidate store, diagnosing conflicts between project and runtime names. */
    private static function append(Symbol_Store $current, symbol_record $symbol): void
    {
        $duplicate = $symbol->kind === symbol_kind::file_entry ? 0
            : $current->find_symbol($symbol->name, $symbol->namespace_name, $symbol->kind, $symbol->owner_symbol_id);
        // Templates share their language name category with ordinary declarations.
        $other_kind = match ($symbol->kind) {
            symbol_kind::template_struct => symbol_kind::struct_symbol,
            symbol_kind::struct_symbol => symbol_kind::template_struct,
            symbol_kind::template_function => symbol_kind::function_symbol,
            symbol_kind::function_symbol => symbol_kind::template_function,
            default => null,
        };
        if (($duplicate === 0) && ($other_kind !== null)) {
            $duplicate = $current->find_symbol($symbol->name, $symbol->namespace_name, $other_kind, $symbol->owner_symbol_id);
        }
        if ($duplicate !== 0)
        {
            $first = $current->symbol_by_id($duplicate);
            if (($symbol->external !== null) || ($first->external !== null)) {
                throw new \RuntimeException('Duplicate function name between project/runtime declarations: ' . $symbol->name);
            }
            $origin = $first->frontend;
            $first_node = $origin->syntax->nodes[$first->declaration_node_id - 1];
            $source = $symbol->frontend->tokens->source;
            $name = Declaration_Syntax::name_anchor($symbol);
            throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $name->start, $name->length,
                'Duplicate ' . $symbol->kind->name . " '" . $symbol->name . "'; first defined at "
                . $origin->tokens->source->path . ': byte ' . $first_node->start);
        }
        $current->add($symbol);
    }
}
