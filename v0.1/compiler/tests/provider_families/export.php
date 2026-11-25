<?php
declare(strict_types=1);
// Host-only normalization lets the untouched prototype validate the same declaration facts.
function reference_row(\type_model\Type_Reference $reference): array {
    if ($reference->kind === \type_model\TYPE_REFERENCE_FAMILY) {
        $arguments = [];
        for ($i=0; $i<$reference->argument_count(); $i++) { $arguments[]=reference_row($reference->argument_at($i)); }
        return ['family', $reference->family_key(), $arguments];
    }
    return match ($reference->kind) {
        \type_model\TYPE_REFERENCE_NAMED => ['named', $reference->name(), $reference->namespace_name()],
        \type_model\TYPE_REFERENCE_PROVIDER => ['provider', $reference->provider(), $reference->id()],
        \type_model\TYPE_REFERENCE_PARAMETER => ['parameter', $reference->owner(), $reference->parameter_slot()],
    };
}
$rows = [];
for ($case=0; $case<33; $case++) {
    $family = \family_test\Probe::candidate($case);
    $row = ['provider'=>$family->provider, 'id'=>$family->id, 'parameters'=>[], 'operations'=>[],
        'lifecycle'=>$family->lifecycle_bindings(), 'language'=>$family->language_type === null ? null : reference_row($family->language_type)];
    for ($i=0; $i<$family->parameter_count(); $i++) { $row['parameters'][]=$family->parameter_at($i)->name; }
    for ($i=0; $i<$family->operation_count(); $i++) {
        $op=$family->operation_at($i); $signature=$op->signature;
        $operation=['id'=>$op->id,'parameters'=>[],'result'=>[reference_row($signature->result->type),\type_model\Semantic_Modes::result_name($signature->result->production)],
            'requirements'=>[], 'effects'=>[], 'receiver'=>$op->receiver, 'exposure'=>$op->expose_as===null ? null : reference_row($op->expose_as)];
        for ($j=0;$j<$signature->parameter_count();$j++) {
            $param=$signature->parameter_at($j);$operation['parameters'][]=[reference_row($param->type),\type_model\Semantic_Modes::passing_name($param->passing)];
        }
        for ($j=0;$j<$op->requirement_count();$j++) {
            $req=$op->requirement_at($j);$operation['requirements'][]=[$req->slot,\type_model\Lifecycle_Roles::name($req->operation)];
        }
        for ($j=0;$j<$op->effect_count();$j++) {
            $effect=$op->effect_at($j);$operation['effects'][]=[$effect->kind===\type_model\ELEMENT_SAFE_INPUT ? 'safe_element_input' : 'invalidate_elements',$effect->receiver,$effect->safe_input];
        }
        $row['operations'][]=$operation;
    }
    $rows[]=$row;
}
echo json_encode($rows,JSON_THROW_ON_ERROR), "\n";
