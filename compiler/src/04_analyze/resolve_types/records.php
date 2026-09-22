<?php
declare(strict_types=1);

/*
 * Role: Select source/provider record inputs and produce normalized field contracts.
 * Call map: Type_Resolver -> Record_Preparation::select(); resolve() [each task]
 * Flow: normalized private results -> Record_Join::join()
 * Inputs: fixed declarations/catalog; output: normalized declarations without canonical IDs.
 * Provider adapters supply record_declaration through this same selected-result contract.
 */
namespace resolve_types;

use parse\Syntax_Access;
use type_model\Type_Store;
use type_model\field_declaration;
use type_model\record_declaration;
use type_model\record_layout_policy;

/** Normalize selected source inputs and accept already-normalized provider inputs through one result protocol. */
final class Record_Preparation
{
    /** Select before extraction; unchanged declarations retain their canonical field/type records.
     * @return list<record_task> Fixed declaration/catalog inputs. */
    public static function select(\collect_symbols\Symbol_Store $symbols, \type_model\Type_Catalog $catalog,
        Type_Store $types, bool $full_rebuild, \resolve_symbols\Resolution_Set $names): array
    {
        $tasks = [];
        foreach ($symbols->records() as $owner) {
            if (($owner->kind === \collect_symbols\symbol_kind::struct_symbol)
                && (($full_rebuild) || ($types->find_type($owner->name, $owner->namespace_name) === 0))) {
                $tasks[] = new record_task($owner, $catalog, $names, symbols: $symbols);
            }
        }
        foreach ($catalog->records() as $record) {
            if (($full_rebuild) || ($types->find_type($record->name, $record->namespace_name) === 0)) {
                $tasks[] = new record_task($record, $catalog, $names);
            }
        }
        return $tasks;
    }

    /** Normalize public scalar/array fields without allocating canonical IDs or mutating inputs. */
    public static function resolve(record_task $task): record_result
    {
        if ($task->input instanceof record_declaration) {
            return new record_result($task, $task->input);
        }

        // Syntax_Access owns child roles; this worker owns field meaning and source diagnostics.
        $owner = $task->input;
        $tree = $owner->frontend->syntax;
        $parts = Syntax_Access::struct_parts($tree, \parse\Syntax_Access::underlying_declaration($tree, $owner->declaration_node_id));
        $fields = [];
        $seen = [];
        foreach (Syntax_Access::struct_members($tree, $owner->declaration_node_id, \parse\syntax_kind::field_declaration) as $id)
        {
            $field_parts = Syntax_Access::field_declaration_parts($tree, $id);
            $definition = Annotation_Types::definition($owner, $field_parts->type_syntax_id, $task->definitions ?? $task->catalog, 'field', $task->names, $task->instance, $task->instances);
            $field = substr(\collect_symbols\Declaration_Syntax::name_text($owner->frontend, $field_parts->variable_id), 1);
            if (isset($seen[$field]) || (!$definition->struct_field)) {
                self::fail($owner, $id, 'Duplicate field or unsupported struct field contract');
            }
            $seen[$field] = true;
            if ($field_parts->extent_id !== 0)
            {
                if (($definition->resource !== null) || ($definition->resource_paths !== [])) {
                    self::fail($owner, $id, 'Arrays of allocation owners require dynamic subobject ownership contracts');
                }
                $definition = new \type_model\array_type_definition($definition, self::extent($task, $field_parts->extent_id));
            }
            $fields[] = new field_declaration($field, $definition);
        }
        if ($fields === []) {
            self::fail($owner, $owner->declaration_node_id, 'Empty structs are unsupported in this slice');
        }
        [$constructor, $destructor, $copy, $assignment] = Source_Lifecycle::bodies($task);
        return new record_result($task, new record_declaration($task->instance?->type_name() ?? $owner->name, $task->instance?->type_namespace() ?? $owner->namespace_name, $fields, true, record_layout_policy::target, constructor_body: $constructor, destructor_body: $destructor, copy_body: $copy, assignment_body: $assignment));
    }

    /** Read a positive literal constant from the fixed preparation context; never evaluate expressions. */
    public static function extent(record_task $task, int $node): int
    {
        $argument = \instantiate\Bindings::value($task->instance ?? new \instantiate\instance_context($task->input),
            $node, $task->names, $task->catalog, $task->instances ?? new \instantiate\Instance_Set());
        $count = filter_var($argument->value, FILTER_VALIDATE_INT);
        if (($count === false) || ($count <= 0)) {
            self::fail($task->input, $node, 'Fixed array extent must be a positive integer within compiler index capacity');
        }
        return $count;
    }

    private static function fail(\collect_symbols\symbol_record $owner, int $id, string $message): never
    {
        $node = $owner->frontend->syntax->nodes[$id - 1];
        $source = $owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length, $message);
    }
}
