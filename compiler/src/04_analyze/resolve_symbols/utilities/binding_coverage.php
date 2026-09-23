<?php
declare(strict_types=1);
namespace resolve_symbols;
/** Private positive-value index with consumed slots; avoids unset and mixed payloads. */
final class Coverage_Marks {
    private array $rows /** hash<int,int> */ = [];
    private int $remaining = 0;
    public function add(int $node, int $value): bool {
        if (isset($this->rows[$node])) { return false; }
        $this->rows[$node] = $value; $this->remaining++; return true;
    }
    public function peek(int $node): int { if (!isset($this->rows[$node])) { return 0; } return $this->rows[$node]; }
    public function consume(int $node): int {
        $value = $this->peek($node); if ($value === 0) { return 0; }
        $this->rows[$node] = 0; $this->remaining = $this->remaining - 1; return $value;
    }
    public function complete(): bool { return $this->remaining === 0; }
}
final class Coverage_Cursor {
    public function __construct(public readonly int $node, public readonly bool $siblings,
        public readonly int $scope, public readonly int $access, public readonly int $initializing_local,
        public readonly int $initializing_constant, public readonly bool $publish_parameter) {}
}
/** Structural acceptance for new results only; never invokes a worker or global lookup. */
final class Binding_Coverage {
    private array $pending /** vector<Coverage_Cursor> */ = [];
    private int $used = 0;
    private array $scope_names /** vector<Scope_Names> */ = [];
    private array $template_names /** hash<bool> */ = [];
    public function __construct(private readonly Symbol_Resolution $result,
        private readonly Coverage_Marks $calls, private readonly Coverage_Marks $names,
        private readonly Coverage_Marks $applications, private readonly Coverage_Marks $members,
        private readonly Coverage_Marks $declarations, private readonly Coverage_Marks $scopes,
        private readonly Coverage_Marks $uses) {}
    public static function complete(Symbol_Resolution $result): bool {
        $walk = new Binding_Coverage($result,new Coverage_Marks(),new Coverage_Marks(),new Coverage_Marks(),
            new Coverage_Marks(),new Coverage_Marks(),new Coverage_Marks(),new Coverage_Marks());
        return $walk->inspect();
    }
    private function prepare(): bool {
        for ($i = 0; $i < $this->result->calls_count(); $i++) { if (!$this->calls->add((int)$this->result->calls_at($i)->use_node_id,$i+1)) { return false; } }
        for ($i = 0; $i < $this->result->names_count(); $i++) { if (!$this->names->add($this->result->names_at($i)->use_node_id,$i+1)) { return false; } }
        for ($i = 0; $i < $this->result->applications_count(); $i++) { if (!$this->applications->add($this->result->applications_at($i)->use_node_id,$i+1)) { return false; } }
        for ($i = 0; $i < $this->result->members_count(); $i++) { if (!$this->members->add((int)$this->result->members_at($i)->use_node_id,$i+1)) { return false; } }
        for ($i = 0; $i < $this->result->locals_count(); $i++) {
            $row = $this->result->locals_at($i); if (!$this->declarations->add((int)$row->declaration_node_id,(int)$row->scope_id)) { return false; }
        }
        for ($i = 0; $i < $this->result->constants_count(); $i++) {
            $row = $this->result->constants_at($i); if (!$this->declarations->add((int)$row->declaration_node_id,(int)$row->scope_id)) { return false; }
        }
        for ($i = 0; $i < $this->result->scopes_count(); $i++) {
            if (!$this->scopes->add((int)$this->result->scopes_at($i)->block_node_id,$i+1)) { return false; }
            $this->scope_names[] = new Scope_Names();
        }
        for ($i = 0; $i < $this->result->uses_count(); $i++) { if (!$this->uses->add((int)$this->result->uses_at($i)->use_node_id,$i+1)) { return false; } }
        return true;
    }
    private function push(int $node, bool $siblings, int $scope, int $access, int $local, int $constant): void {
        if ($node === 0) { return; }
        $this->queue(new Coverage_Cursor($node,$siblings,$scope,$access,$local,$constant,false));
    }
    private function queue(Coverage_Cursor $cursor): void {
        if ($this->used === q_count($this->pending)) { $this->pending[] = $cursor; } else { $this->pending[$this->used] = $cursor; }
        $this->used++;
    }
    private function inspect(): bool {
        if (!$this->prepare()) { return false; }
        $owner = $this->result->owner;
        $root = (int)$owner->source_fact()->declaration_node_id;
        if ($root === 0) { $root = (int)$owner->source_fact()->body_node_id; }
        $this->push($root,false,0,\resolve_symbols\LOCAL_READ,0,0);
        if ($owner->owner_symbol_id !== 0) {
            if ($this->result->locals_count() === 0) { return false; }
            if (!$this->result->locals_at(0)->receiver) { return false; }
            if ($this->declarations->consume((int)$owner->source_fact()->declaration_node_id) !== 1) { return false; }
            $this->scope_names[0]->add_local('$this',1);
            $this->push((int)$owner->source_fact()->template_parameters_node_id,false,0,\resolve_symbols\LOCAL_READ,0,0);
        }
        while ($this->used > 0) {
            $this->used = $this->used - 1; $cursor = $this->pending[$this->used];
            if ($cursor->publish_parameter) {
                $parts = \parse\Syntax_Access::template_parameter_parts($this->result->owner->source_frontend()->tree,$cursor->node);
                $name = $this->text((int)$parts->name_id); $this->template_names[$name] = true;
            } elseif (!$this->visit($cursor)) { return false; }
        }
        return $this->calls->complete() && $this->names->complete() && $this->applications->complete()
            && $this->members->complete() && $this->declarations->complete() && $this->scopes->complete() && $this->uses->complete();
    }
    private function text(int $node): string { return \collect_symbols\File_Collector::name_text($this->result->owner->source_frontend(),$node); }
    private function visible(string $name, int $scope, bool $constant): int {
        $id = $scope;
        while ($id !== 0) {
            $value = 0;
            if ($constant) { $value = $this->scope_names[$id-1]->constant($name); }
            else { $value = $this->scope_names[$id-1]->local($name); }
            if ($value !== 0) { return $value; }
            $id = (int)$this->result->scope_for($id)->parent_scope_id;
        }
        return 0;
    }
    private function free_name(Coverage_Cursor $cursor): bool {
        $id = $cursor->node; $name = $this->text($id); $constant = $this->visible($name,$cursor->scope,true);
        $call = $this->calls->consume($id);
        if ($call !== 0) { return ($constant === 0) && !isset($this->template_names[$name]); }
        $position = $this->names->consume($id); if ($position === 0) { return false; }
        $binding = $this->result->names_at($position-1);
        if (isset($this->template_names[$name])) { return $binding->kind === \resolve_symbols\REFERENCE_TEMPLATE_PARAMETER; }
        if ($binding->kind === \resolve_symbols\REFERENCE_TEMPLATE_PARAMETER) { return false; }
        if ($constant === 0) { return $binding->kind !== \resolve_symbols\REFERENCE_LOCAL_CONSTANT; }
        return ($binding->kind === \resolve_symbols\REFERENCE_LOCAL_CONSTANT) && ($binding->target_id === $constant)
            && ($constant !== $cursor->initializing_constant);
    }
    private function variable(Coverage_Cursor $cursor): bool {
        $position = $this->uses->consume($cursor->node); if ($position === 0) { return false; }
        $binding = $this->result->uses_at($position-1); $visible = $this->visible($this->text($cursor->node),$cursor->scope,false);
        return ($visible !== 0) && ($visible !== $cursor->initializing_local)
            && ((int)$binding->local_id === $visible) && ((int)$binding->access === $cursor->access);
    }
    private function visit(Coverage_Cursor $cursor): bool {
        $id = $cursor->node; $scope = $cursor->scope; $tree = $this->result->owner->source_frontend()->tree; $node = $tree->row($id); $kind = (int)$node->kind;
        if ($cursor->siblings) { $this->push((int)$node->next_sibling,true,$scope,$cursor->access,$cursor->initializing_local,$cursor->initializing_constant); }
        if ($kind === \parse\SYNTAX_NAME) { return $this->free_name($cursor); }
        if ($kind === \parse\SYNTAX_VARIABLE_NAME) { return $this->variable($cursor); }
        if ($kind === \parse\SYNTAX_TEMPLATE_APPLICATION) {
            $position = $this->applications->consume($id); if ($position === 0) { return false; }
            $name = (int)$node->first_child; $call = $this->calls->peek($name); $target = 0;
            if ($call !== 0) { $target = (int)$this->result->calls_at($call-1)->target_symbol_id; }
            else { $named = $this->names->peek($name); if ($named !== 0) { $target = $this->result->names_at($named-1)->target_id; } }
            if ($target !== $this->result->applications_at($position-1)->definition->symbol_id) { return false; }
        }
        if ($kind === \parse\SYNTAX_CALL_EXPRESSION) {
            $target = (int)$node->first_child;
            if ((int)$tree->row($target)->kind === \parse\SYNTAX_FIELD_EXPRESSION) { if ($this->members->consume($target) === 0) { return false; } }
        }
        if ($kind === \parse\SYNTAX_BLOCK) {
            $nested = $this->scopes->consume($id); if ($nested === 0) { return false; }
            if ((int)$this->result->scope_for($nested)->parent_scope_id !== $scope) { return false; }
            $scope = $nested;
        }
        if (($kind === \parse\SYNTAX_LOCAL_DECLARATION) || ($kind === \parse\SYNTAX_PARAMETER_DECLARATION)) {
            $target_scope = $scope; if ($kind === \parse\SYNTAX_PARAMETER_DECLARATION) { $target_scope = 1; }
            if ($this->declarations->consume($id) !== $target_scope) { return false; }
            $local = $this->result->local_for_declaration($id); $variable = 0; $type = 0; $initializer = 0;
            if ($kind === \parse\SYNTAX_PARAMETER_DECLARATION) {
                $parameter = \parse\Syntax_Access::parameter_parts($tree,$id); $variable = (int)$parameter->variable_id; $type = (int)$parameter->type_syntax_id;
            } else {
                $parts = \parse\Syntax_Access::local_declaration_parts($tree,$id); $variable = (int)$parts->variable_id; $type = (int)$parts->type_syntax_id; $initializer = (int)$parts->initializer_id;
            }
            $name = $this->text($variable);
            if ($this->scope_names[$target_scope-1]->local($name) !== 0) { return false; }
            $this->scope_names[$target_scope-1]->add_local($name,$local);
            $this->push($initializer,false,$target_scope,\resolve_symbols\LOCAL_READ,$local,0);
            $this->push($type,false,$target_scope,\resolve_symbols\LOCAL_READ,0,0); return true;
        }
        if ($kind === \parse\SYNTAX_CONSTANT_DECLARATION) {
            $parts = \parse\Syntax_Access::constant_parts($tree,$id); $initializing = 0;
            if ($id !== (int)$this->result->owner->source_fact()->declaration_node_id) {
                if ($this->declarations->consume($id) !== $scope) { return false; }
                $name = $this->text((int)$parts->name_id); if ($this->scope_names[$scope-1]->constant($name) !== 0) { return false; }
                $this->scope_names[$scope-1]->add_constant($name,$id); $initializing = $id;
            }
            $this->push((int)$parts->initializer_id,false,$scope,\resolve_symbols\LOCAL_READ,0,$initializing);
            $this->push((int)$parts->type_syntax_id,false,$scope,\resolve_symbols\LOCAL_READ,0,$initializing); return true;
        }
        if ($kind === \parse\SYNTAX_ASSIGNMENT_STATEMENT) {
            $parts = \parse\Syntax_Access::assignment_parts($tree,$id);
            $this->push((int)$parts->value_id,false,$scope,\resolve_symbols\LOCAL_READ,0,0);
            $this->push((int)$parts->target_id,false,$scope,\resolve_symbols\LOCAL_WRITE,0,0); return true;
        }
        if ($kind === \parse\SYNTAX_INDEX_EXPRESSION) {
            $base = (int)$node->first_child; $index = (int)$tree->row($base)->next_sibling;
            $this->push($index,false,$scope,\resolve_symbols\LOCAL_READ,$cursor->initializing_local,$cursor->initializing_constant);
            $this->push($base,false,$scope,$cursor->access,$cursor->initializing_local,$cursor->initializing_constant); return true;
        }
        $child = (int)$node->first_child; $follow = true;
        if ($kind === \parse\SYNTAX_METHOD_DECLARATION) {
            if ($id !== (int)$this->result->owner->source_fact()->declaration_node_id) { $child = 0; }
            $follow = false;
        } elseif ($kind === \parse\SYNTAX_TYPE_PARAMETER_DECLARATION) {
            $name = $this->text($child); $this->template_names[$name] = true; $child = 0;
        } elseif ($kind === \parse\SYNTAX_VALUE_PARAMETER_DECLARATION) {
            // Its annotation sees earlier parameters; publish this name only afterward.
            $this->queue(new Coverage_Cursor($id,false,$scope,\resolve_symbols\LOCAL_READ,0,0,true)); $follow = false;
        }
        elseif ($kind === \parse\SYNTAX_FIELD_DECLARATION) {
            $parts = \parse\Syntax_Access::field_declaration_parts($tree,$id);
            $this->push((int)$parts->extent_id,false,$scope,\resolve_symbols\LOCAL_READ,0,0); $follow = false;
        } elseif ($kind === \parse\SYNTAX_FIELD_EXPRESSION) { $follow = false; }
        elseif (($kind === \parse\SYNTAX_FUNCTION_DECLARATION) || ($kind === \parse\SYNTAX_STRUCT_DECLARATION)) { $child = (int)$tree->row($child)->next_sibling; }
        $this->push($child,$follow,$scope,$cursor->access,$cursor->initializing_local,$cursor->initializing_constant); return true;
    }
}
