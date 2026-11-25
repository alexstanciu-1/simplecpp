<?php
declare(strict_types=1);

/*
 * Role: Accept selected structural preparation results into a private candidate.
 * Call map: preparation coordinator -> Record_Join::join()
 * Output: validated canonical contracts for downstream workers.
 */
namespace resolve_types;

use parse\Syntax_Access;
use type_model\Type_Store;
use type_model\field_declaration;
use type_model\record_declaration;
use type_model\record_layout_policy;

/** Accept source/provider worker results before exposing one fixed definition view to annotation workers. */
final class Record_Join implements \compile\Join
{
    /** @param list<record_task> $tasks Fixed selected source/provider definitions. */
    public function __construct(private readonly Type_Store $types, private readonly array $tasks,
        private readonly \collect_symbols\Symbol_Store $symbols)
    {
    }

    /** Validate the complete batch before materializing; failed private candidates are discarded.
     * @param list<record_result> $results Private normalized outputs. */
    public function join(array $results): Type_Store
    {
        // Establish current membership and one type namespace before accepting private outputs.
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $input = $task->input;
            $current = $input instanceof record_declaration
                ? $task->catalog->find_record($input->name, $input->namespace_name) === $input
                : ((($input->kind === \collect_symbols\symbol_kind::struct_symbol)
                        || (($input->kind === \collect_symbols\symbol_kind::template_struct) && (($task->instance?->definition === $input)
                            && ($task->instances?->context_for($task->instance->context_id) === $task->instance))))
                    && $this->symbols->contains($input->symbol_id)
                    && ($this->symbols->symbol_by_id($input->symbol_id) === $input));
            if ((!$current) || ($task->catalog->content_key !== $this->types->context->provider_key)
                || (($task->symbols !== null) && ($task->symbols !== $this->symbols))) {
                throw new \LogicException('Stale record task');
            }
            $key = self::key($task);
            if (isset($selected[$key]))
            {
                // Repeated tasks are a protocol error; distinct declarations are a source conflict.
                $other = $selected[$key]->input;
                if ($input !== $other) {
                    $source = $input instanceof record_declaration ? $other : $input;
                    if ($source instanceof \collect_symbols\symbol_record) {
                        self::duplicate_type($source);
                    }
                }
                throw new \LogicException('Duplicate source/provider record task');
            }
            $selected[$key] = $task;
        }

        // Validate all results before canonical materialization changes the private candidate.
        $accepted = [];
        foreach ($results as $result)
        {
            $task = $result->task;
            $id = self::key($task);
            if ((($selected[$id] ?? null) !== $task) || isset($accepted[$id])
                || !self::matches($task, $result->declaration)) {
                throw new \LogicException('Unexpected, duplicate or stale record result');
            }
            if ($task->catalog->find_type($result->declaration->name, $result->declaration->namespace_name) !== null) {
                self::duplicate_type($task->input);
            }
            $accepted[$id] = $result;
        }
        if (count($accepted) !== count($selected)) {
            throw new \LogicException('Incomplete record preparation');
        }

        // Apply accepted declarations in selection order so canonical ID allocation stays deterministic.
        foreach ($this->tasks as $task) {
            Record_Definitions::materialize($this->types, $accepted[self::key($task)]->declaration);
        }
        return $this->types;
    }

    /** Validate source anchors and shared definitions without rerunning semantic extraction. */
    private static function matches(record_task $task, record_declaration $record): bool
    {
        if ($task->input instanceof record_declaration) {
            return $task->input === $record;
        }
        $owner = $task->input;
        if (($record->name !== ($task->instance?->type_name() ?? $owner->name)) || ($record->namespace_name !== ($task->instance?->type_namespace() ?? $owner->namespace_name))
            || (!$record->automatic_lifecycle) || ($record->layout_policy !== record_layout_policy::target)
            || ($record->native_layout !== null) || !array_is_list($record->fields)) {
            return false;
        }
        if ([$record->constructor_body, $record->destructor_body, $record->copy_body, $record->assignment_body] !== Source_Lifecycle::bodies($task)) {
            return false;
        }
        $tree = $owner->frontend->syntax;
        $fields /** vector<int> */ = [];
        $member_cursor = Syntax_Access::struct_members($tree, $owner->declaration_node_id, \parse\syntax_kind::field_declaration);
        while ($member_cursor->advance()) {
            $fields[] = $member_cursor->current();
        }
        $position = 0;
        $id = $fields[$position] ?? 0;
        foreach ($record->fields as $field)
        {
            if (($id === 0) || !($field instanceof field_declaration)) {
                return false;
            }
            $parts = Syntax_Access::field_declaration_parts($tree, $id);
            $field_name = substr(\collect_symbols\Declaration_Syntax::name_text($owner->frontend, $parts->variable_id), 1);
            $element = Annotation_Types::definition($owner, $parts->type_syntax_id, $task->definitions ?? $task->catalog, 'field', $task->names, $task->instance, $task->instances);
            $matches = $parts->extent_id === 0 ? $field->definition === $element
                : (($field->definition instanceof \type_model\array_type_definition)
                    && ($field->definition->element === $element)
                    && ($field->definition->count === Record_Preparation::extent($task, $parts->extent_id)));
            if (($field->name !== $field_name) || (!$field->writable) || (!$matches)) {
                return false;
            }
            $id = $fields[++$position] ?? 0;
        }
        return $id === 0;
    }

    /** Report a declaration collision at the source name, without retaining the failed syntax snapshot. */
    private static function duplicate_type(\collect_symbols\symbol_record $owner): never
    {
        $node = \collect_symbols\Declaration_Syntax::name_anchor($owner);
        $source = $owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length,
            'Duplicate source/provider type: ' . $owner->name);
    }

    /** Exact qualified identity; declaration origin never creates a second type namespace. */
    private static function key(record_task $task): string
    {
        if ($task->instance !== null) {
            return 'instance:' . $task->instance->instance_id;
        }
        return strlen($task->input->namespace_name) . ':' . $task->input->namespace_name . $task->input->name;
    }
}
