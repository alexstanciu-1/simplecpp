<?php
declare(strict_types=1);
namespace instantiate;
/** Private phase candidate. Joins validate complete batches before invoking these mutators. */
final class Instance_Store {
    use Instance_Lookup;
    public function view(): Instance_View { return new Instance_View($this->state,$this->templates); }
    private array $introduced /** vector<Instance_Context> */ = [];
    public function __construct(private Instance_State $state, private readonly \check_templates\Template_Set $templates) {
        $this->state = $state->fork();
        $empty_index /** hash<int> */ = []; $this->state->type_contexts = $empty_index;
        foreach ($this->state->concrete_types as $id => $definition) { $this->state->type_contexts[Instance_Keys::type($definition)] = $id; }
    }
    public function allocate(\type_model\Type_Store $types, int $definition, array $arguments /** vector<Template_Argument> */): int {
        return $this->state->identities->allocate($types,$definition,$arguments);
    }
    public function accept(Instance_Context $context): void {
        $old = $this->context_for($context->context_id);
        if ($old !== null) {
            if ($old !== $context) { throw new \LogicException('Conflicting current instance context'); }
            return;
        }
        $this->state->contexts[$context->context_id] = $context; $this->introduced[] = $context;
    }
    public function bind(Instance_Context $context, int $node, Instance_Context $target): void {
        $this->state->uses[Instance_Keys::application($context,$node)] = $target->context_id;
    }
    public function accept_type(Instance_Context $context, \type_model\Named_Definition $definition): void {
        if ($context->instance_id === 0) { throw new \LogicException('Concrete type requires an instance context'); }
        if ($this->context_for($context->context_id) !== $context) { throw new \LogicException('Type requires its accepted instance context'); }
        $old = $this->instance_type($context->instance_id);
        if ($old !== null) {
            $key = Instance_Keys::type($old);
            if (isset($this->state->type_contexts[$key])) {
                if ($this->state->type_contexts[$key] === $context->instance_id) { $this->state->type_contexts[$key] = 0; }
            }
        }
        $this->state->concrete_types[$context->instance_id] = $definition;
        $this->state->type_contexts[Instance_Keys::type($definition)] = $context->instance_id;
    }
    public function take_introduced(): array /** vector<Instance_Context> */ {
        $out = $this->introduced; $empty_introduced /** vector<Instance_Context> */ = []; $this->introduced = $empty_introduced; return $out;
    }
    public function snapshot(array $bindings /** vector<\resolve_symbols\Symbol_Resolution> */): Instance_Set {
        $copy = $this->state->fork(); $empty_bindings /** hash<\resolve_symbols\Symbol_Resolution,int> */ = []; $copy->source_bindings = $empty_bindings;
        foreach ($bindings as $binding) { $copy->source_bindings[$binding->owner->symbol_id] = $binding; }
        return new Instance_Set($copy,$this->templates);
    }
}
