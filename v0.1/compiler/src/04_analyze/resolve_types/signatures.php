<?php
declare(strict_types=1);
namespace resolve_types;
/** Resolve fixed callable annotations. Incremental selection and canonical publication remain separate. */
final class Signature_Resolver {
    public static function participates(Callable_Input $input, Entry_Contract $entry): bool {
        $symbol = $input->owner; $kind = $symbol->kind();
        $participates = false;
        if ($kind === \collect_symbols\SYMBOL_FUNCTION) {
            $participates = ($symbol->owner_symbol_id === 0) || ($input->instance !== null);
        } else if ($kind === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION) { $participates = $input->instance !== null; }
        else if ($kind === \collect_symbols\SYMBOL_FILE_ENTRY) { $participates = $symbol === $entry->symbol; }
        else if (($kind === \collect_symbols\SYMBOL_STRUCT) || ($kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT) || ($kind === \collect_symbols\SYMBOL_CONSTANT)) { $participates = false; }
        else { throw new \LogicException('Unsupported callable signature owner'); }
        return $participates;
    }
    public static function body_participates(Callable_Input $input, Entry_Contract $entry): bool {
        if (!$input->owner->is_source()) { return false; }
        if ((int)$input->owner->source_fact()->body_node_id === 0) { return false; }
        return Signature_Resolver::participates($input,$entry);
    }
    public static function resolve(\collect_symbols\Symbol_Store $symbols, \instantiate\Bindings $reader,
        Callable_Input $input, Entry_Contract $entry, array $prepared /** hash<\type_model\Runtime_Callable,int> */): Signature_Request {
        if (!Callable_Inputs::is_current($input,$symbols,$reader->instances)) { throw new \LogicException('Stale signature task'); }
        if (!Signature_Resolver::participates($input,$entry)) { throw new \LogicException('Nonparticipating signature task'); }
        $symbol = $input->owner; $parameters /** vector<\type_model\Named_Definition> */ = []; $passing /** vector<int> */ = [];
        if ($symbol === $entry->symbol) { return new Signature_Request($input,0,$entry->return_type,$parameters,$passing); }
        if (!$symbol->is_source()) {
            if ($symbol->provider()->kind() === \collect_symbols\PROVIDER_STORAGE_FUNCTION) { return Storage_Signatures::signature($input,$reader->annotations->definitions); }
        }
        $external = Callable_Inputs::external($input,$prepared);
        if ($external !== null) {
            for ($i = 0; $i < $external->signature->parameter_count(); $i++) {
                $parameters[] = Signature_Resolver::referenced_definition($reader->annotations->definitions,$external->signature->parameter_at($i)->type);
                $passing[] = $external->passing_for($i);
            }
            $result = Signature_Resolver::referenced_definition($reader->annotations->definitions,$external->signature->result->type);
            return new Signature_Request($input,0,$result,$parameters,$passing);
        }
        $context = $input->context(); $tree = $symbol->source_frontend()->tree;
        if ($input->instance !== null) {
            if ($symbol->owner_symbol_id === 0) {
                $wrapped = (int)\parse\Syntax_Access::template_parts($tree,(int)$symbol->source_fact()->declaration_node_id)->declaration_id;
                $kind = (int)$tree->row($wrapped)->kind;
                if (($kind === \parse\SYNTAX_CONSTEXPR_DECLARATION) || ($kind === \parse\SYNTAX_CONSTEVAL_DECLARATION)) { $reader->annotations->fail($symbol,$wrapped,'Compile-time function execution is not implemented'); }
            }
        }
        $parts = \parse\Syntax_Access::function_parts($tree,\parse\Syntax_Access::underlying_declaration($tree,(int)$symbol->source_fact()->declaration_node_id));
        $annotation = (int)$parts->return_type_id;
        $definition = Annotation_Types::definition($context,$annotation,'return',$reader);
        if ($context->receiver_type !== null) {
            $parameters[] = $context->receiver_type;
            $mode = \type_model\PASS_BORROW_MUTABLE; if ($symbol->receiver_const()) { $mode = \type_model\PASS_BORROW_CONST; } $passing[] = $mode;
        }
        $node = \parse\Syntax_Access::first_parameter($tree,(int)$parts->parameters_id);
        while ($node !== 0) {
            $type_node = (int)\parse\Syntax_Access::parameter_parts($tree,$node)->type_syntax_id;
            $mode = Parameter_Contracts::passing($tree,$node);
            $parameter = Annotation_Types::definition($context,$type_node,'parameter',$reader);
            Parameter_Contracts::validate($symbol,$type_node,$mode,$parameter,$reader->annotations);
            $parameters[] = $parameter; $passing[] = $mode; $node = (int)$tree->row($node)->next_sibling;
        }
        $request = new Signature_Request($input,$annotation,$definition,$parameters,$passing);
        Source_Lifecycle_Signature::validate($request,$reader->annotations);
        return $request;
    }
    private static function referenced_definition(Definition_View $definitions, \type_model\Type_Reference $reference): \type_model\Named_Definition {
        $result = $definitions->find_type($reference->name(),$reference->namespace_name());
        if ($result === null) { throw new \LogicException('Unresolved provider signature type: '.$reference->name()); }
        return $result;
    }
}
