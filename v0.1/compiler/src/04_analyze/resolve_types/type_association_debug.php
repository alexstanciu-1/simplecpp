<?php
declare(strict_types=1);
namespace resolve_types;
/** On-demand association projection, not a persisted cache or complete compiler dump. */
final class Type_Association_Debug {
    public static function encode(Type_Resolution $snapshot): string {
        $signatures = '['; $locals = '['; $signature_separator = ''; $local_separator = '';
        for ($i = 0; $i < $snapshot->callables->size(); $i++) {
            $signature = $snapshot->callables->at($i);
            $signatures .= $signature_separator . Type_Association_Debug::signature($snapshot,$signature); $signature_separator = ',';
            $local = $snapshot->locals_for($signature->callable_id);
            if ($local !== null) { $locals .= $local_separator . Type_Association_Debug::local($local); $local_separator = ','; }
        }
        $families = '['; $separator = '';
        foreach ($snapshot->prepared_families() as $id => $prepared) {
            $family = $prepared->task->context->definition->provider()->family();
            $operations = '['; $operation_separator = '';
            foreach ($prepared->callable_operations() as $operation_id => $callable) { $operations .= $operation_separator . json_quote($operation_id); $operation_separator = ','; }
            $families .= $separator . '{"instance_id":'.$id.',"provider":'.json_quote($family->provider).',"family":'.json_quote($family->id)
                .',"type_id":'.json_quote($prepared->type_id).',"operations":'.$operations.'],"package_directory":'.json_quote($prepared->package->directory).'}';
            $separator = ',';
        }
        return '{"signatures":'.$signatures.'],"local_types":'.$locals.'],"prepared_families":'.$families.'],"entry_symbol_id":'.$snapshot->entry->symbol->symbol_id.'}';
    }
    private static function signature(Type_Resolution $snapshot, Callable_Signature $signature): string {
        $owner = $signature->input->owner; $path = 'null'; $declaration = 0; $body = 0;
        if ($owner->is_source()) { $path = json_quote($owner->source_frontend()->tokens->source->path); $declaration = (int)$owner->source_fact()->declaration_node_id; $body = (int)$owner->source_fact()->body_node_id; }
        $operation = 'null'; if ($signature->external !== null) { $operation = json_quote($signature->external->id); }
        $receiver = 0; $receiver_json = 'null'; if (take_nullable($receiver,$signature->receiver_index)) { $receiver_json = '' . $receiver; }
        $shape = $snapshot->signature_for($signature->callable_id); $parameters = '['; $passing = '['; $separator = '';
        for ($p = 0; $p < $shape->member_count(); $p++) {
            $parameters .= $separator . $snapshot->parameter_type_for($signature->callable_id,$p+1);
            $passing .= $separator . json_quote(\type_model\Semantic_Modes::passing_name($shape->parameter_passing($p))); $separator = ',';
        }
        return '{"symbol_id":'.$owner->symbol_id.',"callable_id":'.$signature->callable_id.',"source_path":'.$path.',"provider_operation":'.$operation
            .',"receiver_index":'.$receiver_json.',"parameter_passing":'.$passing.'],"declaration_node_id":'.$declaration.',"return_annotation_id":'.$signature->return_annotation_id
            .',"body_node_id":'.$body.',"representation_id":'.$signature->representation_id.',"return_type_id":'.$shape->signature_return().',"parameter_type_ids":'.$parameters.']}';
    }
    private static function local(Local_Types $local): string {
        $rows = '['; $separator = '';
        for ($id = 1; $id < $local->size()+1; $id++) { $rows .= $separator . '{"local_id":'.$id.',"type_id":'.$local->type_for($id).'}'; $separator = ','; }
        return '{"symbol_id":'.$local->names->owner->symbol_id.',"callable_id":'.$local->callable_id.',"source_path":'.json_quote($local->names->owner->source_frontend()->tokens->source->path).',"locals":'.$rows.']}';
    }
}
