<?php
declare(strict_types=1);
namespace check_bodies;
const FLOW_JUMP = 1;
const FLOW_BRANCH = 2;
const FLOW_RETURN = 3;
const FLOW_FALLTHROUGH = 4;
/** Contiguous checked statements and callable-local successor IDs. */
final class Typed_Block {
    public function __construct(public readonly int $statement_start, public readonly int $statement_count,
        public readonly int $scope_id, public readonly int $end,
        public readonly int $first = 0, public readonly int $second = 0) {
        if (($end < \check_bodies\FLOW_JUMP) || ($end > \check_bodies\FLOW_FALLTHROUGH)) {
            throw new \InvalidArgumentException('Invalid checked flow end');
        }
    }
}
/** Private builder reservation; null means this block has not been closed. */
final class Flow_Slot {
    public function __construct(public ?Typed_Block $value = null) {}
}
