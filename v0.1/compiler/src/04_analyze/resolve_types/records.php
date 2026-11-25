<?php
declare(strict_types=1);
namespace resolve_types;
/** Normalize field/lifecycle contracts without canonical allocation. */
final class Record_Preparation {
    public static function select(\collect_symbols\Symbol_Store $symbols, \instantiate\Bindings $reader,
        \type_model\Type_Store $types, bool $full_rebuild): array /** vector<Record_Task> */ {
        $tasks /** vector<Record_Task> */ = [];
        for ($i = 0; $i < $symbols->size(); $i++) {
            $owner = $symbols->record_at($i);
            if (!$owner->is_source()) { continue; }
            if ($owner->kind() !== \collect_symbols\SYMBOL_STRUCT) { continue; }
            if ($full_rebuild || ($types->find_type($owner->name,$owner->namespace_name) === 0)) {
                $tasks[] = new Record_Task($reader,$symbols,\instantiate\Instance_Context::ordinary($owner));
            }
        }
        $catalog = $reader->catalog;
        for ($i = 0; $i < $catalog->record_count(); $i++) {
            $record = $catalog->record_at($i);
            if ($full_rebuild || ($types->find_type($record->name,$record->namespace_name) === 0)) { $tasks[] = new Record_Task($reader,$symbols,null,$record); }
        }
        return $tasks;
    }
    public static function resolve(Record_Task $task): Record_Result {
        if ($task->provided !== null) { return new Record_Result($task,$task->provided); }
        $context = $task->context; if ($context === null) { throw new \LogicException('Missing source record context'); }
        $owner = $context->definition; $tree = $owner->source_frontend()->tree;
        $fields /** vector<\type_model\Field_Declaration> */ = []; $seen /** hash<bool> */ = [];
        $cursor = \parse\Syntax_Access::struct_members($tree,(int)$owner->source_fact()->declaration_node_id,\parse\SYNTAX_FIELD_DECLARATION);
        while ($cursor->advance()) {
            $id = $cursor->current(); $parts = \parse\Syntax_Access::field_declaration_parts($tree,$id);
            $element = Annotation_Types::definition($context,(int)$parts->type_syntax_id,'field',$task->reader);
            $spelling = \collect_symbols\File_Collector::name_text($owner->source_frontend(),(int)$parts->variable_id);
            $name = string_byte_slice($spelling,1,string_byte_len($spelling)-1);
            if (isset($seen[$name]) || !$element->struct_field) { $task->reader->annotations->fail($owner,$id,'Duplicate field or unsupported struct field contract'); }
            $seen[$name] = true; $field_type = \type_model\Field_Type::named($element);
            if ((int)$parts->extent_id !== 0) {
                $ownership = $element->ownership;
                if ($ownership !== null) {
                    if ($ownership->has_owners()) { $task->reader->annotations->fail($owner,$id,'Arrays of allocation owners require dynamic subobject ownership contracts'); }
                }
                $field_type = \type_model\Field_Type::fixed_array($element,Record_Preparation::extent($task,(int)$parts->extent_id));
            }
            $fields[] = new \type_model\Field_Declaration($name,$field_type,true);
        }
        if (q_count($fields) === 0) { $task->reader->annotations->fail($owner,(int)$owner->source_fact()->declaration_node_id,'Empty structs are unsupported in this slice'); }
        $bodies = Source_Lifecycle_Bodies::prepare($owner,$task->symbols,$task->reader->annotations);
        $record = new \type_model\Record_Declaration($task->name(),$task->namespace_name(),$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,(int)$bodies->constructor,(int)$bodies->destructor,(int)$bodies->copy,(int)$bodies->assignment);
        return new Record_Result($task,$record);
    }
    /** Exact positive extent bounded by the compiler's signed 64-bit index domain. */
    public static function extent(Record_Task $task, int $node): int {
        $context = $task->context; if ($context === null) { throw new \LogicException('Provider records already carry normalized extents'); }
        $argument = $task->reader->value($context,$node); $value = $argument->value;
        if ($value === null) { throw new \LogicException('Value binding lost its integer spelling'); }
        $valid = ($value !== '') && ($value !== '0');
        for ($i = 0; $i < string_byte_len($value); $i++) {
            $digit = string_byte_at($value,$i); if (($digit < 48) || ($digit > 57)) { $valid = false; }
        }
        if ($valid) { $valid = \check_bodies\Decimal_Range::fits_positive($value,63); }
        if (!$valid) { $task->reader->annotations->fail($context->definition,$node,'Fixed array extent must be a positive integer within compiler index capacity'); }
        $count = 0;
        for ($i = 0; $i < string_byte_len($value); $i++) { $count = $count * 10 + (string_byte_at($value,$i) - 48); }
        return $count;
    }
}
