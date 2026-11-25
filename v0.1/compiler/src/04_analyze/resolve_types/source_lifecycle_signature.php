<?php
declare(strict_types=1);
namespace resolve_types;
/** Exact type/passing validation for custom lifecycle bodies after receiver preparation. */
final class Source_Lifecycle_Signature {
    public static function validate(Signature_Request $request, Annotation_Types $annotations): void {
        $symbol = $request->input->owner; $role = Source_Lifecycle::role($symbol);
        if ($role === \type_model\LIFECYCLE_NONE) { return; }
        $copy = \type_model\Lifecycle_Roles::has_source($role); $count = $copy ? 2 : 1;
        $valid = ($request->definition->representation->kind() === \type_model\REPRESENTATION_VOID)
            && ($request->parameter_count() === $count) && ((int)$symbol->source_fact()->body_node_id !== 0);
        $instance = $request->input->instance;
        if ($instance === null) { $valid = false; }
        else {
            if ($instance->receiver_type === null) { $valid = false; }
            if ($request->parameter_count() === $count) {
                if (($request->parameter_at(0) !== $instance->receiver_type) || ($request->passing_at(0) !== \type_model\PASS_BORROW_MUTABLE)) { $valid = false; }
                if ($copy) {
                    if (($request->parameter_at(1) !== $instance->receiver_type) || ($request->passing_at(1) !== \type_model\PASS_BORROW_CONST)) { $valid = false; }
                }
            }
        }
        if (!$valid) { $annotations->fail($symbol,(int)$symbol->source_fact()->name_node_id,'Custom lifecycle body must return void and borrow its mutable receiver plus an exact const source for copying'); }
    }
}
