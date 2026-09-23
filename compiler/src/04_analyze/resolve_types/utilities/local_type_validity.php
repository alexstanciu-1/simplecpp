<?php
declare(strict_types=1);
namespace resolve_types;
/** Optional prior completed snapshot; shared read-only checks for selection and join. */
final class Local_Type_Validity {
    public function __construct(public readonly ?Type_Resolution $previous = null) {}
    public static function names_for(\collect_symbols\Symbol_Record $owner, \resolve_symbols\Resolution_Set $names): \resolve_symbols\Symbol_Resolution {
        $result = $names->for_symbol($owner->symbol_id);
        if ($result === null) { throw new \LogicException('Missing local type bindings'); }
        if ($result->owner !== $owner) { throw new \LogicException('Stale local type bindings'); }
        if ($result->scopes_count() === 0) { throw new \LogicException('Local types require a callable body'); }
        if ((int)$result->scopes_at(0)->block_node_id !== (int)$owner->source_fact()->body_node_id) { throw new \LogicException('Stale local type root scope'); }
        return $result;
    }
    public function is_current(\type_model\Type_Store $types, \resolve_symbols\Symbol_Resolution $names, Callable_Input $input): bool {
        $previous = $this->previous; if ($previous === null) { return false; }
        if ($previous->types->lineage !== $types->lineage) { return false; }
        $local = $previous->locals_for($input->callable_id); if ($local === null) { return false; }
        if (($local->instance !== $input->instance) || ($local->names !== $names) || ($names->owner !== $input->owner)) { return false; }
        for ($i = 1; $i < $local->size()+1; $i++) {
            $id = $local->type_for($i);
            if (($id > $previous->types->type_count()) || ($id > $types->type_count())) { return false; }
            if (($previous->types->type_by_id($id) !== $types->type_by_id($id)) || $types->needs_representation($id)) { return false; }
        }
        return true;
    }
    public function retained(int $id): Local_Types {
        $previous = $this->previous; if ($previous === null) { throw new \LogicException('No previous local types'); }
        $local = $previous->locals_for($id); if ($local === null) { throw new \LogicException('Missing previous local types'); } return $local;
    }
}
