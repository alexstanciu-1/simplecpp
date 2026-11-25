<?php
declare(strict_types=1);
namespace resolve_symbols;
trait Expression_Resolution {
    private function expression(int $id, int $scope, int $initializing, int $role, string $description): void {
        if ($id === 0) { return; }
        $pending = new Expression_Stack(); $pending->push(new Binding_Cursor($id,$role,false,$description));
        $tree = $this->owner->source_frontend()->tree;
        while (!$pending->empty()) {
            $cursor = $pending->pop(); $node = $tree->row($cursor->node_id);
            if ($cursor->siblings) {
                if ((int)$node->next_sibling !== 0) { $pending->push(new Binding_Cursor((int)$node->next_sibling,$cursor->role,true,$cursor->description)); }
            }
            $this->expression_node($cursor,$scope,$initializing,$pending);
        }
    }
    private function expression_node(Binding_Cursor $cursor, int $scope, int $initializing, Expression_Stack $pending): void {
        $id = $cursor->node_id; $tree = $this->owner->source_frontend()->tree; $node = $tree->row($id); $kind = (int)$node->kind;
        if ($kind === \parse\SYNTAX_TEMPLATE_APPLICATION) {
            if ($cursor->role !== \resolve_symbols\NAME_TYPE) { $this->fail($id,'A template type application is not a value expression'); }
            $parts = \parse\Syntax_Access::template_application_parts($tree,$id);
            $binding = $this->bind_name((int)$parts->name_id,\resolve_symbols\NAME_TYPE_FAMILY,$scope,$cursor->description);
            $this->application_arguments($id,$binding->target_id,$pending,$cursor->description); return;
        }
        if ($kind === \parse\SYNTAX_NAME) { $this->bind_name($id,$cursor->role,$scope,$cursor->description); return; }
        if ($cursor->role === \resolve_symbols\NAME_TYPE) { $this->fail($id,'Expected type argument, not a value expression'); }
        if (($kind === \parse\SYNTAX_FIELD_EXPRESSION) || ($kind === \parse\SYNTAX_INDEX_EXPRESSION)) {
            $root = \parse\Syntax_Access::place_root($tree,$id);
            if ($root === 0) { $this->fail($id,'Field access requires a local storage root'); }
            $this->bind_local($root,$scope,\resolve_symbols\LOCAL_READ,$initializing);
            // Collected leaf-to-root; LIFO executes root-to-leaf.
            foreach ($this->place_indices($id) as $index) { $pending->push(new Binding_Cursor($index,\resolve_symbols\NAME_VALUE,false,$cursor->description)); }
        } elseif ($kind === \parse\SYNTAX_VARIABLE_NAME) { $this->bind_local($id,$scope,\resolve_symbols\LOCAL_READ,$initializing); }
        elseif ($kind === \parse\SYNTAX_CALL_EXPRESSION) { $this->call_expression($id,$scope,$pending,$cursor->description); }
        elseif (\parse\Binary_Syntax::operation($kind) !== '') { $pending->push(new Binding_Cursor((int)$node->first_child,\resolve_symbols\NAME_VALUE,true,$cursor->description)); }
        elseif ($kind === \parse\SYNTAX_CONSTRUCT_EXPRESSION) { $pending->push(new Binding_Cursor((int)$node->first_child,\resolve_symbols\NAME_TYPE,false,'construction')); }
        elseif (($kind !== \parse\SYNTAX_INTEGER_LITERAL) && ($kind !== \parse\SYNTAX_STRING_LITERAL) && ($kind !== \parse\SYNTAX_BOOLEAN_LITERAL)) { $this->fail($id,'Unsupported expression for name resolution: ' . $kind); }
    }
    private function call_expression(int $id, int $scope, Expression_Stack $pending, string $description): void {
        $tree = $this->owner->source_frontend()->tree; $target_id = \parse\Syntax_Access::call_target($tree,$id); $node = $tree->row($target_id);
        if ((int)$node->kind === \parse\SYNTAX_FIELD_EXPRESSION) {
            $receiver = (int)$node->first_child;
            if ((int)$tree->row($receiver)->kind !== \parse\SYNTAX_VARIABLE_NAME) { $this->fail($receiver,'Method calls currently require a local receiver'); }
            $row = new Member_Call_Binding(); $row->use_node_id = $target_id; $row->receiver_node_id = $receiver; $this->members[] = $row;
            $argument = \parse\Syntax_Access::first_argument($tree,$id);
            if ($argument !== 0) { $pending->push(new Binding_Cursor($argument,\resolve_symbols\NAME_VALUE,true,$description)); }
            $pending->push(new Binding_Cursor($receiver,\resolve_symbols\NAME_VALUE,false,$description)); return;
        }
        $application = (int)$node->kind === \parse\SYNTAX_TEMPLATE_APPLICATION;
        $name_id = $target_id;
        if ($application) { $name_id = (int)\parse\Syntax_Access::template_application_parts($tree,$target_id)->name_id; }
        $name = $this->text($name_id);
        if (isset($this->parameter_names[$name])) { $this->fail($name_id,'Calling a template parameter is unsupported'); }
        if (($this->find_constant($name,$scope) !== 0) || ($this->symbols->find_symbol($name,\collect_symbols\SYMBOL_CONSTANT,0,'') !== 0)) { $this->fail($name_id,'Calling a constant is not implemented'); }
        $target = Function_Lookup::find($this->owner,$name_id,$this->symbols);
        if ($target === 0) { $this->fail($name_id,"Unknown function '" . $name . "'"); }
        $row = new Symbol_Binding(); $row->use_node_id = $name_id; $row->target_symbol_id = $target; $this->calls[] = $row;
        $argument = \parse\Syntax_Access::first_argument($tree,$id);
        if ($argument !== 0) { $pending->push(new Binding_Cursor($argument,\resolve_symbols\NAME_VALUE,true,$description)); }
        if ($application) {
            if ($this->symbols->symbol_by_id($target)->kind() !== \collect_symbols\SYMBOL_TEMPLATE_FUNCTION) { $this->fail($target_id,'Template arguments require a template function'); }
            $this->application_arguments($target_id,$target,$pending,$description);
        } elseif ($this->symbols->symbol_by_id($target)->is_template()) { $this->fail($name_id,'Template argument deduction is not implemented; provide explicit arguments'); }
    }
    private function application_arguments(int $id, int $target, Expression_Stack $pending, string $description): void {
        $tree = $this->owner->source_frontend()->tree; $argument = (int)\parse\Syntax_Access::template_application_parts($tree,$id)->first_argument_id;
        $definition = $this->symbols->symbol_by_id($target); $this->applications[] = new Template_Application_Binding($id,$definition);
        if (!$definition->is_source()) {
            $external = $definition->provider(); $arity = 1;
            if ($external->kind() === \collect_symbols\PROVIDER_FAMILY) { $arity = $external->family()->definition->parameter_count(); }
            $provider_arguments /** vector<Binding_Cursor> */ = [];
            for ($slot = 0; $slot < $arity; $slot++) {
                if ($argument === 0) { $this->fail($id,'Provider family type argument count mismatch'); }
                $provider_arguments[] = new Binding_Cursor($argument,\resolve_symbols\NAME_TYPE,false,$description);
                $argument = (int)$tree->row($argument)->next_sibling;
            }
            if ($argument !== 0) { $this->fail($id,'Provider family type argument count mismatch'); }
            $position_provider = q_count($provider_arguments);
            while ($position_provider > 0) { $position_provider = $position_provider - 1; $pending->push($provider_arguments[$position_provider]); }
            return;
        }
        $source = $definition->source_frontend()->tree; $list = (int)$definition->source_fact()->template_parameters_node_id;
        $arguments /** vector<Binding_Cursor> */ = []; $parameter = (int)$source->row($list)->first_child;
        while ($parameter !== 0) {
            if ($argument === 0) { $this->fail($id,'Template argument count mismatch'); }
            $parts = \parse\Syntax_Access::template_parameter_parts($source,$parameter);
            $role = (int)$parts->type_syntax_id === 0 ? \resolve_symbols\NAME_TYPE : \resolve_symbols\NAME_VALUE;
            $arguments[] = new Binding_Cursor($argument,$role,false,$description);
            $argument = (int)$tree->row($argument)->next_sibling; $parameter = (int)$source->row($parameter)->next_sibling;
        }
        if ($argument !== 0) { $this->fail($id,'Template argument count mismatch'); }
        $position = q_count($arguments);
        while ($position > 0) { $position = $position - 1; $pending->push($arguments[$position]); }
    }
    /** Leaf-to-root location operands; callers choose forward or stack scheduling. */
    private function place_indices(int $id): array /** vector<int> */ {
        $tree = $this->owner->source_frontend()->tree; $out /** vector<int> */ = [];
        while (true) {
            $node = $tree->row($id); $kind = (int)$node->kind;
            if (($kind !== \parse\SYNTAX_FIELD_EXPRESSION) && ($kind !== \parse\SYNTAX_INDEX_EXPRESSION)) { break; }
            if ($kind === \parse\SYNTAX_INDEX_EXPRESSION) { $out[] = (int)$tree->row((int)$node->first_child)->next_sibling; }
            $id = (int)$node->first_child;
        }
        return $out;
    }
}
