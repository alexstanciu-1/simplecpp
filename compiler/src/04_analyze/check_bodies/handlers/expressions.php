<?php
declare(strict_types=1);
namespace check_bodies;
trait Expression_Checking {
    private function check_expression(int $node_id, int $expected_type): int {
        $tree = $this->tree(); $pending = new Body_Expression_Stack(); $value_id = 0; $finished = false;
        while (!$finished) {
            if ($node_id !== 0) {
                $node = $tree->row($node_id); $kind = (int)$node->kind;
                if ($kind === \parse\SYNTAX_CALL_EXPRESSION) {
                    $call = $this->begin_call($node_id); $pending->push(new Body_Expression_Frame($call,null,null));
                    $node_id = $call->next;
                    $expected_type = $node_id === 0 ? 0 : $this->context->types->parameter_type_for($call->target,$call->argument_index + 1);
                    continue;
                }
                if (\parse\Binary_Syntax::operation($kind) !== '') {
                    $pending->push(new Body_Expression_Frame(null,new Operation_Cursor($node_id,(int)$node->first_child),null));
                    $node_id = (int)$node->first_child; $expected_type = 0; continue;
                }
                if (($kind === \parse\SYNTAX_VARIABLE_NAME) || ($kind === \parse\SYNTAX_FIELD_EXPRESSION) || ($kind === \parse\SYNTAX_INDEX_EXPRESSION)) {
                    $place = $this->begin_place($node_id,false); $node_id = $this->advance_place($place,-1);
                    if ($node_id !== 0) { $pending->push(new Body_Expression_Frame(null,null,$place)); $expected_type = 0; continue; }
                    $value_id = $this->append_place($place);
                } else { $value_id = $this->check_leaf($node_id,$expected_type); }
                $node_id = 0;
            }
            if ($pending->empty()) { $finished = true; continue; }
            $frame = $pending->top(); $place = $frame->place; $operation = $frame->operation; $call = $frame->call;
            if ($place !== null) {
                $node_id = $this->advance_place($place,$value_id); $expected_type = 0;
                if ($node_id === 0) { $value_id = $this->append_place($place); $pending->pop(); }
                continue;
            }
            if ($operation !== null) {
                $node_id = $this->resume_binary($operation,$value_id);
                if ($node_id === 0) { $value_id = $operation->result; $pending->pop(); }
                continue;
            }
            if ($call === null) { throw new \LogicException('Missing call continuation'); }
            if ($call->next !== 0) {
                $node_id = $this->check_argument($call,$value_id);
                $expected_type = $node_id === 0 ? 0 : $this->context->types->parameter_type_for($call->target,$call->argument_index + 1);
                continue;
            }
            $value_id = $this->finish_call($call); $pending->pop();
        }
        return $value_id;
    }
    private function begin_call(int $id): Call_Cursor {
        $tree = $this->tree(); $target_node = \parse\Syntax_Access::call_target($tree,$id);
        $kind = (int)$tree->row($target_node)->kind; $member = $kind === \parse\SYNTAX_FIELD_EXPRESSION; $target = 0;
        if ($member || ($kind === \parse\SYNTAX_TEMPLATE_APPLICATION)) {
            $application = $this->context->types->instances->application($this->context->input->context(),$target_node);
            if ($application === null) { throw new \LogicException('Missing prepared template call'); }
            $target = $application->context_id;
        } else { $target = $this->context->names->target_for($target_node); }
        $signature = $this->context->signature($target); $shape = $signature->representation;
        $argument = \parse\Syntax_Access::first_argument($tree,$id);
        if ($argument !== 0) { if ($signature->parameter_count() === ($member ? 1 : 0)) { $this->arity_error($argument); } }
        $start = $this->output->reserve_arguments($signature->parameter_count()); $receiver = -1;
        if ($member) {
            $contract = $this->context->types->for_callable($target);
            if ($contract === null) { throw new \LogicException('Missing method signature'); }
            $position = $contract->receiver_index;
            if ($position === null) { throw new \LogicException('Method signature lost its receiver position'); }
            $receiver = $position;
        }
        $cursor = new Call_Cursor($id,$target,$shape->signature_return(),$start,$signature->parameter_count(),$argument,$receiver);
        if ($member) {
            $receiver_node = (int)$tree->row($target_node)->first_child;
            $value = $this->append_place($this->check_place($receiver_node,false));
            $type = $signature->parameter_type($receiver); $passing = $shape->parameter_passing($receiver);
            $value = $this->argument_value($value,$type,$passing,$receiver_node);
            $this->output->complete_argument($start + $receiver,new Typed_Argument($value,$type,$passing));
            $cursor->argument_index = $receiver === 0 ? 1 : 0;
        }
        return $cursor;
    }
    private function check_leaf(int $id, int $expected): int {
        $kind = (int)$this->tree()->row($id)->kind; $result = 0;
        if ($kind === \parse\SYNTAX_CONSTRUCT_EXPRESSION) {
            $lookup = new \resolve_types\Construction_Types($this->context->types,$this->reader);
            $type = $lookup->resolve($this->context->input,(int)$this->tree()->row($id)->first_child);
            $this->context->retain_type($type); $result = $this->default_value($id,$type);
        } elseif ($kind === \parse\SYNTAX_INTEGER_LITERAL) {
            $literal = '';
            try { $literal = Integer_Literals::resolve($this->text($id),$this->context->types->definition_for($this->context->integer_literal_type)); }
            catch (\RangeException $error) { $this->fail($id,$error->getMessage()); }
            $result = $this->append_value(new Typed_Value($id,$this->context->integer_literal_type,\check_bodies\VALUE_INTEGER_LITERAL,$literal));
        } elseif ($kind === \parse\SYNTAX_BOOLEAN_LITERAL) {
            $type = $this->context->types->boolean_type();
            if ($type === 0) { $this->fail($id,'No boolean type contract is configured'); }
            $result = $this->append_value(new Typed_Value($id,$type,\check_bodies\VALUE_INTEGER_LITERAL,$this->text($id) === 'true' ? '1' : '0'));
        } elseif ($kind === \parse\SYNTAX_NAME) {
            $argument = $this->reader->value($this->context->input->context(),$id); $literal = $argument->value;
            if ($literal === null) { throw new \LogicException('Missing prepared integer value'); }
            $type = $this->context->types->types->find_type($argument->type->name,$argument->type->namespace_name);
            $result = $this->append_value(new Typed_Value($id,$type,\check_bodies\VALUE_INTEGER_LITERAL,$literal));
        } elseif ($kind === \parse\SYNTAX_STRING_LITERAL) { $result = $this->check_byte_literal($id,$expected); }
        else { $this->fail($id,'Unsupported expression for body checking'); }
        return $result;
    }
    private function default_value(int $node, int $type): int {
        $shape = $this->context->types->definition_for($type)->representation->kind();
        if (($shape !== \type_model\REPRESENTATION_STRUCTURE) && ($shape !== \type_model\REPRESENTATION_OPAQUE)) { $this->fail($node,'Default construction requires a supported inline type'); }
        $life = $this->life($type); $construction = (int)$life->construction;
        if ($construction === \type_model\CONSTRUCTION_UNAVAILABLE) { $this->fail($node,'Default construction is unavailable for a constituent field'); }
        return $this->append_value(new Typed_Value($node,$type,$construction === \type_model\CONSTRUCTION_ZERO ? \check_bodies\VALUE_RECORD_DEFAULT : \check_bodies\VALUE_DEFAULT_CONSTRUCT));
    }
    private function check_byte_literal(int $id, int $expected): int {
        $bytes = '';
        try { $bytes = Byte_Literals::decode($this->text($id)); }
        catch (\InvalidArgumentException $error) { $this->fail($id,$error->getMessage()); }
        if ($expected !== 0) {
            if ($this->context->types->definition_for($expected)->representation->kind() === \type_model\REPRESENTATION_BYTE_SPAN) {
                return $this->append_value(new Typed_Value($id,$expected,\check_bodies\VALUE_BYTE_LITERAL,'',0,null,null,null,new Byte_Literal($bytes)));
            }
        }
        $target = $this->context->types->language_callable(\type_model\BINDING_BYTE_LITERAL,$expected);
        if ($target === 0) { $this->fail($id,'No byte-literal construction contract for this context'); }
        $signature = $this->context->signature($target); $type = $signature->parameter_type(0);
        $value = $this->append_value(new Typed_Value($id,$type,\check_bodies\VALUE_BYTE_LITERAL,'',0,null,null,null,new Byte_Literal($bytes)));
        return $this->bound_call($id,$target,$signature,$value);
    }
    private function bound_call(int $node, int $target, Signature_Dependency $signature, int $value): int {
        $passing = $signature->representation->parameter_passing(0); $type = $signature->parameter_type(0);
        $value = $this->argument_value($value,$type,$passing,$node); $start = $this->output->reserve_arguments(1);
        $this->output->complete_argument($start,new Typed_Argument($value,$type,$passing));
        $cursor = new Call_Cursor($node,$target,$signature->representation->signature_return(),$start,1,0,-1);
        $cursor->argument_index = 1; return $this->finish_call($cursor);
    }
    private function resume_binary(Operation_Cursor $cursor, int $value): int {
        if ($value === 0) { $this->fail($cursor->node,'Binary operation requires value operands'); }
        $cursor->operands[] = $value; $cursor->next = (int)$this->tree()->row($cursor->next)->next_sibling;
        if ($cursor->next !== 0) { return $cursor->next; }
        if (q_count($cursor->operands) !== 2) { throw new \LogicException('Binary operation requires two parsed operands'); }
        $left = $cursor->operands[0]; $right = $cursor->operands[1];
        $left_type = $this->output->value_for($left)->type_id; $right_type = $this->output->value_for($right)->type_id;
        $operation = \parse\Binary_Syntax::operation((int)$this->tree()->row($cursor->node)->kind);
        $key = $operation . ':' . $left_type . ':' . $right_type;
        $contract = $this->binary_contract($cursor->node,$operation,$left_type,$right_type,$key);
        $this->output->select_place_access($left,false); $this->output->select_place_access($right,false);
        $cursor->result = $this->append_value(new Typed_Value($cursor->node,$contract->result_type,\check_bodies\VALUE_OPERATION,'',0,null,null,new Operation_Value($left,$right,$contract)));
        return 0;
    }
    private function binary_contract(int $node, string $operation, int $left, int $right, string $key): \type_model\Operation_Contract {
        if (isset($this->operation_contracts[$key])) { return $this->operation_contracts[$key]; }
        $contract = Operation_Resolver::binary($this->context->types->types,$operation,$left,$right,$this->context->types->boolean_type());
        if ($contract === null) { $this->fail($node,'Unsupported ' . $operation . ' operand types or result contract'); }
        $this->operation_contracts[$key] = $contract;
        return $contract;
    }
    private function check_argument(Call_Cursor $cursor, int $value): int {
        $signature = $this->context->signature($cursor->target); $type = $signature->parameter_type($cursor->argument_index);
        $passing = $signature->representation->parameter_passing($cursor->argument_index);
        $value = $this->argument_value($value,$type,$passing,$cursor->next);
        $this->output->complete_argument($cursor->start + $cursor->argument_index,new Typed_Argument($value,$type,$passing));
        $cursor->argument_index++; if ($cursor->argument_index === $cursor->receiver) { $cursor->argument_index++; }
        $cursor->next = (int)$this->tree()->row($cursor->next)->next_sibling;
        if ($cursor->next !== 0) { if ($cursor->argument_index === $cursor->count) { $this->arity_error($cursor->next); } }
        return $cursor->next;
    }
    private function argument_value(int $id, int $destination, int $passing, int $node): int {
        $id = $this->convert($id,$destination,'argument',$node); $shape = $this->context->types->definition_for($destination)->representation->kind();
        $object = ($shape === \type_model\REPRESENTATION_OPAQUE) || ($shape === \type_model\REPRESENTATION_STRUCTURE);
        $borrow = \type_model\Semantic_Modes::is_borrow($passing);
        if ($object && !$borrow) { $this->fail($node,'Unsupported inline object value argument; a call-scoped borrow is required'); }
        if ($borrow) {
            if (($shape === \type_model\REPRESENTATION_STRUCTURE) || ($passing === \type_model\PASS_BORROW_MUTABLE)) {
                $location = $this->output->value_for($id)->pending;
                if ($location === null) { $this->fail($node,'Record or mutable borrowing requires existing local storage; temporary record borrowing is unsupported'); }
                if ($passing === \type_model\PASS_BORROW_MUTABLE) { if (!$this->place_writable($location)) { $this->fail($node,'A const reference cannot be passed as a mutable reference'); } }
            }
        }
        $this->output->select_place_access($id,$borrow); return $id;
    }
    private function finish_call(Call_Cursor $cursor): int {
        if ($cursor->argument_index !== $cursor->count) { $this->arity_error($cursor->node); }
        $target = $cursor->target; $external = $this->context->signature($target)->external;
        if ($external !== null) {
            $purpose = $external->conversion_purpose;
            if ($purpose !== null) {
                $argument = $this->output->argument_at($cursor->start); $source = $this->output->value_for($argument->value_id)->type_id;
                $selection = Conversion_Resolver::resolve($this->context->types,new Conversion_Request($source,$cursor->return_type,$purpose));
                if ($selection === null) { $this->fail($cursor->node,'Named conversion does not select its declared operation'); }
                if (($selection->form !== \check_bodies\CONVERSION_PROVIDER_CALL) || ($selection->callable_id !== $target)) { $this->fail($cursor->node,'Named conversion does not select its declared operation'); }
                $target = $selection->callable_id;
            }
        }
        $has_result = $this->context->types->definition_for($cursor->return_type)->representation->kind() !== \type_model\REPRESENTATION_VOID;
        $call_id = $this->output->append_call(new Typed_Call($cursor->node,$target,$has_result ? $this->output->value_count() + 1 : 0,$cursor->start,$cursor->count));
        if (!$has_result) { return 0; }
        return $this->append_value(new Typed_Value($cursor->node,$cursor->return_type,\check_bodies\VALUE_CALL_RESULT,'',$call_id));
    }
    private function arity_error(int $node): void { $this->fail($node,'Call argument count does not match the resolved signature'); }
}
