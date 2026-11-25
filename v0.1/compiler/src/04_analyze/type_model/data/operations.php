<?php
declare(strict_types=1);
namespace type_model;
const IMPLEMENTATION_CALLABLE = 0;
const IMPLEMENTATION_NATIVE_OPERATION = 1;
/** Provider identity only; this descriptor does not implement a capability. */
final class Implementation_Binding {
    public function __construct(public readonly int $kind, public readonly string $provider, public readonly string $entry) {
        if (($kind !== \type_model\IMPLEMENTATION_CALLABLE) && ($kind !== \type_model\IMPLEMENTATION_NATIVE_OPERATION)) {
            throw new \InvalidArgumentException('Invalid implementation binding kind');
        }
        if (($provider === '') || ($entry === '')) { throw new \InvalidArgumentException('Implementation binding needs a provider and entry'); }
    }
}
/** Exact canonical operand/result IDs in one type lineage, not implicit conversion rules. */
final class Operation_Contract {
    private array $operands /** vector<int> */ = [];
    public function __construct(public readonly string $operation, array $operand_types /** vector<int> */,
        public readonly int $result_type, public readonly Implementation_Binding $implementation) {
        if (($operation === '') || ($result_type < 1)) { throw new \InvalidArgumentException('Invalid operation contract'); }
        foreach ($operand_types as $id) {
            if ($id < 1) { throw new \InvalidArgumentException('Invalid operand type ID'); }
            $this->operands[] = $id;
        }
    }
    public function size(): int { return q_count($this->operands); }
    public function operand_at(int $index): int {
        if (($index < 0) || ($index >= q_count($this->operands))) { throw new \OutOfBoundsException('Missing operation operand type'); }
        return $this->operands[$index];
    }
}
