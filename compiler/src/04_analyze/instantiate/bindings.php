<?php
declare(strict_types=1);
namespace instantiate;
/** One fixed concrete reading context; no identity allocation or constant execution. */
final class Bindings {
    public function __construct(public readonly \resolve_types\Annotation_Types $annotations,
        public readonly \type_model\Type_Catalog $catalog, public readonly Instance_View $instances) {}
    public function type(Instance_Context $context, int $node): ?\type_model\Named_Definition {
        $owner=$context->definition;
        if ((int)$owner->source_frontend()->tree->row($node)->kind===\parse\SYNTAX_TEMPLATE_APPLICATION) { return $this->instances->type_for($context,$node); }
        $binding=$this->annotations->bindings($owner)->name_for($node);
        if ($binding->kind===\resolve_symbols\REFERENCE_TEMPLATE_PARAMETER) {
            if ($binding->target_id >= $context->argument_count()) { throw new \LogicException('Missing concrete type parameter'); }
            $argument=$context->argument_at($binding->target_id);
            if ($argument->value!==null) { throw new \LogicException('Missing concrete type parameter'); }
            return $argument->type;
        }
        return $this->annotations->bound_definition($owner,$node);
    }
    /** Only exact integer literals, global constants and integer parameters are supported. */
    public function value(Instance_Context $context, int $node): Template_Argument {
        $owner=$context->definition;
        if ((int)$owner->source_frontend()->tree->row($node)->kind===\parse\SYNTAX_INTEGER_LITERAL) { return $this->literal($owner,$node,$this->catalog->integer_literal_type); }
        $argument=$this->value_reference($context,$node);
        if ($argument===null) {
            $this->annotations->fail($owner,$node,'Only integer literals, global integer constants and integer template parameters are supported; constant evaluation is not implemented');
        }
        if ($argument->value===null) {
            $this->annotations->fail($owner,$node,'Only integer literals, global integer constants and integer template parameters are supported; constant evaluation is not implemented');
        }
        return $argument;
    }
    private function value_reference(Instance_Context $context, int $node): ?Template_Argument {
        $owner=$context->definition;
        if ((int)$owner->source_frontend()->tree->row($node)->kind!==\parse\SYNTAX_NAME) { return null; }
        $binding=$this->annotations->bindings($owner)->name_for($node);
        if ($binding->kind===\resolve_symbols\REFERENCE_TEMPLATE_PARAMETER) {
            if ($binding->target_id < $context->argument_count()) { return $context->argument_at($binding->target_id); }
        }
        if ($binding->kind===\resolve_symbols\REFERENCE_PROJECT_CONSTANT) { return $this->instances->constant_for($binding->target_id); }
        return null;
    }
    public function literal(\collect_symbols\Symbol_Record $owner, int $node, \type_model\Named_Definition $type): Template_Argument {
        $frontend=$owner->source_frontend();$syntax=$frontend->tree->row($node);
        if ((int)$syntax->kind!==\parse\SYNTAX_INTEGER_LITERAL) { $this->annotations->fail($owner,$node,'Constant initializer must be an integer literal; constant evaluation is not implemented'); }
        $text=string_byte_slice($frontend->tokens->source->content,(int)$syntax->start,(int)$syntax->length);$value='';
        try { $value=\check_bodies\Integer_Literals::resolve($text,$type); }
        catch (\RangeException $error) { $this->annotations->fail($owner,$node,$error->getMessage()); }
        return new Template_Argument($type,$value);
    }
}
