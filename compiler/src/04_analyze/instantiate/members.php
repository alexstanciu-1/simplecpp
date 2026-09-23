<?php
declare(strict_types=1);
namespace instantiate;
/** Read fixed receiver annotations and nominal member ownership; never infer expression types. */
final class Member_Worker {
    public static function run(Member_Task $task, Bindings $reader, \collect_symbols\Symbol_Store $symbols): Member_Result {
        $receiver = Member_Worker::receiver($task,$reader);
        $arguments /** vector<Template_Argument> */ = [];
        if ($receiver === null) { return new Member_Result($task,null,null,$arguments); }
        $context = $reader->instances->type_context($receiver);
        $family_receiver = false;
        if ($context !== null) {
            if (!$context->definition->is_source()) { $family_receiver = $context->definition->provider()->kind() === \collect_symbols\PROVIDER_FAMILY; }
        }
        if (($receiver->representation->kind() !== \type_model\REPRESENTATION_STRUCTURE) && !$family_receiver) {
            $reader->annotations->fail($task->context->definition,$task->source_node(),'Method receiver requires a source record');
        }
        $owner = Member_Worker::owner($receiver,$symbols);
        if ($context !== null) {
            $owner = $context->definition;
            for ($i = 0; $i < $context->argument_count(); $i++) { $arguments[] = $context->argument_at($i); }
        }
        $method = $task->declaration;
        if ($method === null) {
            if ($owner !== null) { $method = Member_Worker::method_for($task,$owner,$reader,$symbols); }
        }
        if ($method === null) { $reader->annotations->fail($task->context->definition,$task->source_node(),'Unknown or unsupported record method'); }
        return new Member_Result($task,$receiver,$method,$arguments);
    }
    public static function receiver(Member_Task $task, Bindings $reader): ?\type_model\Named_Definition {
        if ($task->declaration !== null) {
            $owner = $task->context->definition;
            $name = $owner->name; $namespace_name = $owner->namespace_name;
            if ($owner->is_template()) { $name = $task->context->type_name(); $namespace_name = $task->context->type_namespace(); }
            return $reader->annotations->definitions->find_type($name,$namespace_name);
        }
        $bindings = $reader->annotations->bindings($task->context->definition);
        $binding = $bindings->binding_for($task->receiver_node_id);
        $local = $bindings->local_for((int)$binding->local_id);
        if ($local->receiver) {
            $type = $task->context->receiver_type;
            if ($type === null) { throw new \LogicException('Missing concrete method receiver'); }
            return $type;
        }
        return $reader->type($task->context,Member_Worker::receiver_annotation($task,$reader));
    }
    public static function receiver_annotation(Member_Task $task, Bindings $reader): int {
        if ($task->declaration !== null) { throw new \LogicException('Declaration member request has no local receiver annotation'); }
        $bindings = $reader->annotations->bindings($task->context->definition);
        $binding = $bindings->binding_for($task->receiver_node_id); $local = $bindings->local_for((int)$binding->local_id);
        if ($local->receiver) { throw new \LogicException('Concrete this receiver has no local annotation prerequisite'); }
        $tree = $task->context->definition->source_frontend()->tree; $node = (int)$local->declaration_node_id;
        if ((int)$tree->row($node)->kind === \parse\SYNTAX_PARAMETER_DECLARATION) { return (int)\parse\Syntax_Access::parameter_parts($tree,$node)->type_syntax_id; }
        return (int)\parse\Syntax_Access::local_declaration_parts($tree,$node)->type_syntax_id;
    }
    public static function owner(\type_model\Named_Definition $type, \collect_symbols\Symbol_Store $symbols): ?\collect_symbols\Symbol_Record {
        $id = $symbols->find_symbol($type->name,\collect_symbols\SYMBOL_STRUCT,0,$type->namespace_name);
        if ($id === 0) { return null; }
        return $symbols->symbol_by_id($id);
    }
    public static function method_for(Member_Task $task, \collect_symbols\Symbol_Record $owner,
        Bindings $reader, \collect_symbols\Symbol_Store $symbols): ?\collect_symbols\Symbol_Record {
        $source = $task->context->definition->source_frontend();
        $name_node = (int)$source->tree->row($task->receiver_node_id)->next_sibling;
        $name = \collect_symbols\File_Collector::name_text($source,$name_node);
        $kind = \collect_symbols\SYMBOL_FUNCTION;
        if ($owner->is_template()) { $kind = \collect_symbols\SYMBOL_TEMPLATE_FUNCTION; }
        $id = $symbols->find_symbol($name,$kind,$owner->symbol_id,$owner->namespace_name);
        if ($id === 0) { return null; }
        $method = $symbols->symbol_by_id($id);
        if (\resolve_types\Source_Lifecycle::role($method) !== \type_model\LIFECYCLE_NONE) {
            $reader->annotations->fail($task->context->definition,$task->use_node_id,'Lifecycle bodies cannot be called explicitly');
        }
        return $method;
    }
}
