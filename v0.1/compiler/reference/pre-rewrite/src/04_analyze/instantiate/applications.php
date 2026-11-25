<?php
declare(strict_types=1);

/*
 * Role: Prepare explicit argument requests against fixed instance/type inputs.
 * Used by: Concrete_Preparation::run()
 * Call map: Application_Worker::run() -> Bindings::type(); value()
 *   [native family] family_arguments() -> Bindings::type(); Generic_Contracts::missing()
 */
namespace instantiate;

use parse\Syntax_Access;

final class Application_Worker
{
    /** Return private normalized arguments or a pending prerequisite, without allocating identities. */
    public static function run(application_task $task, \resolve_symbols\Resolution_Set $names,
        \resolve_types\Definition_View $definitions, \type_model\Type_Catalog $catalog, Instance_View $instances): application_result
    {
        $context = $task->context;
        $target = $task->application->definition;
        $instances->template_checks()->require_definition($target, $names, $catalog);
        $tree = $context->definition->frontend->syntax;
        $node = Syntax_Access::template_application_parts($tree, $task->application->use_node_id)->first_argument_id;
        if ($target->external instanceof \type_model\family_declaration) {
            return self::family_arguments($task, $names, $definitions, $instances);
        }
        if ($target->external !== null) {
            $type = Bindings::type($context, $node, $names, $definitions, $instances);
            if (($type !== null) && !\resolve_types\Storage_Definitions::eligible($type)) {
                Bindings::fail($context->definition, $node, 'Storage elements require supported copying/cleanup and no compiler-tracked allocation ownership');
            }
            return new application_result($task, $type === null ? null : [new template_argument($type)], $type === null ? [$node] : []);
        }
        $arguments = [];
        foreach ($names->for_symbol($target->symbol_id)->template_parameters as $parameter)
        {
            if ($node === 0) {
                throw new \LogicException('Bound application lost a required argument');
            }
            if ($parameter->type_syntax_id === 0)
            {
                $type = Bindings::type($context, $node, $names, $definitions, $instances);
                if ($type === null) {
                    return new application_result($task, null, [$node]);
                }
                $missing = \type_model\Generic_Contracts::missing($type, $parameter->contract);
                if ($missing !== null) {
                    Bindings::fail($context->definition, $node, 'Default generic contract requires supported ' . $missing);
                }
                $arguments[] = new template_argument($type);
            }
            else
            {
                if ($target->frontend->syntax->nodes[$parameter->type_syntax_id - 1]->kind === \parse\syntax_kind::template_application) {
                    Bindings::fail($target, $parameter->type_syntax_id, 'Only the language integer contract is supported for value template parameters');
                }
                $binding = $names->for_symbol($target->symbol_id)->name_for($parameter->type_syntax_id);
                $type = $binding->kind === \resolve_symbols\reference_kind::template_parameter
                    ? ($arguments[$binding->target]->type ?? null)
                    : \resolve_types\Annotation_Types::bound_definition($target, $parameter->type_syntax_id, $definitions, $names);
                if ($type !== $catalog->integer_literal_type) {
                    Bindings::fail($target, $parameter->type_syntax_id, 'Only the language integer contract is supported for value template parameters');
                }
                $argument = Bindings::value($context, $node, $names, $catalog, $instances);
                if ($argument->type !== $type) {
                    Bindings::fail($context->definition, $node, 'Integer template argument type mismatch');
                }
                $arguments[] = $argument;
            }
            $node = $tree->nodes[$node - 1]->next_sibling_id;
        }
        if ($node !== 0) {
            throw new \LogicException('Bound application has extra arguments');
        }
        return new application_result($task, $arguments);
    }

    /** Normalize ordered provider type arguments under the declared baseline, without preparing native code. */
    public static function family_arguments(application_task $task, \resolve_symbols\Resolution_Set $names,
        \resolve_types\Definition_View $definitions, Instance_View $instances): application_result
    {
        $owner = $task->context->definition;
        $tree = $owner->frontend->syntax;
        $node = Syntax_Access::template_application_parts($tree, $task->application->use_node_id)->first_argument_id;
        $arguments = [];
        $missing = [];
        foreach ($task->application->definition->external->definition->parameters as $parameter)
        {
            if ($node === 0) {
                throw new \LogicException('Bound family application lost a required type argument');
            }
            $type = Bindings::type($task->context, $node, $names, $definitions, $instances);
            if ($type === null) {
                $missing[] = $node;
            }
            else {
                $failure = \type_model\Generic_Contracts::missing($type, $parameter->contract);
                if ($failure !== null) {
                    Bindings::fail($owner, $node, 'Default generic contract requires supported ' . $failure);
                }
                $arguments[] = new template_argument($type);
            }
            $node = $tree->nodes[$node - 1]->next_sibling_id;
        }
        if ($node !== 0) {
            throw new \LogicException('Bound family application has surplus type arguments');
        }
        return new application_result($task, $missing === [] ? $arguments : null, $missing);
    }
}
