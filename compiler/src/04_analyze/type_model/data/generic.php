<?php
declare(strict_types=1);
namespace type_model;
const GENERIC_NONE = 0;
const GENERIC_COPYABLE_VALUE = 1;
/** Definition-level permission is independent of a favorable specialization. */
final class Generic_Contracts {
    public static function permits(int $contract, int $operation): bool {
        if ($contract !== \type_model\GENERIC_COPYABLE_VALUE) { throw new \InvalidArgumentException('Invalid generic contract'); }
        \type_model\Lifecycle_Roles::require_role($operation);
        return ($operation === \type_model\LIFECYCLE_COPY) || ($operation === \type_model\LIFECYCLE_ASSIGN) || ($operation === \type_model\LIFECYCLE_DESTROY);
    }
}
