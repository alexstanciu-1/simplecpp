<?php
declare(strict_types=1);
namespace resolve_types;
/** Last type-association join. Signature parameters are a fixed prefix, not worker output. */
final class Local_Type_Join {
    private array $tasks /** vector<Callable_Input> */ = [];
    public function __construct(private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \instantiate\Bindings $reader, private readonly \type_model\Type_Store $types,
        private readonly Local_Type_Validity $history, array $tasks /** vector<Callable_Input> */,
        private readonly Entry_Contract $entry, private readonly Signature_Set $signatures) {
        foreach ($tasks as $task) { $this->tasks[] = $task; }
    }
    public function join(array $results /** vector<Local_Type_Request> */): array /** vector<Local_Types> */ {
        $definitions = $this->reader->annotations->definitions;
        if (!$this->symbols->contains($this->entry->symbol->symbol_id)) { throw new \LogicException('Stale local entry contract'); }
        if (($this->symbols->symbol_by_id($this->entry->symbol->symbol_id) !== $this->entry->symbol)
            || ($this->entry->return_type !== $definitions->entry_return_type())) { throw new \LogicException('Stale local entry contract'); }
        $previous = $this->history->previous;
        if ($previous !== null) { if ($previous->types === $this->types) { throw new \LogicException('Local join requires a separate candidate'); } }
        if (($this->types->context->provider_key !== $definitions->content_key())
            || ($this->types->context->target_key !== $definitions->representation_scope())
            || ($this->signatures->types !== $this->types)) { throw new \LogicException('Local join requires current canonical context'); }
        $selected /** hash<Callable_Input,int> */ = [];
        foreach ($this->tasks as $input) {
            $id = $input->callable_id;
            if (isset($selected[$id])) { throw new \LogicException('Duplicate local task'); }
            if (!Callable_Inputs::is_current($input,$this->symbols,$this->reader->instances)) { throw new \LogicException('Stale local task'); }
            if (!Signature_Resolver::body_participates($input,$this->entry)) { throw new \LogicException('Nonparticipating local task'); }
            if (Local_Type_Validity::names_for($input->owner,$this->reader->annotations->names)->locals_count() === 0) { throw new \LogicException('Empty local task'); }
            $selected[$id] = $input;
        }
        $requests /** hash<Local_Type_Request,int> */ = [];
        foreach ($results as $result) {
            $id = $result->input->callable_id;
            if (!isset($selected[$id])) { throw new \LogicException('Unexpected local result'); }
            if (($selected[$id] !== $result->input) || isset($requests[$id])
                || ($result->names !== $this->reader->annotations->names->for_symbol($result->input->owner->symbol_id))
                || ($result->size() !== $result->names->locals_count()-$result->names->runtime_parameter_count())) { throw new \LogicException('Duplicate or stale local result'); }
            $tree = $result->input->owner->source_frontend()->tree;
            for ($i = 0; $i < $result->size(); $i++) {
                $local = $result->names->locals_at($result->names->runtime_parameter_count()+$i);
                $node = (int)\parse\Syntax_Access::local_declaration_parts($tree,(int)$local->declaration_node_id)->type_syntax_id;
                $definition = $result->at($i);
                if ((Annotation_Types::definition($result->input->context(),$node,'local',$this->reader) !== $definition)
                    || ($definition->representation->kind() === \type_model\REPRESENTATION_VOID)) { throw new \LogicException('Stale local definition'); }
            }
            $requests[$id] = $result;
        }
        if (q_count($requests) !== q_count($selected)) { throw new \LogicException('Incomplete local phase'); }
        $inputs = Callable_Inputs::all($this->symbols,$this->reader->instances);
        foreach ($inputs as $input) {
            if (!Signature_Resolver::body_participates($input,$this->entry)) { continue; }
            $names = Local_Type_Validity::names_for($input->owner,$this->reader->annotations->names);
            if ($names->locals_count() === 0) { continue; }
            if (!isset($requests[$input->callable_id])) {
                if (!$this->history->is_current($this->types,$names,$input)) { throw new \LogicException('Incomplete or stale local phase'); }
            }
        }
        foreach ($requests as $id => $request) {
            $signature = $this->signatures->for_callable($id);
            if ($signature === null) { throw new \LogicException('Missing local signature'); }
            if (($signature->input->owner !== $request->input->owner) || ($signature->input->instance !== $request->input->instance)) { throw new \LogicException('Stale local signature'); }
            if ($this->types->representation_by_id($signature->representation_id)->member_count() !== $request->names->runtime_parameter_count()) { throw new \LogicException('Local parameter count differs from signature'); }
        }
        $locals /** vector<Local_Types> */ = [];
        foreach ($inputs as $input) {
            if (!Signature_Resolver::body_participates($input,$this->entry)) { continue; }
            $names = Local_Type_Validity::names_for($input->owner,$this->reader->annotations->names);
            if ($names->locals_count() === 0) { continue; }
            $id = $input->callable_id;
            if (!isset($requests[$id])) { $locals[] = $this->history->retained($id); continue; }
            $signature = $this->signatures->for_callable($id); if ($signature === null) { throw new \LogicException('Missing validated local signature'); }
            $shape = $this->types->representation_by_id($signature->representation_id); $ids /** vector<int> */ = [];
            for ($i = 0; $i < $shape->member_count(); $i++) { $ids[] = $this->types->member_at($shape->member_first()+$i)->type_id; }
            $request = $requests[$id];
            for ($i = 0; $i < $request->size(); $i++) { $ids[] = Type_Cache::materialize($this->types,$request->at($i)); }
            $locals[] = new Local_Types($names,$ids,$input->instance);
        }
        return $locals;
    }
}
