<?php
declare(strict_types=1);

/*
 * Role: Native-family semantic declarations, with no measured ABI or C++ names.
 * Used by: family adapters and preparation acceptance
 * Flow: validated declaration -> fixed requirement/signature references -> specialization
 */
namespace type_model;

final class family_parameter {
    public function __construct(public readonly string $name,
        public readonly generic_contract $contract = generic_contract::copyable_value)
    {
    }
}

final class capability_requirement {
    public function __construct(public readonly int $slot, public readonly lifecycle_operation_kind $operation)
    {
    }
}

enum element_effect_kind: string {
    case safe_element_input = 'safe_element_input';
    case invalidate_elements = 'invalidate_elements';
}

/** Input overlap and storage invalidation are different relationships. */
final class element_effect {
    public function __construct(public readonly element_effect_kind $kind, public readonly int $receiver, public readonly ?int $safe_input = null)
    {
    }
}

final class family_operation {
    /** @param list<capability_requirement> $requirements @param list<element_effect> $effects */
    public function __construct(public readonly string $id, public readonly semantic_signature $signature,
        public readonly array $requirements = [], public readonly array $effects = [], public readonly ?int $receiver = null,
        public readonly ?named_type_reference $expose_as = null)
    {
    }
}

final class family_definition
{
    /** @param list<family_parameter> $parameters @param array<string, family_operation> $operations
     * @param array<string, string> $lifecycle */
    public function __construct(public readonly string $provider, public readonly string $id,
        public readonly array $parameters, public readonly array $operations, public readonly array $lifecycle,
        public readonly ?named_type_reference $language_type = null)
    {
    }

    public function key(): string
    {
        return json_encode([$this->provider, $this->id], JSON_THROW_ON_ERROR);
    }
}
