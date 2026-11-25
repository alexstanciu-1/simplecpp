<?php
declare(strict_types=1);
namespace type_model;

/** Compare immutable lifecycle plans within the same accepted type/symbol lineage.
 * This does not equate IDs from unrelated stores or execute lifecycle operations. */
final class Lifecycle_Contracts {
    public static function operation(Lifecycle_Operation $left, Lifecycle_Operation $right): bool {
        if ($left === $right) { return true; }
        if (($left->imported !== $right->imported) || ($left->kind !== $right->kind)
            || ($left->link_name !== $right->link_name) || ($left->calling_convention !== $right->calling_convention)
            || ($left->provider !== $right->provider) || ($left->provider_id !== $right->provider_id)
            || ($left->type_id !== $right->type_id) || ($left->repeat !== $right->repeat)
            || ($left->body_symbol_id !== $right->body_symbol_id) || ($left->member_count() !== $right->member_count())) { return false; }
        for ($index = 0; $index < $left->member_count(); $index++) {
            $a = $left->member_at($index); $b = $right->member_at($index);
            if (($a->type_id !== $b->type_id) || ($a->index !== $b->index) || ($a->kind !== $b->kind)) { return false; }
            $a_operation = $a->operation; $b_operation = $b->operation;
            if ($a_operation === null) { if ($b_operation !== null) { return false; } }
            else {
                if ($b_operation === null) { return false; }
                if (!Lifecycle_Contracts::operation($a_operation,$b_operation)) { return false; }
            }
        }
        // Composition order is derived from the already compared kind/body identity.
        return true;
    }
    public static function same(Lifetime_Contract $left, Lifetime_Contract $right): bool {
        if ($left === $right) { return true; }
        $a = $left->policy(); $b = $right->policy();
        if (((int)$a->copy !== (int)$b->copy) || ((int)$a->cleanup !== (int)$b->cleanup)
            || ((int)$a->construction !== (int)$b->construction) || ((int)$a->assignment !== (int)$b->assignment)
            || ((int)$a->expiring !== (int)$b->expiring)) { return false; }
        for ($role = 1; $role < 6; $role++) {
            $present = $left->has_operation($role);
            if ($present !== $right->has_operation($role)) { return false; }
            if ($present) {
                if (!Lifecycle_Contracts::operation($left->operation($role),$right->operation($role))) { return false; }
            }
        }
        return true;
    }
}
