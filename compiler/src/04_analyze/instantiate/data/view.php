<?php
declare(strict_types=1);
namespace instantiate;
/** Borrowed read-only handle. The coordinator fixes the backing registry during a batch. */
final class Instance_View {
    use Instance_Lookup;
    public function __construct(private readonly Instance_State $state, private readonly \check_templates\Template_Set $templates) {}
}
/** One read implementation for fixed-batch candidate reads and immutable retained snapshots. */
trait Instance_Lookup {
    public function template_checks(): \check_templates\Template_Set { return $this->templates; }
    public function context_for(int $id): ?Instance_Context {
        if (!isset($this->state->contexts[$id])) { return null; }
        return $this->state->contexts[$id];
    }
    public function constant_for(int $id): ?Template_Argument {
        if (!isset($this->state->constants[$id])) { return null; }
        return $this->state->constants[$id];
    }
    public function instance_type(int $id): ?\type_model\Named_Definition {
        if (!isset($this->state->concrete_types[$id])) { return null; }
        return $this->state->concrete_types[$id];
    }
    public function type_context(\type_model\Named_Definition $type): ?Instance_Context {
        $key = Instance_Keys::type($type);
        if (!isset($this->state->type_contexts[$key])) { return null; }
        $id = $this->state->type_contexts[$key];
        if ($id === 0) { return null; }
        return $this->context_for(\collect_symbols\MAX_SYMBOL_ID+$id);
    }
    public function application(Instance_Context $context, int $node): ?Instance_Context {
        $key = Instance_Keys::application($context,$node);
        if (!isset($this->state->uses[$key])) { return null; }
        return $this->context_for($this->state->uses[$key]);
    }
    public function type_for(Instance_Context $context, int $node): ?\type_model\Named_Definition {
        $instance = $this->application($context,$node);
        if ($instance === null) { return null; }
        return $this->instance_type($instance->instance_id);
    }
    public function size(): int { return q_count($this->state->contexts); }
    public function next_id(): int { return $this->state->identities->next_id(); }
    public function lineage(): \type_model\Type_Lineage { return $this->state->identities->lineage; }
    public function contexts(): array /** vector<Instance_Context> */ {
        $out /** vector<Instance_Context> */ = []; foreach ($this->state->contexts as $context) { $out[] = $context; } return $out;
    }
    public function constant_owner(int $id): ?\collect_symbols\Symbol_Record {
        if (!isset($this->state->constant_owners[$id])) { return null; } return $this->state->constant_owners[$id];
    }
    public function source_binding(int $id): ?\resolve_symbols\Symbol_Resolution {
        if (!isset($this->state->source_bindings[$id])) { return null; } return $this->state->source_bindings[$id];
    }
}
final class Instance_Keys {
    public static function type(\type_model\Named_Definition $type): string {
        return string_byte_len($type->namespace_name) . ':' . $type->namespace_name . $type->name;
    }
    public static function application(Instance_Context $context, int $node): string { return $context->context_id . ':' . $node; }
}
