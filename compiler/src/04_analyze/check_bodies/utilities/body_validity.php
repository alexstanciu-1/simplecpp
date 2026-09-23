<?php
declare(strict_types=1);
namespace check_bodies;
/** Shared reuse rule for selection and complete batch acceptance. */
final class Body_Validity {
    public static function is_current(Checked_Body $body, \resolve_types\Callable_Input $input,
        \resolve_symbols\Resolution_Set $names, \resolve_types\Type_Resolution $types): bool {
        if (($body->input->owner !== $input->owner) || ($body->input->instance !== $input->instance)
            || ($body->names !== $names->for_symbol($input->owner->symbol_id))
            || ($body->local_types !== $types->locals_for($input->callable_id))) { return false; }
        $signatures = $body->signature_dependencies(); $own = $input->callable_id;
        if (!isset($signatures[$own])) { return false; }
        foreach ($body->type_dependencies() as $id => $record) {
            if (($id < 1) || ($id > $types->types->type_count())) { return false; }
            if ($types->types->type_by_id($id) !== $record) { return false; }
        }
        foreach ($signatures as $id => $dependency) {
            $signature = $types->for_callable($id);
            if ($signature === null) { return false; }
            if (($signature->representation_id !== $dependency->representation_id) || ($signature->external !== $dependency->external)
                || ($signature->storage !== $dependency->storage)) { return false; }
            $representation = $signature->representation_id;
            if (($representation < 1) || ($representation > $types->types->representation_count())) { return false; }
            if ($types->types->representation_by_id($representation) !== $dependency->representation) { return false; }
            // Parameter IDs live in canonical member rows in the migrated model.
            $shape = $dependency->representation;
            if ($dependency->parameter_count() !== $shape->member_count()) { return false; }
            for ($i = 0; $i < $dependency->parameter_count(); $i++) {
                $position = $shape->member_first()+$i;
                if (($position < 0) || ($position >= $types->types->member_count())) { return false; }
                if ($types->types->member_at($position)->type_id !== $dependency->parameter_type($i)) { return false; }
            }
        }
        return true;
    }
}
