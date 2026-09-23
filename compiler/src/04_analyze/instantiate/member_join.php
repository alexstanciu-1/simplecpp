<?php
declare(strict_types=1);
namespace instantiate;
/** Validate a complete selected batch before mutating private preparation candidates. */
final class Member_Join {
    private array $tasks /** vector<Member_Task> */ = [];
    public function __construct(private readonly Instance_Store $input,
        array $tasks /** vector<Member_Task> */, private readonly \type_model\Type_Store $types,
        private readonly \resolve_symbols\Resolution_Set $names,
        private readonly \resolve_types\Definition_View $definitions,
        private readonly \type_model\Type_Catalog $catalog,
        private readonly \collect_symbols\Symbol_Store $symbols, private readonly ?Instance_Set $previous = null) {
        foreach ($tasks as $task) { $this->tasks[] = $task; }
    }
    /** Adoption errors require discarding the private candidates; validation does not mutate them. */
    public function join(array $results /** vector<Member_Result> */): Instance_Store {
        if ($this->input->lineage() !== $this->types->lineage) { throw new \LogicException('Member join requires its fixed type lineage'); }
        $reader = new Bindings(new \resolve_types\Annotation_Types($this->names,$this->definitions),$this->catalog,$this->input->view());
        $selected /** hash<Member_Task> */ = [];
        foreach ($this->tasks as $task) {
            $key = $task->key();
            if (isset($selected[$key])) { throw new \LogicException('Duplicate member task'); }
            $this->require_task($task,$reader); $selected[$key] = $task;
        }
        $accepted /** hash<Member_Result> */ = [];
        foreach ($results as $result) {
            $task = $result->task; $key = $task->key();
            if (!isset($selected[$key])) { throw new \LogicException('Unexpected member result'); }
            if ($selected[$key] !== $task) { throw new \LogicException('Unexpected member result task'); }
            if (isset($accepted[$key])) { throw new \LogicException('Duplicate member result'); }
            $expected = Member_Worker::run($task,$reader,$this->symbols);
            if (($result->receiver !== $expected->receiver) || ($result->definition !== $expected->definition)) { throw new \LogicException('Stale member receiver or method'); }
            if ($result->argument_count() !== $expected->argument_count()) { throw new \LogicException('Stale member arguments'); }
            for ($i = 0; $i < $expected->argument_count(); $i++) {
                if ($result->argument_at($i) !== $expected->argument_at($i)) { throw new \LogicException('Stale member argument identity'); }
            }
            $accepted[$key] = $result;
        }
        if (q_count($accepted) !== q_count($selected)) { throw new \LogicException('Incomplete member preparation'); }
        foreach ($this->tasks as $task) {
            $key = $task->key(); $result = $accepted[$key];
            if ($result->receiver === null) { continue; }
            $definition = $result->definition;
            if ($definition === null) { throw new \LogicException('Accepted member lost its method'); }
            $arguments = $result->arguments();
            $id = $this->input->allocate($this->types,$definition->symbol_id,$arguments);
            $context = new Instance_Context($definition,$id,$arguments,$result->receiver);
            $old = $this->input->context_for($context->context_id);
            if ($old === null) {
                if ($this->previous !== null) { $old = $this->previous->context_for($context->context_id); }
            }
            if ($old !== null) {
                if (Member_Join::matches_context($old,$context)) { $context = $old; }
            }
            $this->input->accept($context);
            if ($task->declaration === null) { $this->input->bind($task->context,$task->use_node_id,$context); }
        }
        return $this->input;
    }
    private function require_task(Member_Task $task, Bindings $reader): void {
        $owner = $task->context->definition;
        if ($this->symbols->symbol_by_id($owner->symbol_id) !== $owner) { throw new \LogicException('Stale member task owner'); }
        if ($task->declaration === null) {
            $bindings = $reader->annotations->bindings($owner); $found = false;
            for ($i = 0; $i < $bindings->members_count(); $i++) {
                $use = $bindings->members_at($i);
                if (((int)$use->use_node_id === $task->use_node_id) && ((int)$use->receiver_node_id === $task->receiver_node_id)) { $found = true; break; }
            }
            if (!$found) { throw new \LogicException('Stale member occurrence'); }
        } else {
            $method = $task->declaration;
            if (($owner->kind() !== \collect_symbols\SYMBOL_STRUCT) && ($owner->kind() !== \collect_symbols\SYMBOL_TEMPLATE_STRUCT)) { throw new \LogicException('Member declaration requires a record owner'); }
            $kind = \collect_symbols\SYMBOL_FUNCTION;
            if ($owner->is_template()) { $kind = \collect_symbols\SYMBOL_TEMPLATE_FUNCTION; }
            if (($method->owner_symbol_id !== $owner->symbol_id) || ($method->kind() !== $kind)) { throw new \LogicException('Stale member declaration owner'); }
            if ($this->symbols->symbol_by_id($method->symbol_id) !== $method) { throw new \LogicException('Stale member declaration'); }
        }
        if ($task->context->instance_id !== 0) {
            if ($this->input->context_for($task->context->context_id) !== $task->context) { throw new \LogicException('Stale member concrete context'); }
        }
    }
    private static function matches_context(Instance_Context $left, Instance_Context $right): bool {
        if (($left->definition !== $right->definition) || ($left->receiver_type !== $right->receiver_type)) { return false; }
        if ($left->argument_count() !== $right->argument_count()) { return false; }
        for ($i = 0; $i < $left->argument_count(); $i++) {
            if ($left->argument_at($i) !== $right->argument_at($i)) { return false; }
        }
        return true;
    }
}
