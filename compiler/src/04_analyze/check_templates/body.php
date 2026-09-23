<?php
declare(strict_types=1);
namespace check_templates;
/** @scpp-struct */
final class Expression_Visit {
    public int $node /** uint32 */ = 0;
    public bool $finish = false;
}
/** Check permissions once per definition; concrete conversion and lifetimes belong downstream. */
final class Template_Worker {
    private bool $started = false;
    private array $locals /** vector<Expression_Type> */ = [];
    private int $visited = 0;
    public function __construct(private readonly Definition_Task $task, private readonly Terms $terms) {
        if (!$task->owner->is_source()) { throw new \LogicException('Template worker requires a source definition'); }
        if (!$task->owner->is_template()) { throw new \LogicException('Template worker requires a template definition'); }

    }
    public static function create(Definition_Task $task, \collect_symbols\Symbol_Store $symbols,
        \resolve_symbols\Resolution_Set $names, \type_model\Type_Catalog $catalog): Template_Worker {
        return new Template_Worker($task,new Terms($symbols,$names,$catalog));
    }
    public function diagnostic(): ?Template_Diagnostic { return $this->terms->diagnostic(); }
    public function check(): Definition_Result {
        if ($this->started) { throw new \LogicException('Template worker is one-shot'); }
        $this->started = true;
        $owner = $this->task->owner;
        $current = $this->terms->declaration($owner->symbol_id);
        if ($current !== $owner) { throw new \LogicException('Stale template task owner'); }
        if ($this->terms->bindings($owner) !== $this->task->bindings) { throw new \LogicException('Stale template task bindings'); }
        $this->visited++;
        if ($owner->kind() === \collect_symbols\SYMBOL_TEMPLATE_STRUCT) { $this->fields(); }
        if ((int)$owner->source_fact()->body_node_id !== 0) { $this->prepare_locals(); $this->statements(); }
        return new Definition_Result($this->task,$this->terms->catalog,$this->terms->declarations(),$this->terms->resolutions(),$this->visited);
    }
    private function annotation(\collect_symbols\Symbol_Record $owner, int $node): Type_Term {
        $empty /** vector<Type_Term> */ = [];
        return $this->terms->annotation($owner,$node,$empty);
    }
    private function fields(): void {
        $owner = $this->task->owner; $defaults = false;
        foreach ($this->terms->symbols->child_symbol_ids($owner->symbol_id) as $id) {
            $member = $this->terms->declaration($id); $role = \resolve_types\Source_Lifecycle::role($member);
            if ($role !== \type_model\LIFECYCLE_NONE) {
                $order = \type_model\Lifecycle_Roles::composition($role,true);
                if ((int)$order->member_kind === \type_model\LIFECYCLE_DEFAULT) { $defaults = true; }
            }
        }
        $tree = $owner->source_frontend()->tree;
        $cursor = \parse\Syntax_Access::struct_members($tree,(int)$owner->source_fact()->declaration_node_id,\parse\SYNTAX_FIELD_DECLARATION);
        while ($cursor->advance()) {
            $field = $cursor->current(); $this->visited++;
            $parts = \parse\Syntax_Access::field_declaration_parts($tree,$field);
            $type = $this->annotation($owner,(int)$parts->type_syntax_id);
            if ($defaults && $type->dependent) { $this->terms->fail($owner,$field,'Generic contract does not permit implicit field default construction in custom lifecycle'); }
            if (((int)$parts->extent_id !== 0) && $type->dependent) { $this->terms->fail($owner,$field,'Generic fixed-array initialization is deferred; the default contract does not guarantee initialization'); }
        }
    }
    private function prepare_locals(): void {
        $owner = $this->task->owner; $tree = $owner->source_frontend()->tree;
        for ($index = 0; $index < $this->task->bindings->locals_count(); $index++) {
            $local = $this->task->bindings->locals_at($index); $node = (int)$local->declaration_node_id;
            if ($local->receiver) { $this->locals[] = new Expression_Type($this->terms->receiver($owner),$owner->receiver_const()); continue; }
            $parameter = (int)$tree->row($node)->kind === \parse\SYNTAX_PARAMETER_DECLARATION;
            $reference = 0; $type_node = 0;
            if ($parameter) {
                $parts = \parse\Syntax_Access::parameter_parts($tree,$node); $reference = (int)$parts->reference; $type_node = (int)$parts->type_syntax_id;
            } else { $parts_local = \parse\Syntax_Access::local_declaration_parts($tree,$node); $type_node = (int)$parts_local->type_syntax_id; }
            $type = $this->annotation($owner,$type_node);
            $readonly = $parameter && ($reference === \parse\SYNTAX_CONST_REFERENCE_ANNOTATION);
            if ($parameter && ($reference === 0)) { $this->terms->provider_value_use($type,$owner,$node); }
            if ($parameter && ($type->kind === \check_templates\TERM_PARAMETER) && ($reference === \parse\SYNTAX_REFERENCE_ANNOTATION)) {
                $this->terms->fail($owner,$node,'Mutable borrowing of bare generic T is not in the initial contract');
            }
            $this->locals[] = new Expression_Type($type,$readonly);
        }
    }
    /** Source order is preserved. Both runtime branches are checked without path expansion. */
    private function statements(): void {
        $owner = $this->task->owner; $tree = $owner->source_frontend()->tree;
        $parts = \parse\Syntax_Access::function_parts($tree,\parse\Syntax_Access::underlying_declaration($tree,(int)$owner->source_fact()->declaration_node_id));
        $return_type = $this->annotation($owner,(int)$parts->return_type_id);
        $pending /** vector<int> */ = [(int)$owner->source_fact()->body_node_id]; $used = 1;
        while ($used > 0) {
            $used = $used - 1; $id = $pending[$used]; $node = $tree->row($id); $kind = (int)$node->kind; $this->visited++;
            $children /** vector<int> */ = [];
            if ($kind === \parse\SYNTAX_BLOCK) { $children = $this->terms->arguments($owner,(int)$node->first_child); }
            elseif (($kind === \parse\SYNTAX_IF_STATEMENT) || ($kind === \parse\SYNTAX_WHILE_STATEMENT)) {
                $control = \parse\Syntax_Access::control_parts($tree,$id);
                $this->non_generic($this->expression((int)$control->condition),(int)$control->condition,'condition conversion');
                $children[] = (int)$control->body;
                if ((int)$control->alternative !== 0) { $children[] = (int)$control->alternative; }
            } elseif (($kind === \parse\SYNTAX_CONSTEXPR_IF_STATEMENT) || ($kind === \parse\SYNTAX_CONSTEVAL_IF_STATEMENT)) {
                $this->terms->fail($owner,$id,'Unsupported compile-time branch; constant evaluation is not implemented');
            } elseif ($kind === \parse\SYNTAX_LOCAL_DECLARATION) {
                $local = \parse\Syntax_Access::local_declaration_parts($tree,$id);
                $target = $this->locals[$this->task->bindings->local_for_declaration($id)-1];
                if ((int)$local->initializer_id === 0) {
                    $present = $target->type;
                    if ($present !== null) { $this->terms->default_construction($present,$owner,$id); }
                } else { $this->value_use($target,$id); $this->compatible($target,$this->expression((int)$local->initializer_id),$id); }
            } elseif ($kind === \parse\SYNTAX_ASSIGNMENT_STATEMENT) {
                $write = \parse\Syntax_Access::assignment_parts($tree,$id); $target = $this->expression((int)$write->target_id);
                if ($target->readonly) { $this->terms->fail($owner,$id,'Cannot assign through a const reference in a generic definition'); }
                $this->value_use($target,$id); $this->compatible($target,$this->expression((int)$write->value_id),$id);
            } elseif ($kind === \parse\SYNTAX_RETURN_STATEMENT) {
                if ((int)$node->first_child !== 0) {
                    $this->terms->provider_value_use($return_type,$owner,$id);
                    $this->compatible(new Expression_Type($return_type,false),$this->expression((int)$node->first_child),$id);
                }
            } elseif ($kind === \parse\SYNTAX_EXPRESSION_STATEMENT) { $this->expression((int)$node->first_child); }
            elseif ($kind === \parse\SYNTAX_ECHO_STATEMENT) {
                foreach ($this->terms->arguments($owner,(int)$node->first_child) as $value) { $this->non_generic($this->expression($value),$value,'output conversion'); }
            } else { $this->terms->fail($owner,$id,'Unsupported statement in generic definition: ' . ('syntax tag ' . $kind)); }
            $position = q_count($children);
            while ($position > 0) { $position = $position - 1; if ($used === q_count($pending)) { $pending[] = $children[$position]; } else { $pending[$used] = $children[$position]; } $used++; }
        }
    }
    /** Iterative postorder operands; ordinary literal/conversion typing stays downstream. */
    private function expression(int $root): Expression_Type {
        $owner = $this->task->owner; $tree = $owner->source_frontend()->tree;
        $first = new Expression_Visit(); $first->node = $root;
        $pending /** vector<Expression_Visit> */ = [$first]; $used = 1; $values /** hash<Expression_Type,int> */ = [];
        while ($used > 0) {
            $used = $used - 1; $visit = $pending[$used]; $id = (int)$visit->node; $node = $tree->row($id); $kind = (int)$node->kind;
            $children /** vector<int> */ = [];
            if ($kind === \parse\SYNTAX_CALL_EXPRESSION) {
                $target = \parse\Syntax_Access::call_target($tree,$id); $target_node = $tree->row($target);
                if ((int)$target_node->kind === \parse\SYNTAX_FIELD_EXPRESSION) { $children[] = (int)$target_node->first_child; }
                foreach ($this->terms->arguments($owner,\parse\Syntax_Access::first_argument($tree,$id)) as $argument) { $children[] = $argument; }
            } elseif ($kind === \parse\SYNTAX_FIELD_EXPRESSION) { $children[] = (int)$node->first_child; }
            elseif (($kind === \parse\SYNTAX_INDEX_EXPRESSION) || (\parse\Binary_Syntax::operation($kind) !== '')) { $children = $this->terms->arguments($owner,(int)$node->first_child); }
            if (!$visit->finish) {
                if (q_count($children) !== 0) {
                    $finish = new Expression_Visit(); $finish->node = $id; $finish->finish = true;
                    if ($used === q_count($pending)) { $pending[] = $finish; } else { $pending[$used] = $finish; } $used++;
                    $position = q_count($children);
                    while ($position > 0) {
                        $position = $position - 1; $child = new Expression_Visit(); $child->node = $children[$position];
                        if ($used === q_count($pending)) { $pending[] = $child; } else { $pending[$used] = $child; } $used++;
                    }
                    continue;
                }
            }
            $this->visited++; $value = new Expression_Type(null,false);
            if ($kind === \parse\SYNTAX_VARIABLE_NAME) { $value = $this->locals[(int)$this->task->bindings->binding_for($id)->local_id-1]; }
            elseif (($kind === \parse\SYNTAX_INTEGER_LITERAL) || ($kind === \parse\SYNTAX_NAME)) { $value = new Expression_Type(Type_Term::named($this->terms->catalog->integer_literal_type),false); }
            elseif (($kind === \parse\SYNTAX_BOOLEAN_LITERAL) || ($kind === \parse\SYNTAX_STRING_LITERAL)) { /* Concrete typing is a later pass. */ }
            elseif ($kind === \parse\SYNTAX_CONSTRUCT_EXPRESSION) {
                $constructed = $this->annotation($owner,(int)$node->first_child); $this->terms->default_construction($constructed,$owner,$id); $value = new Expression_Type($constructed,false);
            } elseif ($kind === \parse\SYNTAX_FIELD_EXPRESSION) {
                $receiver = $values[$children[0]]; $receiver_type = $receiver->type;
                if ($receiver_type !== null) {
                    $name_id = (int)$tree->row($children[0])->next_sibling; $name = Terms::text($owner,$name_id);
                    if (string_byte_slice($name,0,1) === '$') { $name = string_byte_slice($name,1,string_byte_len($name)-1); }
                    $value = new Expression_Type($this->terms->field($receiver_type,$name,$owner,$id),$receiver->readonly);
                }
            } elseif ($kind === \parse\SYNTAX_INDEX_EXPRESSION) {
                $receiver = $values[$children[0]]; $this->non_generic($values[$children[1]],$children[1],'index conversion'); $receiver_type = $receiver->type;
                $array = false; if ($receiver_type !== null) { $array = $receiver_type->kind === \check_templates\TERM_ARRAY; }
                if ($array) { $value = new Expression_Type($receiver_type->argument_at(0),$receiver->readonly); }
                else { $this->non_generic($receiver,$id,'index access'); }
            } elseif ($kind === \parse\SYNTAX_CALL_EXPRESSION) {
                $arguments /** vector<Expression_Type> */ = []; foreach ($children as $child_id) { $arguments[] = $values[$child_id]; }
                $value = $this->call($id,$arguments);
            } else {
                if (\parse\Binary_Syntax::operation($kind) === '') { $this->terms->fail($owner,$id,'Unsupported expression in generic definition: ' . ('syntax tag ' . $kind)); }
                foreach ($children as $child_id) { $this->non_generic($values[$child_id],$child_id,'operator ' . ('syntax tag ' . $kind)); }
            }
            $values[$id] = $value;
        }
        return $values[$root];
    }
    private function call(int $id, array $values /** vector<Expression_Type> */): Expression_Type {
        $owner = $this->task->owner; $tree = $owner->source_frontend()->tree;
        $target = \parse\Syntax_Access::call_target($tree,$id); $node = $tree->row($target);
        $arguments /** vector<Type_Term> */ = []; $receiver = new Expression_Type(null,false); $offset = 0; $callee_id = 0;
        if ((int)$node->kind === \parse\SYNTAX_FIELD_EXPRESSION) {
            $receiver = $values[0]; $receiver_type = $receiver->type;
            if ($receiver_type === null) { $this->terms->fail($owner,$id,'Unsupported symbolic method receiver'); }
            $name = (int)$tree->row((int)$node->first_child)->next_sibling;
            $method = $this->terms->method($receiver_type,Terms::text($owner,$name),$owner,$id); $callee_id = $method->symbol_id;
            if ($receiver->readonly && !$method->receiver_const()) { $this->terms->fail($owner,$id,'Mutable method requires a non-const receiver'); }
            for ($i = 0; $i < $receiver_type->argument_count(); $i++) { $arguments[] = $receiver_type->argument_at($i); }
            $offset = 1;
        } else {
            $name = $target;
            if ((int)$node->kind === \parse\SYNTAX_TEMPLATE_APPLICATION) {
                $application = \parse\Syntax_Access::template_application_parts($tree,$target); $name = (int)$application->name_id;
                foreach ($this->terms->arguments($owner,(int)$application->first_argument_id) as $argument) {
                    $term = $this->annotation($owner,$argument); $this->terms->forwarded_type($term,$owner,$argument); $arguments[] = $term;
                }
            }
            $callee_id = $this->task->bindings->target_for($name);
        }
        $callee = $this->terms->declaration($callee_id);
        $actual /** vector<Expression_Type> */ = []; for ($i = $offset; $i < q_count($values); $i++) { $actual[] = $values[$i]; }
        if (!$callee->is_source()) {
            $provider = $callee->provider();
            if ($provider->kind() === \collect_symbols\PROVIDER_METHOD) { return $this->provider_call($provider->method(),$receiver,$actual,$id); }
            foreach ($arguments as $argument) { $this->non_generic(new Expression_Type($argument,false),$id,'provider storage family requirements'); }
            foreach ($actual as $argument_value) { $this->non_generic($argument_value,$id,'forwarding to a concrete provider parameter'); }
            return new Expression_Type(null,false);
        }
        $this->terms->bindings($callee); $callee_tree = $callee->source_frontend()->tree;
        $parts = \parse\Syntax_Access::function_parts($callee_tree,\parse\Syntax_Access::underlying_declaration($callee_tree,(int)$callee->source_fact()->declaration_node_id));
        $parameter_ids = $this->terms->arguments($callee,(int)$callee_tree->row((int)$parts->parameters_id)->first_child);
        if (q_count($parameter_ids) !== q_count($actual)) { $this->terms->fail($owner,$id,'Generic call argument count mismatch'); }
        foreach ($parameter_ids as $index => $parameter) {
            $part = \parse\Syntax_Access::parameter_parts($callee_tree,$parameter); $type = $this->terms->annotation($callee,(int)$part->type_syntax_id,$arguments);
            if ((int)$part->reference === 0) { $this->terms->provider_value_use($type,$owner,$id); }
            $this->compatible(new Expression_Type($type,false),$actual[$index],$id);
            if (((int)$part->reference === \parse\SYNTAX_REFERENCE_ANNOTATION) && $actual[$index]->readonly) { $this->terms->fail($owner,$id,'Cannot forward const reference to mutable parameter'); }
        }
        return new Expression_Type($this->terms->annotation($callee,(int)$parts->return_type_id,$arguments),false);
    }
    private function provider_call(\type_model\Family_Method $method, Expression_Type $receiver,
        array $values /** vector<Expression_Type> */, int $node): Expression_Type {
        $operation = $method->operation; $signature = $operation->signature; $receiver_type = $receiver->type;
        if ($receiver_type === null) { throw new \LogicException('Provider call requires symbolic receiver'); }
        if (q_count($values) !== ($signature->parameter_count()-1)) { $this->terms->fail($this->task->owner,$node,'Generic call argument count mismatch'); }
        $actual = 0;
        for ($index = 0; $index < $signature->parameter_count(); $index++) {
            $parameter = $signature->parameter_at($index); $value = $receiver;
            if ($index !== $operation->receiver) { $value = $values[$actual]; $actual++; }
            $expected = $this->terms->provider_type($parameter->type,$method->family,$receiver_type);
            if ($parameter->passing === \type_model\PASS_VALUE) { $this->terms->provider_value_use($expected,$this->task->owner,$node); }
            $this->compatible(new Expression_Type($expected,false),$value,$node);
            if ($parameter->passing === \type_model\PASS_BORROW_MUTABLE) {
                $value_type = $value->type;
                if ($value_type !== null) { if ($value_type->kind === \check_templates\TERM_PARAMETER) { $this->terms->fail($this->task->owner,$node,'Mutable borrowing of bare generic T is not in the initial contract'); } }
                if ($value->readonly) { $this->terms->fail($this->task->owner,$node,'Cannot forward const reference to mutable parameter'); }
            }
        }
        for ($i = 0; $i < $operation->requirement_count(); $i++) {
            $requirement = $operation->requirement_at($i); $this->terms->family_argument($receiver_type->argument_at($requirement->slot),$this->task->owner,$node);
        }
        return new Expression_Type($this->terms->provider_type($signature->result->type,$method->family,$receiver_type),false);
    }
    private function value_use(Expression_Type $value, int $node): void {
        $type = $value->type; if ($type !== null) { $this->terms->provider_value_use($type,$this->task->owner,$node); }
    }
    private function compatible(Expression_Type $target_value, Expression_Type $source_value, int $node): void {
        $target = $target_value->type; $source = $source_value->type; $dependent = false;
        if ($target !== null) { $dependent = $target->dependent; }
        if ($source !== null) { if ($source->dependent) { $dependent = true; } }
        if (!$dependent) { return; }
        $same = false;
        if ($target !== null) { if ($source !== null) { $same = Type_Term::same($target,$source); } }
        if (!$same) { $this->terms->fail($this->task->owner,$node,'Generic contract requires the same declared type; concrete substitution cannot authorize this conversion'); }
    }
    private function non_generic(Expression_Type $value, int $node, string $operation): void {
        $type = $value->type;
        if ($type !== null) { if ($type->dependent) { $this->terms->fail($this->task->owner,$node,'Generic contract does not permit ' . $operation); } }
    }
}
