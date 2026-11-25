<?php
declare(strict_types=1);
namespace resolve_types;
/** Validate the complete batch before writing its private canonical candidate. */
final class Signature_Join {
    private array $tasks /** vector<Callable_Input> */ = [];
    private array $prepared /** hash<\type_model\Runtime_Callable,int> */ = [];
    public function __construct(private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \instantiate\Bindings $reader, private readonly \type_model\Type_Store $types,
        private readonly Signature_Set $previous, array $tasks /** vector<Callable_Input> */,
        private readonly Entry_Contract $entry, array $prepared /** hash<\type_model\Runtime_Callable,int> */) {
        foreach ($tasks as $task) { $this->tasks[] = $task; }
        foreach ($prepared as $id => $callable) { $this->prepared[$id] = $callable; }
    }
    /** Publication order follows declarations then concrete instances, never worker completion.
     * Discard the candidate if canonical materialization fails after validation.
     */
    public function join(array $results /** vector<Signature_Request> */): Signature_Set {
        $definitions = $this->reader->annotations->definitions;
        if (!$this->symbols->contains($this->entry->symbol->symbol_id)) { throw new \LogicException('Stale entry contract'); }
        if (($this->symbols->symbol_by_id($this->entry->symbol->symbol_id) !== $this->entry->symbol)
            || ($this->entry->return_type !== $definitions->entry_return_type())) { throw new \LogicException('Stale entry contract'); }
        if (($this->types === $this->previous->types)
            || ($this->types->context->provider_key !== $definitions->content_key())
            || ($this->types->context->target_key !== $definitions->representation_scope())) { throw new \LogicException('Signature join requires a separate candidate with current catalog context'); }
        $selected /** hash<Callable_Input,int> */ = [];
        foreach ($this->tasks as $input) {
            $id = $input->callable_id;
            if (isset($selected[$id])) { throw new \LogicException('Duplicate signature task'); }
            if (!Callable_Inputs::is_current($input,$this->symbols,$this->reader->instances)) { throw new \LogicException('Stale signature task'); }
            if (!Signature_Resolver::participates($input,$this->entry)) { throw new \LogicException('Nonparticipating signature task'); }
            $selected[$id] = $input;
        }
        $requests /** hash<Signature_Request,int> */ = [];
        foreach ($results as $result) {
            $id = $result->input->callable_id;
            if (!isset($selected[$id])) { throw new \LogicException('Unexpected signature result'); }
            if (($selected[$id] !== $result->input) || isset($requests[$id])) { throw new \LogicException('Duplicate or stale signature result'); }
            $this->validate($result); $requests[$id] = $result;
        }
        if (q_count($requests) !== q_count($selected)) { throw new \LogicException('Incomplete signature phase'); }
        $inputs = Callable_Inputs::all($this->symbols,$this->reader->instances);
        foreach ($inputs as $input) {
            if (!Signature_Resolver::participates($input,$this->entry)) { continue; }
            if (!isset($requests[$input->callable_id])) {
                if (!Signature_Validity::is_current($this->previous,$this->types,$input,$this->prepared)) { throw new \LogicException('Incomplete or stale signature phase'); }
            }
        }
        $signatures /** vector<Callable_Signature> */ = [];
        foreach ($inputs as $input) {
            if (!Signature_Resolver::participates($input,$this->entry)) { continue; }
            $id = $input->callable_id;
            if (!isset($requests[$id])) {
                $retained = $this->previous->for_callable($id);
                if ($retained === null) { throw new \LogicException('Missing retained signature'); }
                $signatures[] = $retained; continue;
            }
            $request = $requests[$id]; $parameters /** vector<int> */ = []; $passing /** vector<int> */ = [];
            $return_type = Type_Cache::materialize($this->types,$request->definition);
            for ($i = 0; $i < $request->parameter_count(); $i++) {
                $parameters[] = Type_Cache::materialize($this->types,$request->parameter_at($i)); $passing[] = $request->passing_at($i);
            }
            $representation = $this->types->intern_signature($return_type,$parameters,$passing);
            $signatures[] = Signature_Join::association($input,$request->return_annotation_id,$representation,$this->prepared);
        }
        return new Signature_Set($this->types,$signatures);
    }
    private function validate(Signature_Request $result): void {
        $input = $result->input; $owner = $input->owner; $definitions = $this->reader->annotations->definitions;
        Source_Lifecycle_Signature::validate($result,$this->reader->annotations);
        if ($owner === $this->entry->symbol) {
            if (($result->return_annotation_id !== 0) || ($result->definition !== $this->entry->return_type) || ($result->parameter_count() !== 0)) { throw new \LogicException('Stale entry signature result'); }
            return;
        }
        if (!$owner->is_source()) {
            if ($owner->provider()->kind() === \collect_symbols\PROVIDER_STORAGE_FUNCTION) {
                if (!Storage_Signatures::matches($result,$definitions)) { throw new \LogicException('Stale storage signature result'); }
                return;
            }
            $external = Callable_Inputs::external($input,$this->prepared);
            if ($external === null) { throw new \LogicException('Missing provider callable'); }
            if (($result->return_annotation_id !== 0) || ($result->parameter_count() !== $external->signature->parameter_count())
                || !Signature_Join::matches_reference($external->signature->result->type,$result->definition,$definitions)) { throw new \LogicException('Stale provider signature result'); }
            for ($i = 0; $i < $result->parameter_count(); $i++) {
                if (($result->passing_at($i) !== $external->passing_for($i))
                    || !Signature_Join::matches_reference($external->signature->parameter_at($i)->type,$result->parameter_at($i),$definitions)) { throw new \LogicException('Stale provider parameter'); }
            }
            return;
        }
        $tree = $owner->source_frontend()->tree;
        $parts = \parse\Syntax_Access::function_parts($tree,\parse\Syntax_Access::underlying_declaration($tree,(int)$owner->source_fact()->declaration_node_id));
        $context = $input->context(); $return_node = (int)$parts->return_type_id;
        if (($result->return_annotation_id !== $return_node)
            || (Annotation_Types::definition($context,$return_node,'signature',$this->reader) !== $result->definition)) { throw new \LogicException('Stale return signature'); }
        $position = 0;
        if ($owner->owner_symbol_id !== 0) {
            $receiver = $context->receiver_type;
            if (($receiver === null) || ($result->parameter_count() === 0)) { throw new \LogicException('Missing receiver signature'); }
            $passing = \type_model\PASS_BORROW_MUTABLE; if ($owner->receiver_const()) { $passing = \type_model\PASS_BORROW_CONST; }
            if (($result->parameter_at(0) !== $receiver) || ($result->passing_at(0) !== $passing)) { throw new \LogicException('Stale receiver signature'); }
            $position = 1;
        }
        $node = \parse\Syntax_Access::first_parameter($tree,(int)$parts->parameters_id);
        while ($node !== 0) {
            if ($position >= $result->parameter_count()) { throw new \LogicException('Missing source parameter'); }
            $annotation = (int)\parse\Syntax_Access::parameter_parts($tree,$node)->type_syntax_id;
            $passing = Parameter_Contracts::passing($tree,$node); $definition = $result->parameter_at($position);
            if (($result->passing_at($position) !== $passing)
                || (Annotation_Types::definition($context,$annotation,'signature',$this->reader) !== $definition)) { throw new \LogicException('Stale parameter signature'); }
            Parameter_Contracts::validate($owner,$annotation,$passing,$definition,$this->reader->annotations);
            $position++; $node = (int)$tree->row($node)->next_sibling;
        }
        if ($position !== $result->parameter_count()) { throw new \LogicException('Stale source parameter count'); }
    }
    private static function matches_reference(\type_model\Type_Reference $reference, \type_model\Named_Definition $definition, Definition_View $view): bool {
        return $view->find_type($reference->name(),$reference->namespace_name()) === $definition;
    }
    private static function association(Callable_Input $input, int $annotation, int $representation,
        array $prepared /** hash<\type_model\Runtime_Callable,int> */): Callable_Signature {
        $owner = $input->owner; $receiver /** nullable<int> */ = null;
        if ($owner->owner_symbol_id !== 0) {
            $receiver = 0;
            if (!$owner->is_source()) {
                if ($owner->provider()->kind() === \collect_symbols\PROVIDER_METHOD) { $receiver = $owner->provider()->method()->operation->receiver; }
            }
        }
        if (!$owner->is_source()) {
            if ($owner->provider()->kind() === \collect_symbols\PROVIDER_STORAGE_FUNCTION) {
                return new Callable_Signature($input,$annotation,$representation,null,$owner->provider()->storage_function(),$receiver);
            }
        }
        return new Callable_Signature($input,$annotation,$representation,Callable_Inputs::external($input,$prepared),null,$receiver);
    }
}
