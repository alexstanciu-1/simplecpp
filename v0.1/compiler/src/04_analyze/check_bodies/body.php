<?php
declare(strict_types=1);
namespace check_bodies;
/** One-shot source worker. Fixed signatures are read; called bodies are never checked recursively. */
final class Body_Worker {
    use Expression_Checking;
    use Place_Checking;
    use Statement_Checking;
    use Local_Write_Checking;
    use Control_Statement_Checking;
    private bool $started = false;
    private array $operation_contracts /** hash<\type_model\Operation_Contract> */ = [];
    public function __construct(private readonly Body_Context $context, private readonly Body_Output $output,
        private readonly \instantiate\Bindings $reader) {}
    public static function prepare(\resolve_types\Callable_Input $input, \resolve_symbols\Symbol_Resolution $names,
        \resolve_types\Type_Resolution $types): Body_Worker {
        $context = new Body_Context($input,$names,$types);
        $reader = new \instantiate\Bindings(new \resolve_types\Annotation_Types($types->names,
            new \resolve_types\Definition_View($types->catalog,$types->types)),$types->catalog,$types->instances->view());
        return new Body_Worker($context,new Body_Output($names->scopes_count()),$reader);
    }
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic { return $this->reader->annotations->diagnostic(); }
    public function check(): Checked_Body {
        if ($this->started) { throw new \LogicException('Body worker is one-shot'); }
        $this->started = true;
        $return_type = $this->context->signature($this->context->input->callable_id)->representation->signature_return();
        $is_void = $this->context->types->definition_for($return_type)->representation->kind() === \type_model\REPRESENTATION_VOID;
        $blocks = $this->check_statements($return_type,$is_void); $falls_through = false;
        foreach (Flow_Graph::reachable($blocks) as $id) { if ($blocks[$id - 1]->end === \check_bodies\FLOW_FALLTHROUGH) { $falls_through = true; } }
        if ($falls_through && !$is_void) { $this->fail((int)$this->context->input->owner->source_fact()->body_node_id,'Callable can finish without returning a value'); }
        return new Checked_Body($this->context->input,$this->context->names,$falls_through,$this->context->local_types(),
            $this->output->completed_values(),$this->output->completed_calls(),$this->output->completed_statements(),
            $this->output->completed_scopes(),$this->output->completed_arguments(),$blocks,
            $this->context->type_dependencies(),$this->context->signature_dependencies());
    }
    private function tree(): \parse\Syntax_Arena { return $this->context->input->owner->source_frontend()->tree; }
    private function text(int $id): string {
        $node = $this->tree()->row($id);
        return string_byte_slice($this->context->input->owner->source_frontend()->tokens->source->content,(int)$node->start,(int)$node->length);
    }
    private function fail(int $node, string $message): void { $this->reader->annotations->fail($this->context->input->owner,$node,$message); }
    private function append_value(Typed_Value $value): int { $this->context->retain_type($value->type_id); return $this->output->append_value($value); }
    private function append_place(Place_Cursor $cursor): int {
        $this->context->retain_type($cursor->type);
        return $this->output->append_place($cursor->node,$cursor->type,new Place($cursor->local,$cursor->projections));
    }
    private function life(int $type): \type_model\Lifetime_Policy {
        $life = $this->context->types->definition_for($type)->lifetime;
        if ($life === null) { throw new \LogicException('Expected value lifetime'); }
        return $life->policy();
    }
    private function convert(int $id, int $destination, string $role, int $node): int {
        if ($id === 0) {
            $capital = $role;
            if ($role === 'return') { $capital = 'Return'; } elseif ($role === 'argument') { $capital = 'Argument'; }
            elseif ($role === 'assignment') { $capital = 'Assignment'; } elseif ($role === 'initialization') { $capital = 'Initialization'; }
            $this->fail($node,$capital . ' expression produces no value');
        }
        $source = $this->output->value_for($id)->type_id;
        $selection = Conversion_Resolver::resolve($this->context->types,new Conversion_Request($source,$destination,\type_model\CONVERSION_IMPLICIT));
        if ($selection === null) { $this->fail($node,'Unsupported implicit ' . $role . ' conversion from ' . $this->context->types->definition_for($source)->name . ' to ' . $this->context->types->definition_for($destination)->name); }
        if ($selection->form === \check_bodies\CONVERSION_IDENTITY) { return $id; }
        $this->output->select_place_access($id,false);
        return $this->append_value(new Typed_Value($node,$destination,\check_bodies\VALUE_CONVERSION,'',0,null,new Conversion_Value($id,$selection->primitive)));
    }
}
