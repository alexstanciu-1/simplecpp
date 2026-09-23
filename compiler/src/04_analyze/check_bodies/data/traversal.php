<?php
declare(strict_types=1);
namespace check_bodies;
/** Private suspended call, preserving semantic parameter positions including the receiver. */
final class Call_Cursor {
    public int $argument_index = 0;
    public function __construct(public readonly int $node, public readonly int $target, public readonly int $return_type,
        public readonly int $start, public readonly int $count, public int $next, public readonly int $receiver) {}
}
final class Operation_Cursor {
    public array $operands /** vector<int> */ = [];
    public int $result = 0;
    public function __construct(public readonly int $node, public int $next) {}
}
final class Place_Cursor {
    public int $position = 0;
    public array $projections /** vector<Place_Projection> */ = [];
    public function __construct(public readonly int $node, public readonly int $local, public int $type,
        public readonly array $nodes /** vector<int> */, public readonly bool $write) {}
}
/** Exactly one suspended expression operation. */
final class Body_Expression_Frame {
    public function __construct(public readonly ?Call_Cursor $call, public readonly ?Operation_Cursor $operation,
        public readonly ?Place_Cursor $place) {
        $count = 0;
        if ($call !== null) { $count++; } if ($operation !== null) { $count++; } if ($place !== null) { $count++; }
        if ($count !== 1) { throw new \LogicException('Expected one expression continuation'); }
    }
}
final class Body_Expression_Stack {
    private array $frames /** vector<Body_Expression_Frame> */ = [];
    private int $used = 0;
    public function empty(): bool { return $this->used === 0; }
    public function push(Body_Expression_Frame $frame): void {
        if ($this->used === q_count($this->frames)) { $this->frames[] = $frame; } else { $this->frames[$this->used] = $frame; }
        $this->used++;
    }
    public function top(): Body_Expression_Frame {
        if ($this->used === 0) { throw new \LogicException('Empty expression stack'); }
        return $this->frames[$this->used - 1];
    }
    public function pop(): void {
        if ($this->used === 0) { throw new \LogicException('Empty expression stack'); }
        $this->used = $this->used - 1;
    }
}
final class Body_Cursor {
    public function __construct(public readonly int $scope, public readonly int $start, public int $next) {}
}
final class Control_Cursor {
    public int $stage = 0;
    public function __construct(public readonly int $scope, public readonly int $body, public readonly int $alternative,
        public readonly int $body_block, public readonly int $alternative_block, public readonly int $join, public readonly int $header) {}
}
final class Body_Statement_Frame {
    public function __construct(public readonly ?Body_Cursor $body, public readonly ?Control_Cursor $control) {
        if (($body === null) === ($control === null)) { throw new \LogicException('Expected one statement continuation'); }
    }
}
final class Body_Statement_Stack {
    private array $frames /** vector<Body_Statement_Frame> */ = [];
    private int $used = 0;
    public function empty(): bool { return $this->used === 0; }
    public function push(Body_Statement_Frame $frame): void {
        if ($this->used === q_count($this->frames)) { $this->frames[] = $frame; } else { $this->frames[$this->used] = $frame; }
        $this->used++;
    }
    public function top(): Body_Statement_Frame {
        if ($this->used === 0) { throw new \LogicException('Empty statement stack'); }
        return $this->frames[$this->used - 1];
    }
    public function pop(): void {
        if ($this->used === 0) { throw new \LogicException('Empty statement stack'); }
        $this->used = $this->used - 1;
    }
}
