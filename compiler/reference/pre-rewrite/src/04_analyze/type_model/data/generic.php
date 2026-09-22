<?php
declare(strict_types=1);

/*
 * Role: Shared generic acceptance vocabulary.
 * Used by: source template checking and native family preparation
 * Flow: explicit parameter contract -> eligibility and permission checks
 */
namespace type_model;

/** Bare type parameters promise this baseline; value parameters have no such contract. */
enum generic_contract: string
{
    case copyable_value = 'copyable_value';

    /** Definition-level permissions are fixed by the contract, never by a favorable specialization. */
    public function permits(lifecycle_operation_kind $operation): bool
    {
        return in_array($operation, [lifecycle_operation_kind::copy_construct,
            lifecycle_operation_kind::copy_assign, lifecycle_operation_kind::destroy], true);
    }
}
