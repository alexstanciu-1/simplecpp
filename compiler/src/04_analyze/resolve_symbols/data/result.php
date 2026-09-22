<?php
declare(strict_types=1);
namespace resolve_symbols;

/** Published facts for one exact source owner. Value rows are copied on input and output. */
final class Symbol_Resolution {
    private array $call_index /** hash<int,int> */ = [];
    private array $scope_index /** hash<int,int> */ = [];
    private array $local_index /** hash<int,int> */ = [];
    private array $use_index /** hash<int,int> */ = [];
    private array $name_index /** hash<int,int> */ = [];
    private int $parameter_count = 0;
    private array $calls /** vector<Symbol_Binding> */ = [];
    private array $scopes /** vector<Lexical_Scope> */ = [];
    private array $locals /** vector<Local_Record> */ = [];
    private array $uses /** vector<Local_Binding> */ = [];
    private array $parameters /** vector<Template_Parameter> */ = [];
    private array $constants /** vector<Scoped_Constant> */ = [];
    private array $members /** vector<Member_Call_Binding> */ = [];
    private array $names /** vector<Name_Binding> */ = [];
    private array $applications /** vector<Template_Application_Binding> */ = [];
    public function __construct(public readonly \collect_symbols\Symbol_Record $owner,
        array $calls /** vector<Symbol_Binding> */,
        array $scopes /** vector<Lexical_Scope> */,
        array $locals /** vector<Local_Record> */,
        array $uses /** vector<Local_Binding> */,
        array $parameters /** vector<Template_Parameter> */,
        array $constants /** vector<Scoped_Constant> */,
        array $members /** vector<Member_Call_Binding> */,
        array $names /** vector<Name_Binding> */,
        array $applications /** vector<Template_Application_Binding> */) {
        foreach ($calls as $row) { $this->calls[] = Binding_Rows::symbol_binding($row); }
        foreach ($scopes as $row) { $this->scopes[] = Binding_Rows::lexical_scope($row); }
        foreach ($locals as $row) { $this->locals[] = Binding_Rows::local_record($row); }
        foreach ($uses as $row) { $this->uses[] = Binding_Rows::local_binding($row); }
        foreach ($parameters as $row) { $this->parameters[] = Binding_Rows::template_parameter($row); }
        foreach ($constants as $row) { $this->constants[] = Binding_Rows::scoped_constant($row); }
        foreach ($members as $row) { $this->members[] = Binding_Rows::member_call_binding($row); }
        foreach ($names as $row) { $this->names[] = $row; }
        foreach ($applications as $row) { $this->applications[] = $row; }
        $this->validate();
    }
    public function calls_count(): int { return q_count($this->calls); }
    public function calls_at(int $index): Symbol_Binding {
        if (($index < 0) || ($index >= q_count($this->calls))) { throw new \InvalidArgumentException("Invalid calls position"); }
        return Binding_Rows::symbol_binding($this->calls[$index]);
    }
    public function scopes_count(): int { return q_count($this->scopes); }
    public function scopes_at(int $index): Lexical_Scope {
        if (($index < 0) || ($index >= q_count($this->scopes))) { throw new \InvalidArgumentException("Invalid scopes position"); }
        return Binding_Rows::lexical_scope($this->scopes[$index]);
    }
    public function locals_count(): int { return q_count($this->locals); }
    public function locals_at(int $index): Local_Record {
        if (($index < 0) || ($index >= q_count($this->locals))) { throw new \InvalidArgumentException("Invalid locals position"); }
        return Binding_Rows::local_record($this->locals[$index]);
    }
    public function uses_count(): int { return q_count($this->uses); }
    public function uses_at(int $index): Local_Binding {
        if (($index < 0) || ($index >= q_count($this->uses))) { throw new \InvalidArgumentException("Invalid uses position"); }
        return Binding_Rows::local_binding($this->uses[$index]);
    }
    public function parameters_count(): int { return q_count($this->parameters); }
    public function parameters_at(int $index): Template_Parameter {
        if (($index < 0) || ($index >= q_count($this->parameters))) { throw new \InvalidArgumentException("Invalid parameters position"); }
        return Binding_Rows::template_parameter($this->parameters[$index]);
    }
    public function constants_count(): int { return q_count($this->constants); }
    public function constants_at(int $index): Scoped_Constant {
        if (($index < 0) || ($index >= q_count($this->constants))) { throw new \InvalidArgumentException("Invalid constants position"); }
        return Binding_Rows::scoped_constant($this->constants[$index]);
    }
    public function members_count(): int { return q_count($this->members); }
    public function members_at(int $index): Member_Call_Binding {
        if (($index < 0) || ($index >= q_count($this->members))) { throw new \InvalidArgumentException("Invalid members position"); }
        return Binding_Rows::member_call_binding($this->members[$index]);
    }
    public function names_count(): int { return q_count($this->names); }
    public function names_at(int $index): Name_Binding {
        if (($index < 0) || ($index >= q_count($this->names))) { throw new \InvalidArgumentException("Invalid names position"); }
        return $this->names[$index];
    }
    public function applications_count(): int { return q_count($this->applications); }
    public function applications_at(int $index): Template_Application_Binding {
        if (($index < 0) || ($index >= q_count($this->applications))) { throw new \InvalidArgumentException("Invalid applications position"); }
        return $this->applications[$index];
    }
    public function runtime_parameter_count(): int { return $this->parameter_count; }
    public function parameter_for(int $position): Local_Record {
        if (($position < 1) || ($position > $this->parameter_count)) { throw new \InvalidArgumentException('Invalid runtime parameter position'); }
        return $this->locals_at($position - 1);
    }
    public function target_for(int $node): int {
        if (!isset($this->call_index[$node])) { throw new \InvalidArgumentException('Missing call binding'); }
        return (int)$this->calls[$this->call_index[$node]]->target_symbol_id;
    }
    public function local_for_declaration(int $node): int {
        if (!isset($this->local_index[$node])) { throw new \InvalidArgumentException('Missing local declaration'); }
        return $this->local_index[$node] + 1;
    }
    public function local_for(int $id): Local_Record { return $this->locals_at($id - 1); }
    public function scope_for(int $id): Lexical_Scope { return $this->scopes_at($id - 1); }
    public function scope_for_block(int $node): int {
        if (!isset($this->scope_index[$node])) { throw new \InvalidArgumentException('Missing block scope'); }
        return $this->scope_index[$node] + 1;
    }
    public function binding_for(int $node): Local_Binding {
        if (!isset($this->use_index[$node])) { throw new \InvalidArgumentException('Missing local binding'); }
        return $this->uses_at($this->use_index[$node]);
    }
    public function name_for(int $node): Name_Binding {
        if (!isset($this->name_index[$node])) { throw new \InvalidArgumentException('Missing declaration binding'); }
        return $this->names[$this->name_index[$node]];
    }
    private function require_scope(int $id): void {
        if (($id < 1) || ($id > q_count($this->scopes))) { throw new \InvalidArgumentException('Invalid lexical scope'); }
    }
    private function validate(): void {
        $tree = $this->owner->frontend->tree;
        foreach ($this->calls as $position => $call) {
            $id = (int)$call->use_node_id;
            if (((int)$tree->row($id)->kind !== \parse\SYNTAX_NAME) || ((int)$call->target_symbol_id < 1) || ((int)$call->target_symbol_id > 4294967295)) { throw new \InvalidArgumentException('Invalid call binding'); }
            if (isset($this->call_index[$id])) { throw new \InvalidArgumentException('Duplicate call binding'); }
            $this->call_index[$id] = $position;
        }
        foreach ($this->scopes as $position => $scope) {
            $id = (int)$scope->block_node_id; $parent = (int)$scope->parent_scope_id;
            if (((int)$tree->row($id)->kind !== \parse\SYNTAX_BLOCK) || ($parent < 0) || ($parent > $position)) { throw new \InvalidArgumentException('Invalid resolved scope'); }
            if ($position > 0) { if ($parent === 0) { throw new \InvalidArgumentException('Multiple root scopes'); } }
            if (isset($this->scope_index[$id])) { throw new \InvalidArgumentException('Duplicate resolved scope'); }
            $this->scope_index[$id] = $position;
        }
        $body = (int)$this->owner->declaration->body_node_id;
        if ($body === 0) {
            if (q_count($this->scopes) !== 0) { throw new \InvalidArgumentException('Non-body owner has scopes'); }
        } else {
            if (q_count($this->scopes) === 0) { throw new \InvalidArgumentException('Missing owner root scope'); }
            if ((int)$this->scopes[0]->block_node_id !== $body) { throw new \InvalidArgumentException('Wrong owner root scope'); }
        }
        foreach ($this->locals as $position => $local) {
            $id = (int)$local->declaration_node_id; $scope = (int)$local->scope_id;
            $this->require_scope($scope);
            if (isset($this->local_index[$id])) { throw new \InvalidArgumentException('Duplicate local declaration'); }
            $kind = (int)$tree->row($id)->kind;
            if ($local->receiver) {
                if (($position !== 0) || ($scope !== 1) || ($kind !== \parse\SYNTAX_METHOD_DECLARATION)
                    || ($id !== (int)$this->owner->declaration->declaration_node_id) || ($this->owner->owner_symbol_id === 0)) { throw new \InvalidArgumentException('Invalid implicit receiver'); }
                $this->parameter_count++;
            } elseif ($kind === \parse\SYNTAX_PARAMETER_DECLARATION) {
                if (($scope !== 1) || ($position !== $this->parameter_count)) { throw new \InvalidArgumentException('Parameters must be the root local prefix'); }
                \parse\Syntax_Access::parameter_parts($tree,$id); $this->parameter_count++;
            } else { \parse\Syntax_Access::local_declaration_parts($tree,$id); }
            $this->local_index[$id] = $position;
        }
        foreach ($this->uses as $position => $use) {
            $id = (int)$use->use_node_id; $local = (int)$use->local_id; $access = (int)$use->access;
            if (((int)$tree->row($id)->kind !== \parse\SYNTAX_VARIABLE_NAME) || ($local < 1) || ($local > q_count($this->locals))
                || (($access !== \resolve_symbols\LOCAL_READ) && ($access !== \resolve_symbols\LOCAL_WRITE))) { throw new \InvalidArgumentException('Invalid local use'); }
            if (isset($this->use_index[$id])) { throw new \InvalidArgumentException('Duplicate local use'); }
            $this->use_index[$id] = $position;
        }
        foreach ($this->names as $position => $name) {
            $id = $name->use_node_id;
            if ((int)$tree->row($id)->kind !== \parse\SYNTAX_NAME) { throw new \InvalidArgumentException('Invalid free name'); }
            if (isset($this->name_index[$id]) || isset($this->call_index[$id])) { throw new \InvalidArgumentException('Duplicate free name'); }
            $this->name_index[$id] = $position;
        }
        foreach ($this->parameters as $parameter) {
            $parts = \parse\Syntax_Access::template_parameter_parts($tree,(int)$parameter->declaration_node_id);
            $contract = (int)$parameter->type_syntax_id === 0 ? \type_model\GENERIC_COPYABLE_VALUE : \type_model\GENERIC_NONE;
            if (((int)$parts->name_id !== (int)$parameter->name_node_id) || ((int)$parts->type_syntax_id !== (int)$parameter->type_syntax_id)
                || ((int)$parameter->contract !== $contract)) { throw new \InvalidArgumentException('Invalid template parameter'); }
        }
        foreach ($this->constants as $constant) {
            \parse\Syntax_Access::constant_parts($tree,(int)$constant->declaration_node_id); $this->require_scope((int)$constant->scope_id);
        }
        $applications /** hash<bool,int> */ = [];
        foreach ($this->applications as $application) {
            $id = $application->use_node_id;
            \parse\Syntax_Access::template_application_parts($tree,$id);
            if (!$application->definition->is_template()) { throw new \InvalidArgumentException('Application needs a template'); }
            if (isset($applications[$id])) { throw new \InvalidArgumentException('Duplicate application'); }
            $applications[$id] = true;
        }
        $members /** hash<bool,int> */ = [];
        foreach ($this->members as $member) {
            $id = (int)$member->use_node_id; $receiver = (int)$member->receiver_node_id; $node = $tree->row($id);
            if (((int)$node->kind !== \parse\SYNTAX_FIELD_EXPRESSION) || ((int)$node->first_child !== $receiver)) { throw new \InvalidArgumentException('Invalid member occurrence'); }
            if (!isset($this->use_index[$receiver])) { throw new \InvalidArgumentException('Missing member receiver binding'); }
            if (isset($members[$id])) { throw new \InvalidArgumentException('Duplicate member occurrence'); }
            $members[$id] = true;
        }
    }
}

/** Failure retains exact source bytes and exposes no partial result. */
final class Resolution_Attempt {
    public function __construct(private readonly ?Symbol_Resolution $value, public readonly string $error_path,
        public readonly int $error_start, public readonly int $error_length, public readonly string $error_reason) {}
    public function valid(): bool { return $this->value !== null; }
    public function result(): Symbol_Resolution {
        if ($this->value === null) { throw new \LogicException('Name resolution failed'); }
        return $this->value;
    }
}
