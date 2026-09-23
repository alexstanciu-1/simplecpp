<?php
declare(strict_types=1);
namespace resolve_types;
final class Local_Type_Resolver {
    public static function select(\collect_symbols\Symbol_Store $symbols, \instantiate\Bindings $reader,
        \type_model\Type_Store $types, Local_Type_Validity $history, bool $full, Entry_Contract $entry): array /** vector<Callable_Input> */ {
        $tasks /** vector<Callable_Input> */ = [];
        foreach (Callable_Inputs::all($symbols,$reader->instances) as $input) {
            if (!Signature_Resolver::body_participates($input,$entry)) { continue; }
            $names = Local_Type_Validity::names_for($input->owner,$reader->annotations->names);
            if ($names->locals_count() === 0) { continue; }
            if ($full) { $tasks[] = $input; }
            else if (!$history->is_current($types,$names,$input)) { $tasks[] = $input; }
        }
        return $tasks;
    }
    public static function resolve(\collect_symbols\Symbol_Store $symbols, \instantiate\Bindings $reader, Callable_Input $input): Local_Type_Request {
        if (!Callable_Inputs::is_current($input,$symbols,$reader->instances)) { throw new \LogicException('Stale local type task'); }
        $names = Local_Type_Validity::names_for($input->owner,$reader->annotations->names);
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        $tree = $input->owner->source_frontend()->tree;
        for ($i = $names->runtime_parameter_count(); $i < $names->locals_count(); $i++) {
            $node = (int)\parse\Syntax_Access::local_declaration_parts($tree,(int)$names->locals_at($i)->declaration_node_id)->type_syntax_id;
            $definition = Annotation_Types::definition($input->context(),$node,'local',$reader);
            if ($definition->representation->kind() === \type_model\REPRESENTATION_VOID) { $reader->annotations->fail($input->owner,$node,'A local requires a value type; void has no value'); }
            $definitions[] = $definition;
        }
        return new Local_Type_Request($input,$names,$definitions);
    }
}
