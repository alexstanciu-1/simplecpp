<?php
declare(strict_types=1);

/*
 * Role: Symbolic type references and fixed definition-check tasks.
 * Used by: Template_Worker; Template_Join
 * Flow: bound declarations -> private symbolic terms -> accepted permission result
 */
namespace check_templates;

enum term_kind {
    case named;
    case parameter;
    case application;
    case constant;
    case array_type;
}

/** No layout or canonical type ID: arguments reference immutable symbolic terms. */
final class type_term
{
    public readonly bool $dependent;

    /** Parameters are keyed by their owning declaration and slot, never concrete substitutions. */
    public function __construct(public readonly term_kind $kind,
        public readonly int|string|\type_model\named_type_definition|\type_model\record_declaration $target,
        public readonly array $arguments = [])
    {
        $dependent = $kind === term_kind::parameter;
        foreach ($arguments as $argument) {
            $dependent = ($dependent) || ($argument->dependent);
        }
        $this->dependent = $dependent;
    }
}

/** One template owner, including inherited-parameter methods, in a fixed phase view. */
final class definition_task {
    public function __construct(public readonly \collect_symbols\symbol_record $owner,
        public readonly \resolve_symbols\Symbol_Resolution $bindings)
    {
    }
}

/** Private expression result; constness follows the original location through field projections. */
final class expression_type {
    public function __construct(public readonly ?type_term $type, public readonly bool $readonly = false)
    {
    }
}
