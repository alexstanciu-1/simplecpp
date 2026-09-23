<?php
declare(strict_types=1);
namespace resolve_symbols;
/** One-shot private traversal over fixed declarations, source and catalog. */
final class Resolution_Worker {
    use Name_Resolution;
    use Declaration_Resolution;
    use Statement_Resolution;
    use Expression_Resolution;
    private array $calls /** vector<Symbol_Binding> */ = [];
    private array $members /** vector<Member_Call_Binding> */ = [];
    private array $scopes /** vector<Lexical_Scope> */ = [];
    private array $locals /** vector<Local_Record> */ = [];
    private array $uses /** vector<Local_Binding> */ = [];
    private array $scope_names /** vector<Scope_Names> */ = [];
    private array $name_bindings /** vector<Name_Binding> */ = [];
    private array $applications /** vector<Template_Application_Binding> */ = [];
    private array $template_parameters /** vector<Template_Parameter> */ = [];
    private array $parameter_names /** hash<int> */ = [];
    private array $constants /** vector<Scoped_Constant> */ = [];
    private int $initializing_constant = 0;
    private bool $started = false;
    private int $error_start = 0;
    private int $error_length = 0;
    private string $error_reason = '';
    public function __construct(private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \collect_symbols\Symbol_Record $owner, private readonly \type_model\Type_Catalog $catalog) {}
    public function run(): Resolution_Attempt {
        if (!$this->owner->is_source()) { throw new \LogicException('Resolution requires a source owner'); }
        if ($this->started) { throw new \LogicException('Resolution worker is one-shot'); }
        $this->started = true;
        if (!$this->symbols->contains($this->owner->symbol_id)) { throw new \LogicException('Removed resolution task'); }
        if ($this->symbols->symbol_by_id($this->owner->symbol_id) !== $this->owner) { throw new \LogicException('Stale resolution task'); }
        try {
            $this->definition();
            $body = (int)$this->owner->source_fact()->body_node_id;
            if ($body !== 0) {
                $root = $this->enter($body,0); $this->parameters($root->scope_id); $this->statements($root);
            }
        } catch (\RuntimeException $error) {
            if ($this->error_reason === '') { throw new \LogicException('Unexpected resolution runtime failure'); }
            return new Resolution_Attempt(null,$this->owner->source_frontend()->tokens->source->path,$this->error_start,$this->error_length,$this->error_reason);
        }
        $result = new Symbol_Resolution($this->owner,$this->calls,$this->scopes,$this->locals,$this->uses,
            $this->template_parameters,$this->constants,$this->members,$this->name_bindings,$this->applications);
        return new Resolution_Attempt($result,'',0,0,'');
    }
    private function enter(int $block_id, int $parent): Scope_Cursor {
        $node = $this->owner->source_frontend()->tree->row($block_id);
        if ((int)$node->kind !== \parse\SYNTAX_BLOCK) { throw new \LogicException('Expected callable block'); }
        $row = new Lexical_Scope(); $row->block_node_id = $block_id; $row->parent_scope_id = $parent;
        $this->scopes[] = $row; $this->scope_names[] = new Scope_Names();
        return new Scope_Cursor(q_count($this->scopes),(int)$node->first_child,true);
    }
    private function declare_local(int $declaration, int $variable, int $scope): int {
        $name = $this->text($variable); $bare = string_byte_slice($name,1,string_byte_len($name)-1);
        if (isset($this->parameter_names[$bare])) { $this->fail($variable,"Local '" . $name . "' conflicts with a template parameter"); }
        if ($this->scope_names[$scope-1]->local($name) !== 0) { $this->fail($variable,"Duplicate local '" . $name . "' in this block"); }
        $row = new Local_Record(); $row->declaration_node_id = $declaration; $row->scope_id = $scope;
        $this->locals[] = $row; $id = q_count($this->locals);
        $this->scope_names[$scope-1]->add_local($name,$id); return $id;
    }
    private function bind_local(int $id, int $scope, int $access, int $initializing): void {
        $name = $this->text($id); $local = $this->find_local($name,$scope);
        if ($local === 0) { $this->fail($id,"Unknown local '" . $name . "'"); }
        if ($local === $initializing) { $this->fail($id,"Local '" . $name . "' cannot read itself in its initializer"); }
        $row = new Local_Binding(); $row->use_node_id = $id; $row->local_id = $local; $row->access = $access; $this->uses[] = $row;
    }
    private function find_local(string $name, int $scope): int {
        $id = $scope;
        while ($id !== 0) {
            $local = $this->scope_names[$id-1]->local($name); if ($local !== 0) { return $local; }
            $id = (int)$this->scopes[$id-1]->parent_scope_id;
        }
        return 0;
    }
    private function text(int $id): string { return \collect_symbols\File_Collector::name_text($this->owner->source_frontend(),$id); }
    private function fail(int $id, string $message): void {
        $node = $this->owner->source_frontend()->tree->row($id);
        $this->error_start = (int)$node->start; $this->error_length = (int)$node->length; $this->error_reason = $message;
        throw new \RuntimeException('Source name resolution failed');
    }
}
