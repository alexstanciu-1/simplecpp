<?php
declare(strict_types=1);

/*
 * Role: Declaration references and template parameter bindings before concrete preparation.
 * Used by: Resolution_Worker; Resolution_Validity; annotation consumers
 * Flow: fixed declarations/catalog + syntax -> immutable name bindings
 */
namespace resolve_symbols;

/** The syntactic role selects the lookup namespace, not a representation or conversion. */
enum name_role {
    case type;
    case value;
    case type_family;
}

/** Target tags keep declaration identities separate from canonical types and runtime locals. */
enum reference_kind
{
    case source_type;
    case provided_type;
    case provided_record;
    case template_type;
    case template_parameter;
    case project_constant;
    case local_constant;
}

/** One occurrence and its exact target; parameter positions are zero-based within the owning result. */
final class name_binding
{
    /** Enforce the tagged target and lookup role before a binding can enter a result. */
    public function __construct(public readonly int $use_node_id, public readonly name_role $role,
        public readonly reference_kind $kind,
        public readonly int|\type_model\named_type_definition|\type_model\record_declaration $target)
    {
        $valid_target = match ($kind) {
            reference_kind::provided_type => $target instanceof \type_model\named_type_definition,
            reference_kind::provided_record => $target instanceof \type_model\record_declaration,
            reference_kind::template_parameter => is_int($target) && ($target >= 0),
            default => is_int($target) && ($target > 0),
        };
        $valid_role = match ($kind) {
            reference_kind::source_type, reference_kind::provided_type, reference_kind::provided_record => $role === name_role::type,
            reference_kind::template_type => $role === name_role::type_family,
            reference_kind::template_parameter => $role !== name_role::type_family,
            default => $role === name_role::value,
        };
        if (($use_node_id <= 0) || (!$valid_target) || (!$valid_role)) {
            throw new \LogicException('Invalid declaration binding target or role');
        }
    }
}

/** Ordered parameter identity is (owning template symbol ID, position); syntax belongs to its fixed result. */
final class template_parameter
{
    public readonly ?\type_model\generic_contract $contract;

    /** Attach the language default without resolving concrete lifecycle facts during binding. */
    public function __construct(public readonly int $declaration_node_id, public readonly int $name_node_id,
        public readonly int $type_syntax_id)
    {
        $this->contract = $type_syntax_id === 0 ? \type_model\generic_contract::copyable_value : null;
    }
}

/** A named constant lives in a lexical block; its initializer remains unevaluated source syntax. */
final class scoped_constant {
    public function __construct(public readonly int $declaration_node_id, public readonly int $scope_id)
    {
    }
}

/** Private iterative continuation; siblings share a syntactic role, never a resolved concrete type. */
final class binding_cursor {
    public function __construct(public readonly int $node_id, public readonly ?name_role $role,
        public readonly bool $siblings, public readonly ?string $description = null)
    {
    }
}

/** A use retains the definition whose formal argument roles were checked; no instance is created. */
final class template_application_binding {
    public function __construct(public readonly int $use_node_id,
        public readonly \collect_symbols\symbol_record $definition)
    {
    }
}
