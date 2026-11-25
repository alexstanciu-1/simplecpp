<?php
declare(strict_types=1);
namespace resolve_types;
/** Shared selection/acceptance check. Equal IDs or shapes do not establish reuse. */
final class Signature_Validity {
    public static function is_current(Signature_Set $previous, \type_model\Type_Store $types,
        Callable_Input $input, array $prepared /** hash<\type_model\Runtime_Callable,int> */): bool {
        $signature = $previous->for_callable($input->callable_id);
        if ($signature === null) { return false; }
        if (($signature->input->owner !== $input->owner) || ($signature->input->instance !== $input->instance)) { return false; }
        if (!$input->owner->is_source()) {
            $provider = $input->owner->provider();
            if ($provider->kind() === \collect_symbols\PROVIDER_STORAGE_FUNCTION) {
                if ($signature->storage !== $provider->storage_function()) { return false; }
            } else {
                if ($signature->external !== Callable_Inputs::external($input,$prepared)) { return false; }
            }
        }
        if ($previous->types->lineage !== $types->lineage) { return false; }
        $id = $signature->representation_id;
        if (($id > $previous->types->representation_count()) || ($id > $types->representation_count())) { return false; }
        $old_shape = $previous->types->representation_by_id($id); $shape = $types->representation_by_id($id);
        if (($shape !== $old_shape) || ($shape->kind() !== \type_model\REPRESENTATION_SIGNATURE)) { return false; }
        if (!Signature_Validity::same_type($previous->types,$types,$shape->signature_return())) { return false; }
        for ($i = 0; $i < $shape->member_count(); $i++) {
            $position = $shape->member_first()+$i;
            if ($position >= $types->member_count()) { return false; }
            if (!Signature_Validity::same_type($previous->types,$types,$types->member_at($position)->type_id)) { return false; }
        }
        return true;
    }
    private static function same_type(\type_model\Type_Store $previous, \type_model\Type_Store $current, int $id): bool {
        if (($id < 1) || ($id > $previous->type_count()) || ($id > $current->type_count())) { return false; }
        return ($previous->type_by_id($id) === $current->type_by_id($id)) && (!$current->needs_representation($id));
    }
}
