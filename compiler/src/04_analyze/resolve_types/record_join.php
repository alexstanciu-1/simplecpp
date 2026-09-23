<?php
declare(strict_types=1);
namespace resolve_types;
/** Validate source/provider record batches before canonical allocation in a private candidate. */
final class Record_Join {
    private array $tasks /** vector<Record_Task> */ = [];
    public function __construct(private readonly \type_model\Type_Store $types,
        array $tasks /** vector<Record_Task> */, private readonly \collect_symbols\Symbol_Store $symbols) {
        foreach ($tasks as $task) { $this->tasks[] = $task; }
    }
    public function join(array $results /** vector<Record_Result> */): \type_model\Type_Store {
        $selected /** hash<Record_Task> */ = [];
        foreach ($this->tasks as $task) {
            $this->require_task($task); $key = $task->key();
            if (isset($selected[$key])) {
                $other = $selected[$key];
                $different = $task->provided !== $other->provided;
                if ($task->context !== null) {
                    if ($other->context === null) { $different = true; }
                    else { $different = $task->context->definition !== $other->context->definition; }
                } else if ($other->context !== null) { $different = true; }
                if ($different) {
                    if ($task->context !== null) { Record_Join::duplicate_type($task); }
                    if ($other->context !== null) { Record_Join::duplicate_type($other); }
                }
                throw new \LogicException('Duplicate source/provider record task');
            }
            $selected[$key] = $task;
        }
        $accepted /** hash<Record_Result> */ = [];
        foreach ($results as $result) {
            $task = $result->task; $key = $task->key();
            if (!isset($selected[$key])) { throw new \LogicException('Unexpected record result'); }
            if ($selected[$key] !== $task) { throw new \LogicException('Unexpected record result task'); }
            if (isset($accepted[$key])) { throw new \LogicException('Duplicate record result'); }
            if (!Record_Join::matches($task,$result->declaration)) { throw new \LogicException('Stale record result'); }
            if ($task->reader->catalog->find_type($result->declaration->name,$result->declaration->namespace_name) !== null) { Record_Join::duplicate_type($task); }
            $accepted[$key] = $result;
        }
        if (q_count($accepted) !== q_count($selected)) { throw new \LogicException('Incomplete record preparation'); }
        foreach ($this->tasks as $task) {
            $key = $task->key(); Record_Definitions::materialize($this->types,$accepted[$key]->declaration);
        }
        return $this->types;
    }
    private function require_task(Record_Task $task): void {
        if ($task->symbols !== $this->symbols) { throw new \LogicException('Stale record symbol snapshot'); }
        if ($task->reader->catalog->content_key !== $this->types->context->provider_key) { throw new \LogicException('Stale record catalog'); }
        if ($task->provided !== null) {
            if ($task->reader->catalog->find_record($task->provided->name,$task->provided->namespace_name) !== $task->provided) { throw new \LogicException('Stale provider record task'); }
            return;
        }
        $context = $task->context; if ($context === null) { throw new \LogicException('Missing record context'); }
        $owner = $context->definition;
        if (!$this->symbols->contains($owner->symbol_id)) { throw new \LogicException('Stale source record task'); }
        if ($this->symbols->symbol_by_id($owner->symbol_id) !== $owner) { throw new \LogicException('Stale source record task'); }
        if ($owner->kind() === \collect_symbols\SYMBOL_TEMPLATE_STRUCT) {
            if ($context->instance_id === 0) { throw new \LogicException('Template record requires a concrete context'); }
            if ($task->reader->instances->context_for($context->context_id) !== $context) { throw new \LogicException('Stale record instance'); }
        } else {
            if (($owner->kind() !== \collect_symbols\SYMBOL_STRUCT) || ($context->instance_id !== 0)) { throw new \LogicException('Source record task requires a record declaration'); }
        }
    }
    private static function matches(Record_Task $task, \type_model\Record_Declaration $record): bool {
        if ($task->provided !== null) { return $task->provided === $record; }
        $context = $task->context; if ($context === null) { return false; }
        if (($record->name !== $task->name()) || ($record->namespace_name !== $task->namespace_name()) || !$record->automatic_lifecycle
            || ($record->layout_policy !== \type_model\RECORD_LAYOUT_TARGET) || ($record->native_layout !== null)) { return false; }
        $owner = $context->definition;
        $bodies = Source_Lifecycle_Bodies::prepare($owner,$task->symbols,$task->reader->annotations);
        if (($record->constructor_body !== (int)$bodies->constructor) || ($record->destructor_body !== (int)$bodies->destructor)
            || ($record->copy_body !== (int)$bodies->copy) || ($record->assignment_body !== (int)$bodies->assignment)) { return false; }
        $tree = $owner->source_frontend()->tree;
        $cursor = \parse\Syntax_Access::struct_members($tree,(int)$owner->source_fact()->declaration_node_id,\parse\SYNTAX_FIELD_DECLARATION);
        $position = 0;
        while ($cursor->advance()) {
            if ($position >= $record->field_count()) { return false; }
            $parts = \parse\Syntax_Access::field_declaration_parts($tree,$cursor->current()); $field = $record->field_at($position);
            $spelling = \collect_symbols\File_Collector::name_text($owner->source_frontend(),(int)$parts->variable_id);
            $name = string_byte_slice($spelling,1,string_byte_len($spelling)-1);
            $element = Annotation_Types::definition($context,(int)$parts->type_syntax_id,'field',$task->reader);
            $extent = 0; if ((int)$parts->extent_id !== 0) { $extent = Record_Preparation::extent($task,(int)$parts->extent_id); }
            if (($field->name !== $name) || !$field->writable || ($field->definition->element !== $element) || ($field->definition->extent !== $extent)) { return false; }
            $position++;
        }
        return $position === $record->field_count();
    }
    private static function duplicate_type(Record_Task $task): void {
        $context = $task->context;
        if ($context !== null) {
            $owner = $context->definition;
            $task->reader->annotations->fail($owner,(int)$owner->source_fact()->name_node_id,'Duplicate source/provider type: ' . $owner->name);
        }
        throw new \LogicException('Duplicate provider type: ' . $task->name());
    }
}
