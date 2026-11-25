<?php
declare(strict_types=1);
namespace type_model;
/** Semantic production only; physical ABI and concrete lifetime permissions are separate. */
final class Result_Contracts {
    public static function production(int $kind): int {
        if (($kind < \type_model\REPRESENTATION_VOID) || ($kind > \type_model\REPRESENTATION_BYTE_SPAN)) { throw new \InvalidArgumentException('Unknown representation kind'); }
        if ($kind === \type_model\REPRESENTATION_VOID) { return \type_model\RESULT_NONE; }
        if (($kind === \type_model\REPRESENTATION_STRUCTURE) || ($kind === \type_model\REPRESENTATION_OPAQUE)) { return \type_model\RESULT_OWNED; }
        return \type_model\RESULT_VALUE;
    }
}
