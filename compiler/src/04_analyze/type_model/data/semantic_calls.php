<?php
declare(strict_types=1);
namespace type_model;

/** Semantic passing does not imply a native ABI transport. */
final class Semantic_Parameter {
    public function __construct(public readonly Type_Reference $type, public readonly int $passing) {
        Semantic_Modes::passing_name($passing);
    }
}

final class Semantic_Result {
    public function __construct(public readonly Type_Reference $type, public readonly int $production) {
        Semantic_Modes::result_name($production);
    }
}

/** Ordered semantic positions, including an explicit receiver when declared.
 * Resolution and provider acceptance own type/position validation against declarations. */
final class Semantic_Signature {
    private array $parameters /** vector<Semantic_Parameter> */ = [];
    public function __construct(array $parameters /** vector<Semantic_Parameter> */,
        public readonly Semantic_Result $result, public readonly ?Allocation_Effect $allocation_effect = null) {
        $ordinal = 0;
        foreach ($parameters as $index => $parameter) {
            if ($index !== $ordinal) { throw new \InvalidArgumentException('Semantic parameters require an ordered list'); }
            $this->parameters[] = $parameter;
            $ordinal = $ordinal + 1;
        }
    }
    public function parameter_count(): int { return q_count($this->parameters); }
    public function parameter_at(int $index): Semantic_Parameter {
        if (($index < 0) || ($index >= q_count($this->parameters))) { throw new \OutOfBoundsException('Missing semantic parameter'); }
        return $this->parameters[$index];
    }
}
