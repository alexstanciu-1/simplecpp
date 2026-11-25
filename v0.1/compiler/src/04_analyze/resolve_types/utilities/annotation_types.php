<?php
declare(strict_types=1);
namespace resolve_types;
/** Source-attributed semantic rejection; internal stale-state errors remain LogicExceptions. */
final class Annotation_Diagnostic {
    public function __construct(public readonly string $path, public readonly int $start,
        public readonly int $length, public readonly string $reason) {}
}
/** Follow accepted bindings; spelling resolution belongs exclusively to resolve_symbols. */
final class Annotation_Types {
    private ?Annotation_Diagnostic $diagnostic_value = null;
    public function __construct(public readonly \resolve_symbols\Resolution_Set $names,
        public readonly Definition_View $definitions) {}
    public function diagnostic(): ?Annotation_Diagnostic { return $this->diagnostic_value; }
    public function fail(\collect_symbols\Symbol_Record $owner, int $node, string $reason): void {
        $frontend=$owner->source_frontend();$syntax=$frontend->tree->row($node);
        $this->diagnostic_value=new Annotation_Diagnostic($frontend->tokens->source->path,(int)$syntax->start,(int)$syntax->length,$reason);
        throw new \RuntimeException($reason);
    }
    public function bindings(\collect_symbols\Symbol_Record $owner): \resolve_symbols\Symbol_Resolution {
        $bindings=$this->names->for_symbol($owner->symbol_id);
        if ($bindings===null) { throw new \LogicException('Missing annotation bindings'); }
        if ($bindings->owner!==$owner) { throw new \LogicException('Stale annotation bindings'); }
        return $bindings;
    }
    public function bound_definition(\collect_symbols\Symbol_Record $owner, int $node): ?\type_model\Named_Definition {
        $bindings=$this->bindings($owner);
        if ((int)$owner->source_frontend()->tree->row($node)->kind===\parse\SYNTAX_TEMPLATE_APPLICATION) {
            $this->fail($owner,$node,'Template annotation requires instance preparation');
        }
        $binding=$bindings->name_for($node);
        if ($binding->role!==\resolve_symbols\NAME_TYPE) { throw new \LogicException('Expected bound type annotation'); }
        $name='';$namespace_name='';
        if ($binding->kind===\resolve_symbols\REFERENCE_SOURCE_TYPE) {
            $target=$this->names->declaration_for($binding->target_id);$name=$target->name;$namespace_name=$target->namespace_name;
        } elseif ($binding->kind===\resolve_symbols\REFERENCE_PROVIDED_TYPE) {
            $provided=$binding->provided_type;
            if ($provided===null) { throw new \LogicException('Missing provided type binding'); }
            $name=$provided->name;$namespace_name=$provided->namespace_name;
        } elseif ($binding->kind===\resolve_symbols\REFERENCE_PROVIDED_RECORD) {
            $record=$binding->provided_record;
            if ($record===null) { throw new \LogicException('Missing provided record binding'); }
            $name=$record->name;$namespace_name=$record->namespace_name;
        } else { throw new \LogicException('Dependent annotation reached concrete preparation'); }
        $definition=$this->definitions->find_type($name,$namespace_name);
        if ($binding->kind===\resolve_symbols\REFERENCE_PROVIDED_TYPE) {
            if ($definition!==$binding->provided_type) { throw new \LogicException('Stale provided type binding'); }
        }
        return $definition;
    }
    /** Require a ready source-storage type after concrete argument interpretation. */
    public static function definition(\instantiate\Instance_Context $context, int $node, string $role,
        \instantiate\Bindings $reader): \type_model\Named_Definition {
        $definition=$reader->type($context,$node);
        if ($definition===null) { $reader->annotations->fail($context->definition,$node,'Bound ' . $role . ' type requires unsupported concrete preparation'); }
        if ($definition->representation->kind()===\type_model\REPRESENTATION_BYTE_SPAN) {
            $reader->annotations->fail($context->definition,$node,'Byte spans are only supported as literal call operands');
        }
        return $definition;
    }
}
