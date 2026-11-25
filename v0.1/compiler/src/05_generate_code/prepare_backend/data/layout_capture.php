<?php
declare(strict_types=1);
namespace prepare_backend;

/** Full-width canonical ID; compact value records currently require narrower integer fields. */
final class Layout_Visit {
    public int $id = 0;
    public bool $ready = false;
}
/** Private traversal state, discarded after capture; never part of the accepted dependency graph. */
final class Layout_Capture_Row {
    public function __construct(public readonly \type_model\Named_Definition $definition,
        public readonly array $fields /** vector<\type_model\Type_Member> */, public readonly array $children /** vector<int> */) {}
}
final class Layout_Dependency_Pair {
    public function __construct(public readonly Layout_Dependency $left, public readonly Layout_Dependency $right) {}
}
