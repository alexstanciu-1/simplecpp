<?php
namespace parent_proof;

final class Counter {
    public int $calls = 0;

    public function next(): int {
        $this->calls++;
        return 7;
    }
}

abstract class Base {
    protected Counter $counter;
    protected int $stored_value;

    public function __construct(Counter $counter, int $value) {
        $this->counter = $counter;
        $this->stored_value = $value;
    }

    public function value(): int {
        return $this->stored_value;
    }

    public function touch(): void {
        $this->counter->calls++;
    }
}

final class Child extends Base {
    private int $extra;

    public function __construct(Counter $counter) {
        parent::__construct($counter, $counter->next());
        $this->extra = 3;
    }

    public function total(): int {
        return parent::value() + $this->extra;
    }
}

$counter = new Counter();
$child = new Child($counter);
$child->touch();
echo $child->total(), ':', $counter->calls;
