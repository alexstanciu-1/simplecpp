<?php
declare(strict_types=1);

/*
 * Role: Resolve ordinary declarations or demanded member calls against concrete receivers.
 * Used by: Concrete_Preparation::prepare_members()
 * Call map: Member_Worker::run() -> receiver(); owner(); method()
 * Output: private definition/receiver/argument requests, accepted by Member_Join.
 */
namespace instantiate;

final class Member_Worker
{
    /** Resolve one member demand, or report that its receiver annotation is not yet prepared. */
    public static function run(member_task $task, \resolve_symbols\Resolution_Set $names,
        \resolve_types\Definition_View $definitions, Instance_View $instances,
        \collect_symbols\Symbol_Store $symbols): member_result
    {
        $receiver = self::receiver($task, $names, $definitions, $instances);
        if ($receiver === null) {
            return new member_result($task, null);
        }
        $context = $instances->type_context($receiver);
        if (($receiver->representation->kind !== \type_model\representation_kind::structure)
            && !($context?->definition->external instanceof \type_model\family_declaration)) {
            Bindings::fail($task->context->definition, $task->use?->use_node_id ?? $task->declaration->declaration_node_id, 'Method receiver requires a source record');
        }
        $owner = $context?->definition ?? self::owner($receiver, $symbols);
        $method = $task->declaration ?? ($owner === null ? null : self::method($task, $owner, $symbols));
        if ($method === null) {
            Bindings::fail($task->context->definition, $task->use?->use_node_id ?? $task->declaration->declaration_node_id, 'Unknown or unsupported record method');
        }
        return new member_result($task, $receiver, $method, $context?->arguments ?? []);
    }

    /** Read the receiver's already-bound local annotation; no source spelling lookup or type inference. */
    public static function receiver(member_task $task, \resolve_symbols\Resolution_Set $names,
        \resolve_types\Definition_View $definitions, Instance_View $instances): ?\type_model\named_type_definition
    {
        if ($task->declaration !== null) {
            $owner = $task->context->definition;
            return $definitions->find_type($owner->is_template() ? $task->context->type_name() : $owner->name,
                $owner->is_template() ? $task->context->type_namespace() : $owner->namespace_name);
        }
        $bindings = $names->for_symbol($task->context->definition->symbol_id);
        $local = $bindings->local_for($bindings->binding_for($task->use->receiver_node_id)->local_id);
        if ($local->receiver) {
            return $task->context->receiver_type ?? throw new \LogicException('Missing concrete method receiver');
        }
        $annotation = self::receiver_annotation($task, $names);
        return Bindings::type($task->context, $annotation, $names, $definitions, $instances);
    }

    /** Pending member work depends on this bound receiver annotation, not every known type. */
    public static function receiver_annotation(member_task $task, \resolve_symbols\Resolution_Set $names): int
    {
        $bindings = $names->for_symbol($task->context->definition->symbol_id);
        $local = $bindings->local_for($bindings->binding_for($task->use->receiver_node_id)->local_id);
        $tree = $bindings->syntax;
        return $tree->nodes[$local->declaration_node_id - 1]->kind === \parse\syntax_kind::parameter_declaration
            ? \parse\Syntax_Access::parameter_parts($tree, $local->declaration_node_id)->type_syntax_id
            : \parse\Syntax_Access::local_declaration_parts($tree, $local->declaration_node_id)->type_syntax_id;
    }

    /** Ordinary nominal records use their source declaration; template records use their retained context. */
    public static function owner(\type_model\named_type_definition $type,
        \collect_symbols\Symbol_Store $symbols): ?\collect_symbols\symbol_record
    {
        $id = $symbols->find_symbol($type->name, $type->namespace_name, \collect_symbols\symbol_kind::struct_symbol);
        return $id === 0 ? null : $symbols->symbol_by_id($id);
    }

    /** Member lookup is scoped by the allocated record declaration ID, never a generated global name. */
    public static function method(member_task $task, \collect_symbols\symbol_record $owner,
        \collect_symbols\Symbol_Store $symbols): ?\collect_symbols\symbol_record
    {
        $source = $task->context->definition->frontend;
        $name_node = $source->syntax->nodes[$task->use->receiver_node_id - 1]->next_sibling_id;
        $name = \collect_symbols\Declaration_Syntax::name_text($source, $name_node);
        $kind = $owner->is_template() ? \collect_symbols\symbol_kind::template_function : \collect_symbols\symbol_kind::function_symbol;
        $id = $symbols->find_symbol($name, $owner->namespace_name, $kind, $owner->symbol_id);
        $method = $id === 0 ? null : $symbols->symbol_by_id($id);
        if (($method !== null) && (\resolve_types\Source_Lifecycle::role($method) !== null)) {
            Bindings::fail($task->context->definition, $task->use->use_node_id, 'Lifecycle bodies cannot be called explicitly');
        }
        return $method;
    }
}

