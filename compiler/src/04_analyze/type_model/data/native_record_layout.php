<?php
declare(strict_types=1);
namespace type_model;

/** Provider measurements in an exact target context, before canonical type allocation. */
final class Native_Record_Layout {
    private array $offsets /** vector<int> */ = [];
    public function __construct(public readonly string $target_triple, public readonly string $data_layout,
        public readonly int $size, public readonly int $alignment, array $offsets /** vector<int> */) {
        if (($target_triple === '') || ($data_layout === '') || ($size < 1) || ($alignment < 1)) {
            throw new \InvalidArgumentException('Invalid native record layout');
        }
        $power = 1;
        while ($power < $alignment) {
            if ($power > $alignment - $power) { throw new \InvalidArgumentException('Native alignment must be a power of two'); }
            $power = $power * 2;
        }
        if ((($size % $alignment) !== 0) || (q_count($offsets) === 0)) { throw new \InvalidArgumentException('Invalid native record storage'); }
        $previous = -1;
        foreach ($offsets as $offset) {
            if (($offset < 0) || ($offset === $previous) || ($offset < $previous) || ($offset >= $size)) {
                throw new \InvalidArgumentException('Invalid native field offset');
            }
            $this->offsets[] = $offset; $previous = $offset;
        }
    }
    public function field_count(): int { return q_count($this->offsets); }
    public function field_offset(int $index): int {
        if (($index < 0) || ($index >= q_count($this->offsets))) { throw new \InvalidArgumentException('Invalid native field index'); }
        return $this->offsets[$index];
    }
}
