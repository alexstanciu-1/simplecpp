<?php
declare(strict_types=1);

/*
 * Role: Accepted definition permissions and their exact declaration dependencies.
 * Used by: Template_Checker; instantiation and concrete body consumers
 * Flow: private definition results -> join -> immutable retained Template_Set
 */
namespace check_templates;

final class definition_result
{
    /** Dependencies anchor symbolic signatures/fields, not expanded concrete bodies.
     * @param array<int, \collect_symbols\symbol_record> $dependencies
     * @param array<int, \resolve_symbols\Symbol_Resolution> $bindings */
    public function __construct(public readonly definition_task $task,
        public readonly \type_model\Type_Catalog $catalog, public readonly array $dependencies,
        public readonly array $bindings, public readonly int $visited_nodes)
    {
    }

    /** Reuse only with exact source, catalog and referenced declaration/binding snapshots. */
    public function current(\collect_symbols\symbol_record $owner, \resolve_symbols\Resolution_Set $names,
        \type_model\Type_Catalog $catalog): bool
    {
        if (($this->task->owner !== $owner) || ($this->task->bindings !== $names->for_symbol($owner->symbol_id))
            || ($this->catalog !== $catalog)) {
            return false;
        }
        foreach ($this->dependencies as $id => $dependency) {
            if ($names->declaration_for($id) !== $dependency) {
                return false;
            }
        }
        foreach ($this->bindings as $id => $binding) {
            if ($names->for_symbol($id) !== $binding) {
                return false;
            }
        }
        return true;
    }
}

final class Template_Set implements \compile\Step_Result
{
    /** Rows are keyed by source definition identity; unchanged rows are shared between updates.
     * @param array<int, definition_result> $definitions */
    public function __construct(public readonly array $definitions = [], public readonly int $selected_count = 0)
    {
    }

    /** Consumers cannot admit a source template using only a concrete type/signature. */
    public function require_definition(\collect_symbols\symbol_record $owner,
        \resolve_symbols\Resolution_Set $names, \type_model\Type_Catalog $catalog): void
    {
        if (($owner->external === null) && $owner->is_template()
            && !(($this->definitions[$owner->symbol_id] ?? null)?->current($owner, $names, $catalog) ?? false)) {
            throw new \LogicException('Source template requires its current definition permission result');
        }
    }

    /** Export provenance and work counts without retaining expanded bodies or symbolic scratch. */
    public function to_array(): array
    {
        $rows = [];
        foreach ($this->definitions as $id => $result) {
            $rows[] = ['definition_id' => $id, 'dependencies' => array_keys($result->dependencies),
                'visited_nodes' => $result->visited_nodes];
        }
        return ['definitions' => $rows, 'selected_count' => $this->selected_count];
    }
}
