<?php
declare(strict_types=1);
namespace instantiate;
/** Fixed retained registry. Constructor ownership is isolated even from its supplied seed. */
final class Instance_Set {
    use Instance_Lookup;
    public function view(): Instance_View { return new Instance_View($this->state,$this->templates); }
    public function __construct(private Instance_State $state, private readonly \check_templates\Template_Set $templates) {
        $this->state = $state->fork();
        $empty_index /** hash<int> */ = []; $this->state->type_contexts = $empty_index;
        foreach ($this->state->concrete_types as $id => $definition) { $this->state->type_contexts[Instance_Keys::type($definition)] = $id; }
    }
    public function candidate(): Instance_Store { return new Instance_Store($this->state,$this->templates); }
    public function functions(): array /** vector<Instance_Context> */ {
        $out /** vector<Instance_Context> */ = [];
        foreach ($this->state->contexts as $context) {
            if (($context->definition->kind() === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION) || ($context->definition->owner_symbol_id !== 0)) { $out[] = $context; }
        }
        return $out;
    }
    /** Owned transfer for coordinator filtering/export, never a mutable reference into this set. */
    public function export_state(): Instance_State { return $this->state->fork(); }
}
