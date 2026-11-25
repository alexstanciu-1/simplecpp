<?php
declare(strict_types=1);
namespace parse;

const EXPR_VALUE = 0;
const EXPR_TYPE = 1;
const EXPR_GROUP = 2;
const EXPR_CALL = 3;
const EXPR_TEMPLATE = 4;
const EXPR_CONSTRUCTED = 5;
const EXPR_INDEX = 6;

final class Parse_Int_Stack {
    private array $entries /** vector<int> */ = [];
    private int $used = 0;
    public function empty(): bool { return $this->used === 0; }
    public function push(int $value): void {
        if ($this->used === q_count($this->entries)) { $this->entries[] = $value; }
        else { $this->entries[$this->used] = $value; }
        ++$this->used;
    }
    public function top(): int {
        if ($this->used === 0) { throw new \LogicException('Empty parser stack'); }
        return $this->entries[$this->used - 1];
    }
    public function pop(): int {
        $value = $this->top();
        $this->used = $this->used - 1;
        return $value;
    }
}
final class Expression_Frame {
    public function __construct(public int $context, public int $node, public Parse_Int_Stack $operands, public Parse_Int_Stack $operators) {}
}
