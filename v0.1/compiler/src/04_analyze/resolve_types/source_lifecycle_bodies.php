<?php
declare(strict_types=1);
namespace resolve_types;
/** Interpret source lifecycle declarations; checked body execution and signatures remain separate. */
final class Source_Lifecycle_Bodies {
    public static function prepare(\collect_symbols\Symbol_Record $owner,
        \collect_symbols\Symbol_Store $symbols, Annotation_Types $annotations): Lifecycle_Bodies {
        $tree = $owner->source_frontend()->tree; $out = new Lifecycle_Bodies();
        $cursor = \parse\Syntax_Access::struct_members($tree,(int)$owner->source_fact()->declaration_node_id,\parse\SYNTAX_METHOD_DECLARATION);
        while ($cursor->advance()) {
            $member = $cursor->current();
            $parts = \parse\Syntax_Access::function_parts($tree,\parse\Syntax_Access::underlying_declaration($tree,$member));
            $name = \collect_symbols\File_Collector::name_text($owner->source_frontend(),(int)$parts->name_id);
            $role = Source_Lifecycle::name_role($name);
            if ($role === \type_model\LIFECYCLE_NONE) { continue; }
            $kind = \collect_symbols\SYMBOL_FUNCTION;
            if ($owner->is_template()) { $kind = \collect_symbols\SYMBOL_TEMPLATE_FUNCTION; }
            $id = $symbols->find_symbol($name,$kind,$owner->symbol_id,$owner->namespace_name);
            if ($id === 0) { throw new \LogicException('Lifecycle normalization requires its fixed member symbols'); }
            $symbol = $symbols->symbol_by_id($id); $has_source = \type_model\Lifecycle_Roles::has_source($role);
            $parameter = \parse\Syntax_Access::first_parameter($tree,(int)$parts->parameters_id);
            if ($symbol->receiver_const() || ((int)$parts->body_id === 0) || (!$has_source && ($parameter !== 0))) {
                $annotations->fail($symbol,(int)$symbol->source_fact()->name_node_id,'Custom lifecycle requires a mutable receiver, a body and no explicit parameters except the copy source');
            }
            if ($has_source) {
                if ($parameter === 0) { $annotations->fail($symbol,(int)$symbol->source_fact()->name_node_id,'Custom copy operation requires one const reference source'); }
                $source = \parse\Syntax_Access::parameter_parts($tree,$parameter);
                if (((int)$tree->row($parameter)->next_sibling !== 0) || ((int)$source->reference !== \parse\SYNTAX_CONST_REFERENCE_ANNOTATION)) {
                    $annotations->fail($symbol,(int)$symbol->source_fact()->name_node_id,'Custom copy operation requires one const reference source');
                }
            }
            if ($role === \type_model\LIFECYCLE_DEFAULT) { $out->constructor = $id; }
            else if ($role === \type_model\LIFECYCLE_DESTROY) { $out->destructor = $id; }
            else if ($role === \type_model\LIFECYCLE_COPY) { $out->copy = $id; }
            else if ($role === \type_model\LIFECYCLE_ASSIGN) { $out->assignment = $id; }
        }
        return $out;
    }
}
