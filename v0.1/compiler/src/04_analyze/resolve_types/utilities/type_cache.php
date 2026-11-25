<?php
declare(strict_types=1);
namespace resolve_types;
/** Coordinator chooses full versus incremental; this owner never changes that decision. */
final class Type_Cache {
    public function __construct(private readonly ?\type_model\Type_Store $previous) {}
    public function requires_rebuild(\type_model\Type_Context $context): bool {
        if ($this->previous === null) { return true; }
        $old = $this->previous->context;
        return ($old->configuration_key !== $context->configuration_key) || ($old->provider_key !== $context->provider_key) || ($old->target_key !== $context->target_key);
    }
    public function prepare(\type_model\Type_Context $context, bool $full): \type_model\Type_Store {
        if ($full) { return \type_model\Type_Store::fresh($context); }
        if ($this->requires_rebuild($context)) { throw new \LogicException('Type cache requires full rebuild'); }
        return $this->previous->fork();
    }
    /** Coordinator-only authoritative definitions; failed candidates must be discarded. */
    public static function materialize(\type_model\Type_Store $types, \type_model\Named_Definition $definition): int {
        $id = $types->reference_type($definition->name,$definition->namespace_name);
        if (!$types->is_declared($id)) { $types->declare_type($definition->name,$definition->namespace_name); }
        $types->bind_definition($id,$definition);
        if ($types->needs_representation($id)) {
            $shape = $definition->representation; $kind = $shape->kind(); $representation = 0;
            if ($kind === \type_model\REPRESENTATION_VOID) { $representation = $types->intern_void(); }
            elseif ($kind === \type_model\REPRESENTATION_INTEGER) { $representation = $types->intern_integer($shape->bit_width()); }
            elseif ($kind === \type_model\REPRESENTATION_FLOATING) { $representation = $types->intern_float($shape->floating_format()); }
            elseif ($kind === \type_model\REPRESENTATION_BYTE_SPAN) { $representation = $types->intern_byte_span(); }
            elseif ($kind === \type_model\REPRESENTATION_OPAQUE) { $representation = $types->intern_opaque($shape->opaque_size(),$shape->opaque_alignment()); }
            else { throw new \LogicException('Unsupported materialization contract'); }
            $types->set_representation($id,$representation);
        }
        return $id;
    }
    public static function materialize_field(\type_model\Type_Store $types, \type_model\Field_Type $recipe): int {
        $element=Type_Cache::materialize($types,$recipe->element);
        if ($recipe->extent===0) { return $element; }
        $name='array_' . $element . '_' . $recipe->extent; $namespace_name='@compiler/array';
        $known=$types->find_type($name,$namespace_name);
        if ($known!==0) { return $known; }
        $shape=$types->intern_array($element,$recipe->extent); $id=$types->declare_type($name,$namespace_name);
        $members /** vector<int> */ = [$element]; $bodies=new Lifecycle_Bodies();
        $life=Lifecycle_Composition::derive($types,$id,$members,$recipe->extent,$bodies);
        $definition=new \type_model\Named_Definition($name,$namespace_name,$types->representation_by_id($shape),$life,null,'',false,false,$recipe->element->struct_field);
        $types->bind_definition($id,$definition); $types->set_representation($id,$shape); return $id;
    }
}
